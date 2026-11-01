<?php

namespace App\Http\Controllers;

use App\Models\CreditPurchase;
use App\Models\User;
use App\Services\CreditService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

class StripeWebhookController extends Controller
{
    public function __construct(
        private readonly CreditService $creditService,
    ) {}

    public function handle(Request $request): Response
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature', '');

        try {
            $event = Webhook::constructEvent(
                $payload,
                $sigHeader,
                config('services.stripe.webhook_secret'),
            );
        } catch (SignatureVerificationException $e) {
            Log::warning('Stripe webhook: signature invalide.', ['error' => $e->getMessage()]);

            return response('Signature invalide.', 400);
        }

        // Asynchronous methods (SEPA, bank transfer) complete the session
        // before the money arrives, then send async_payment_succeeded.
        if (in_array($event->type, ['checkout.session.completed', 'checkout.session.async_payment_succeeded'], true)) {
            $this->handleCheckoutSessionPaid($event->data->object);
        }

        return response('OK', 200);
    }

    private function handleCheckoutSessionPaid(object $session): void
    {
        if (($session->payment_status ?? null) !== 'paid') {
            Log::info('Stripe webhook: session non payée, crédits non accordés.', [
                'session_id' => $session->id,
                'payment_status' => $session->payment_status ?? null,
            ]);

            return;
        }

        $purchaseId = $session->metadata->purchase_id ?? null;

        if (! $purchaseId) {
            Log::error('Stripe webhook: purchase_id manquant dans metadata.', [
                'session_id' => $session->id,
            ]);

            return;
        }

        $purchase = CreditPurchase::find($purchaseId);

        if (! $purchase) {
            Log::error('Stripe webhook: CreditPurchase introuvable.', [
                'purchase_id' => $purchaseId,
            ]);

            return;
        }

        if ($purchase->isCompleted()) {
            return;
        }

        // The purchase must belong to this very Checkout Session: metadata
        // alone could point at any pending purchase.
        if ($purchase->stripe_session_id !== $session->id) {
            Log::error('Stripe webhook: session Stripe différente de celle de l\'achat.', [
                'purchase_id' => $purchase->id,
                'session_id' => $session->id,
            ]);

            return;
        }

        if (isset($session->payment_intent)) {
            $purchase->stripe_payment_intent_id = $session->payment_intent;
            $purchase->save();
        }

        $user = User::find($purchase->user_id);

        if (! $user) {
            Log::error('Stripe webhook: utilisateur introuvable.', [
                'user_id' => $purchase->user_id,
            ]);

            return;
        }

        $this->creditService->credit($user, $purchase->credits, $purchase);

        Log::info('Crédits accordés via Stripe.', [
            'user_id' => $user->id,
            'credits' => $purchase->credits,
            'session_id' => $session->id,
        ]);
    }
}
