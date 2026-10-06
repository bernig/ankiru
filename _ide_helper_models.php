<?php

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property string $operation
 * @property int|null $prompt_tokens
 * @property int|null $completion_tokens
 * @property int|null $characters
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\User $user
 * @method static \Database\Factories\ApiUsageLogFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiUsageLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiUsageLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiUsageLog query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiUsageLog whereCharacters($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiUsageLog whereCompletionTokens($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiUsageLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiUsageLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiUsageLog whereOperation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiUsageLog wherePromptTokens($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiUsageLog whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApiUsageLog whereUserId($value)
 */
	class ApiUsageLog extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property string $pack_slug
 * @property int $credits
 * @property int $amount_cents
 * @property string $currency
 * @property string $stripe_session_id
 * @property string|null $stripe_payment_intent_id
 * @property string $status
 * @property \Carbon\CarbonImmutable|null $credited_at
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\User $user
 * @method static \Database\Factories\CreditPurchaseFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreditPurchase newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreditPurchase newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreditPurchase query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreditPurchase whereAmountCents($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreditPurchase whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreditPurchase whereCreditedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreditPurchase whereCredits($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreditPurchase whereCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreditPurchase whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreditPurchase wherePackSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreditPurchase whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreditPurchase whereStripePaymentIntentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreditPurchase whereStripeSessionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreditPurchase whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreditPurchase whereUserId($value)
 */
	class CreditPurchase extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property string $original_file_name
 * @property array<array-key, mixed>|null $csv_rows
 * @property bool $has_csv_loaded
 * @property \Carbon\CarbonImmutable|null $last_accessed_at
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\User $user
 * @method static \Database\Factories\CsvDraftFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CsvDraft newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CsvDraft newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CsvDraft query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CsvDraft whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CsvDraft whereCsvRows($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CsvDraft whereHasCsvLoaded($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CsvDraft whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CsvDraft whereLastAccessedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CsvDraft whereOriginalFileName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CsvDraft whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CsvDraft whereUserId($value)
 */
	class CsvDraft extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property \Carbon\CarbonImmutable|null $email_verified_at
 * @property string $password
 * @property string|null $openai_api_key
 * @property int $credits
 * @property string|null $accent_color
 * @property bool $accent_bold
 * @property bool $accent_unicode
 * @property string|null $learning_context
 * @property string|null $remember_token
 * @property \Carbon\CarbonImmutable|null $tour_completed_at
 * @property \Carbon\CarbonImmutable|null $tour_new_file_tip_seen_at
 * @property \Carbon\CarbonImmutable|null $tour_first_row_tip_seen_at
 * @property bool $is_admin
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ApiUsageLog> $apiUsageLogs
 * @property-read int|null $api_usage_logs_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CreditPurchase> $creditPurchases
 * @property-read int|null $credit_purchases_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CsvDraft> $csvDrafts
 * @property-read int|null $csv_drafts_count
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereAccentBold($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereAccentColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereAccentUnicode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCredits($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereIsAdmin($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereLearningContext($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereOpenaiApiKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTourCompletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTourFirstRowTipSeenAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTourNewFileTipSeenAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 */
	class User extends \Eloquent implements \Illuminate\Contracts\Auth\MustVerifyEmail {}
}

