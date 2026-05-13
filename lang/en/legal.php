<?php

return [

    // ── Legal notice ──────────────────────────────────────────────────────────
    'mentions_title' => 'Legal Notice',
    'mentions_subtitle' => 'Legal information related to this website.',
    'mentions_seo_description' => 'Legal notice for Ankiru.org: publisher details, hosting provider information, and intellectual property statement.',
    'mentions_updated' => 'Last updated: May 2026.',

    'publisher_title' => 'Website publisher',
    'publisher_intro' => 'This site is operated personally, with no commercial activity.',
    'publisher_name_label' => 'Name:',
    'publisher_name_value' => 'Emmanuel Bernigaud',
    'publisher_address_label' => 'Address:',
    'publisher_email_label' => 'Contact:',
    'publisher_lcen_note' => 'Under French law (LCEN, article 6-III-2°), a non-commercial personal website publisher may choose not to publish their personal address, provided they have communicated it to the hosting provider, who can share it with competent authorities on request.',

    'publication_director_title' => 'Publication director',
    'publication_director_value' => 'Emmanuel Bernigaud',

    'hosting_title' => 'Hosting',
    'hosting_intro' => 'This site is hosted by:',
    'hosting_name_label' => 'Company:',
    'hosting_address_label' => 'Address:',
    'hosting_phone_label' => 'Phone:',
    'hosting_website_label' => 'Website:',

    'ip_title' => 'Intellectual property',
    'ip_content' => 'All content on this site (text, source code, visuals) is the exclusive property of Emmanuel Bernigaud. Any reproduction, representation, or distribution, in whole or in part, without prior written authorisation is prohibited.',

    'liability_title' => 'Limitation of liability',
    'liability_content' => 'The publisher endeavours to keep information on this site accurate and up to date. AI features depend on the OpenAI API; their availability is subject to that third party\'s terms of service. The publisher cannot be held liable for service interruptions or results produced by AI models.',

    // ── Privacy policy ────────────────────────────────────────────────────────
    'privacy_title' => 'Privacy Policy',
    'privacy_subtitle' => 'How we collect and use your data.',
    'privacy_seo_description' => 'Privacy policy for Ankiru.org: what data we collect, how we use it, and how we protect your personal information.',
    'privacy_updated' => 'Last updated: May 2026.',

    'controller_title' => 'Data controller',
    'controller_content' => 'This site is operated personally by Emmanuel Bernigaud (:contact_email), with no commercial activity. For any questions regarding your data, you can contact that address directly.',

    'collected_data_title' => 'Data collected',
    'collected_data_intro' => 'The following data is collected when using the site:',
    'collected_data_account' => 'Account: name, email address, and password (hashed with bcrypt, never stored in plain text).',
    'collected_data_apikey' => 'OpenAI API key: provided voluntarily, stored encrypted in the database, used exclusively for your own requests.',
    'collected_data_drafts' => 'CSV drafts: the text content you enter or import, kept so you can resume your work.',
    'collected_data_logs' => 'API usage logs: token and character counters per operation. The content of your requests is never stored.',
    'collected_data_contact' => 'Contact form: name, email, and message.',
    'collected_data_cookies' => 'Technical cookies: session cookie (authentication), CSRF cookie (form security), and language cookie (language preference).',

    'purposes_title' => 'Purposes of processing',
    'purposes_intro' => 'The data collected is used to:',
    'purposes_auth' => 'Manage your account and authenticate you.',
    'purposes_ai' => 'Power AI features: your text is sent to the OpenAI API via your own key on each request.',
    'purposes_contact' => 'Reply to messages sent via the contact form.',
    'purposes_prefs' => 'Remember your language preference.',

    'retention_title' => 'Data retention',
    'retention_account' => 'Account data: kept until the account is deleted.',
    'retention_drafts' => 'CSV drafts and usage logs: kept until deleted by the user or until the account is deleted.',
    'retention_contact' => 'Contact messages: deleted once a reply has been sent.',

    'third_parties_title' => 'Third-party sharing',
    'third_parties_openai' => 'OpenAI (api.openai.com): text submitted for translation, stress correction, or text-to-speech is sent to the OpenAI API using your own key. You remain subject to OpenAI\'s terms of service.',
    'third_parties_none' => 'No other data is shared with third parties. No advertising, no trackers.',

    'cookies_title' => 'Cookies',
    'cookies_intro' => 'This site uses only strictly necessary cookies:',
    'cookies_session' => 'Laravel session cookie: keeps you logged in.',
    'cookies_csrf' => 'CSRF cookie: protects forms against cross-site request forgery attacks.',
    'cookies_locale' => 'Language cookie: remembers your language preference.',
    'cookies_no_tracking' => 'No advertising, analytics, or tracking cookies are used.',

    'security_title' => 'Security',
    'security_content' => 'Passwords are hashed (bcrypt). API keys are encrypted in the database. All communications are secured via HTTPS.',

    'rights_title' => 'Your rights',
    'rights_content' => 'Under applicable privacy law (GDPR), you have the right to access, correct, delete, port, restrict, and object to the processing of your data. To exercise these rights, contact :contact_email. If your concern remains unresolved, you may lodge a complaint with the relevant supervisory authority (in France: cnil.fr).',
];
