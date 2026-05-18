<?php

return [

    /*
     * TTS conversion: credits deducted per character.
     * Price parity: TTS = $30/M chars, GPT input = $3/M tokens → 10x ratio.
     */
    'tts_credits_per_char' => (int) env('CREDITS_TTS_PER_CHAR', 10),

    /*
     * Base unit: credits per unit and base price in cents.
     * The user picks a number of units (multiplier).
     * Base cost = quantity × unit_price_cents (before fees).
     */
    'unit_credits' => (int) env('CREDITS_UNIT_CREDITS', 100_000),
    'unit_price_cents' => (int) env('CREDITS_UNIT_PRICE_CENTS', 100),  // €1.00

    /*
     * Minimum and maximum units per purchase.
     */
    'min_quantity' => (int) env('CREDITS_MIN_QUANTITY', 1),
    'max_quantity' => (int) env('CREDITS_MAX_QUANTITY', 50),

    /*
     * Operating fees displayed transparently to the user.
     * Each fee entry has:
     *   - label       : description shown to the user
     *   - percent     : percentage of the base price (0 if absent)
     *   - fixed_cents : fixed amount in cents added on top (0 if absent)
     *
     * All fees are summed and added to the base price to get the total.
     */
    'fees' => [
        [
            'label' => 'Frais de transaction (Stripe)',
            'percent' => 2.9,
            'fixed_cents' => 30,
        ],
        [
            'label' => 'Hébergement & maintenance',
            'percent' => 5.0,
            'fixed_cents' => 0,
        ],
    ],

];
