<?php

return [

    // ── Mentions légales ──────────────────────────────────────────────────────
    'mentions_title' => 'Mentions lgales',
    'mentions_subtitle' => 'Informations lgales relatives  ce site.',
    'mentions_seo_description' => 'Mentions légales d\'Ankiru.org : informations sur l\'éditeur, l\'hébergeur et la propriété intellectuelle du site.',
    'mentions_updated' => 'Dernière mise à jour : mai 2026.',

    'publisher_title' => 'Éditeur du site',
    'publisher_intro' => 'Ce site est édité à titre personnel, sans activité commerciale.',
    'publisher_name_label' => 'Nom :',
    'publisher_name_value' => 'Emmanuel Bernigaud',
    'publisher_address_label' => 'Adresse :',
    'publisher_email_label' => 'Contact :',
    'publisher_lcen_note' => 'Conformément à l\'article 6-III-2° de la LCEN, l\'éditeur d\'un site à titre non professionnel peut choisir de ne pas publier ses coordonnées personnelles dès lors qu\'il les a communiquées à l\'hébergeur, qui peut les transmettre à toute autorité compétente sur demande.',

    'publication_director_title' => 'Directeur de la publication',
    'publication_director_value' => 'Emmanuel Bernigaud',

    'hosting_title' => 'Hébergement',
    'hosting_intro' => 'Ce site est hébergé par :',
    'hosting_name_label' => 'Société :',
    'hosting_address_label' => 'Adresse :',
    'hosting_phone_label' => 'Téléphone :',
    'hosting_website_label' => 'Site web :',

    'ip_title' => 'Propriété intellectuelle',
    'ip_content' => 'L\'ensemble du contenu de ce site (textes, code source, visuels) est la propriété exclusive d\'Emmanuel Bernigaud. Toute reproduction, représentation ou diffusion, totale ou partielle, sans autorisation préalable écrite est interdite.',

    'liability_title' => 'Limitation de responsabilité',
    'liability_content' => 'L\'éditeur s\'efforce de maintenir les informations publiées à jour et exactes. Les fonctionnalités IA dépendent de l\'API OpenAI ; leur disponibilité est soumise aux conditions d\'utilisation de ce tiers. L\'éditeur ne saurait être tenu responsable des interruptions de service ou des résultats produits par les modèles d\'intelligence artificielle.',

    // ── Politique de confidentialité ─────────────────────────────────────────
    'privacy_title' => 'Politique de confidentialit',
    'privacy_subtitle' => 'Comment nous collectons et utilisons vos donnes.',
    'privacy_seo_description' => 'Politique de confidentialité d\'Ankiru.org : données collectées, finalité, durée de conservation et droits de vos informations personnelles.',
    'privacy_updated' => 'Dernière mise à jour : mai 2026.',

    'controller_title' => 'Responsable du traitement',
    'controller_content' => 'Ce site est édité à titre personnel par Emmanuel Bernigaud (:contact_email), sans activité commerciale. Pour toute question relative à vos données, vous pouvez contacter directement cette adresse.',

    'collected_data_title' => 'Données collectées',
    'collected_data_intro' => 'Les données suivantes sont collectées lors de l\'utilisation du site :',
    'collected_data_account' => 'Compte : nom, adresse e-mail et mot de passe (haché avec bcrypt — jamais stocké en clair).',
    'collected_data_apikey' => 'Clé API OpenAI : fournie volontairement, stockée chiffrée en base de données, utilisée exclusivement pour vos propres requêtes.',
    'collected_data_drafts' => 'Brouillons CSV : le contenu textuel que vous saisissez ou importez, conservé pour vous permettre de reprendre votre travail.',
    'collected_data_logs' => 'Journaux d\'utilisation API : compteurs de tokens et de caractères par opération. Le contenu de vos requêtes n\'est jamais enregistré.',
    'collected_data_contact' => 'Formulaire de contact : nom, e-mail et message.',
    'collected_data_cookies' => 'Cookies techniques : cookie de session (authentification), cookie CSRF (sécurité des formulaires) et cookie de langue (préférence de langue).',

    'purposes_title' => 'Finalités du traitement',
    'purposes_intro' => 'Les données collectées sont utilisées pour :',
    'purposes_auth' => 'Gestion de votre compte et authentification.',
    'purposes_ai' => 'Fonctionnement des fonctionnalités IA : votre texte est transmis à l\'API OpenAI via votre propre clé à chaque requête.',
    'purposes_contact' => 'Répondre aux messages envoyés via le formulaire de contact.',
    'purposes_prefs' => 'Mémoriser votre préférence de langue.',

    'retention_title' => 'Durée de conservation',
    'retention_account' => 'Données de compte : conservées jusqu\'à la suppression du compte.',
    'retention_drafts' => 'Brouillons CSV et journaux d\'utilisation : conservés jusqu\'à suppression par l\'utilisateur ou suppression du compte.',
    'retention_contact' => 'Messages de contact : supprimés dès que la réponse a été apportée.',

    'third_parties_title' => 'Transmission à des tiers',
    'third_parties_openai' => 'OpenAI (api.openai.com) : le texte soumis à la traduction, à la correction d\'accents ou à la synthèse vocale est envoyé à l\'API OpenAI en utilisant votre propre clé. Vous restez soumis aux conditions d\'utilisation d\'OpenAI.',
    'third_parties_none' => 'Aucune autre donnée n\'est transmise à des tiers. Aucune publicité, aucun tracker.',

    'cookies_title' => 'Cookies',
    'cookies_intro' => 'Ce site utilise uniquement des cookies strictement nécessaires :',
    'cookies_session' => 'Cookie de session Laravel : maintient votre connexion.',
    'cookies_csrf' => 'Cookie CSRF : protège les formulaires contre les attaques de type cross-site.',
    'cookies_locale' => 'Cookie de langue : retient votre préférence de langue.',
    'cookies_no_tracking' => 'Aucun cookie publicitaire, analytique ou de suivi n\'est utilisé.',

    'security_title' => 'Sécurité',
    'security_content' => 'Les mots de passe sont hachés (bcrypt). Les clés API sont chiffrées en base de données. Les échanges sont sécurisés par HTTPS.',

    'rights_title' => 'Vos droits',
    'rights_content' => 'Conformément au RGPD, vous disposez d\'un droit d\'accès, de rectification, d\'effacement, de portabilité, de limitation et d\'opposition au traitement de vos données. Pour exercer ces droits, contactez :contact_email. En cas de litige non résolu, vous pouvez introduire une réclamation auprès de la CNIL (cnil.fr).',
];
