<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],

    'openai' => [
        /*
         * Voice used for Russian TTS generation via the Laravel AI SDK.
         * Valid OpenAI voices: alloy, echo, fable, onyx, nova, shimmer.
         */
        'tts_voice' => env('OPENAI_TTS_VOICE', 'echo'),

        /*
         * Display label for the TTS model shown in the Bulk Actions cost estimate.
         * The Laravel AI SDK does not expose the underlying TTS model name, so this
         * is purely informational. Update if you switch between tts-1 and tts-1-hd.
         */
        'tts_model_label' => env('OPENAI_TTS_MODEL_LABEL', 'tts-1-hd'),

        /*
         * OpenAI TTS pricing per 1,000,000 characters.
         * tts-1:    $15.00 / 1M chars
         * tts-1-hd: $30.00 / 1M chars
         * Update this value when OpenAI changes its pricing.
         */
        'tts_price_per_million_chars' => (float) env('OPENAI_TTS_PRICE_PER_MILLION_CHARS', 30.00),

        /*
         * Display label for the stress-correction model shown in the Bulk Actions
         * cost estimate. Must match the #[Model] attribute on RussianStressCorrectorAgent.
         */
        'stress_model_label' => env('OPENAI_STRESS_MODEL_LABEL', 'gpt-5.4'),

        /*
         * gpt-5.4 input / output token pricing per 1,000,000 tokens.
         * Update these values when OpenAI changes its pricing.
         */
        'gpt_5_4_input_price_per_million' => (float) env('OPENAI_GPT54_INPUT_PRICE_PER_MILLION', 3.00),
        'gpt_5_4_output_price_per_million' => (float) env('OPENAI_GPT54_OUTPUT_PRICE_PER_MILLION', 15.00),
    ],

];
