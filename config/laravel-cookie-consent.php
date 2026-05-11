<?php

return [

    'cookie_prefix' => env('APP_NAME', 'Laravel_App'),

    'enabled' => env('COOKIE_CONSENT_ENABLED', true),

    'asset_url' => env('COOKIE_CONSENT_ASSET_URL', null),

    /*
     * How long the consent cookie persists (days).
     * 365 days = the user won't be prompted again for a year after accepting.
     */
    'cookie_lifetime' => env('COOKIE_CONSENT_LIFETIME', 365),

    /*
     * How long the rejection cookie persists (days).
     * Short on purpose: since there are no optional cookies, a rejection
     * only means "remind me later".
     */
    'reject_lifetime' => env('COOKIE_REJECT_LIFETIME', 30),

    /*
     * Bar layout: minimal and unobtrusive at the bottom of the screen.
     */
    'consent_modal_layout' => env('COOKIE_CONSENT_MODAL_LAYOUT', 'cloud-inline'),

    /*
     * No preferences modal: only essential cookies are used,
     * so granular control would be misleading.
     */
    'preferences_modal_enabled' => false,

    'preferences_modal_layout' => 'bar',

    'flip_button' => true,

    /*
     * Do NOT block page interaction: the banner is informational only,
     * as all cookies are strictly necessary.
     */
    'disable_page_interaction' => false,

    'theme' => 'dark',

    /*
     * soft-neutral blends well with the app's zinc/white palette.
     */
    'theme_preset' => env('COOKIE_CONSENT_THEME_PRESET', 'soft-neutral'),

    'cookie_title' => 'Ce site utilise des cookies',

    'cookie_description' => 'Ce site utilise uniquement des cookies strictement nécessaires à son fonctionnement : session d\'authentification, protection CSRF et mémorisation de la langue. Aucun cookie de traçage ou publicitaire n\'est utilisé.',

    'cookie_accept_btn_text' => 'Accepter',

    'cookie_reject_btn_text' => 'Refuser',

    'cookie_preferences_btn_text' => 'Préférences',

    'cookie_preferences_save_text' => 'Enregistrer',

    'cookie_modal_title' => 'Préférences de cookies',

    'cookie_modal_intro' => 'Ce site n\'utilise que des cookies essentiels au fonctionnement du service.',

    'cookie_categories' => [
        'necessary' => [
            'enabled' => true,
            'locked' => true,
            'title' => 'Cookies essentiels',
            'description' => 'Cookies de session (authentification, protection CSRF) et mémorisation de la langue. Indispensables au bon fonctionnement du site.',
        ],
        'analytics' => [
            'enabled' => false,
            'locked' => false,
            'title' => 'Cookies analytiques',
            'description' => 'Non utilisés sur ce site.',
        ],
        'marketing' => [
            'enabled' => false,
            'locked' => false,
            'title' => 'Cookies publicitaires',
            'description' => 'Non utilisés sur ce site.',
        ],
        'preferences' => [
            'enabled' => false,
            'locked' => false,
            'title' => 'Cookies de préférences',
            'description' => 'Non utilisés sur ce site.',
        ],
    ],

    'policy_links' => [
        [
            'text' => 'Politique de confidentialité',
            'link' => env('COOKIE_CONSENT_PRIVACY_URL', '/legal/confidentialite'),
        ],
    ],

];
