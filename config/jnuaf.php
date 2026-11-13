<?php

return [

    // Édition en cours : les inscriptions et l'unicité de l'email sont rattachées à cette valeur.
    'edition' => env('JNUAF_EDITION', '2027'),

    'event_date' => env('JNUAF_EVENT_DATE', '2027-03-08'),

    // Nombre de jours avant l'événement où le rappel est envoyé.
    'reminder_days_before' => 3,

    'registrations_open' => env('JNUAF_REGISTRATIONS_OPEN', true),

    // Jeton de l'adresse POST /_deploy (hébergement sans SSH). Vide = adresse désactivée.
    'deploy_token' => env('DEPLOY_TOKEN'),

    // true sur les environnements de test : en-tête X-Robots-Tag + balise meta « noindex ».
    'noindex' => (bool) env('JNUAF_NOINDEX', false),

    'admin' => [
        'email' => env('JNUAF_ADMIN_EMAIL', 'admin@jnuaf.org'),
        'password' => env('JNUAF_ADMIN_PASSWORD'),
    ],

    'contacts' => [
        'ci' => ['label' => "Côte d'Ivoire", 'phone' => '+225 07 77 20 08 73', 'whatsapp' => '2250777200873'],
        'fr' => ['label' => 'France', 'phone' => '+33 7 46 38 98 23', 'whatsapp' => '33746389823'],
    ],

    'committee' => [
        'countries' => ["Côte d'Ivoire", 'France', 'Sénégal', 'Maroc'],
        // Les 7 équipes opérationnelles de #JNUAF.ORG, plus les rôles territoriaux.
        'poles' => [
            'Programmes',
            'Communication & Médias',
            'Partenariats & Sponsoring',
            'Mobilisation & Communauté',
            'Tech & Outils numériques',
            'Logistique & Opérations',
            'Suivi, Évaluation & Reporting',
            'Point focal / délégation territoriale',
            'Peu importe',
        ],
        'max_file_kb' => 2048,
    ],

    /*
    |--------------------------------------------------------------------------
    | Contenus éditoriaux de l'organisation #JNUAF.ORG
    |--------------------------------------------------------------------------
    | Regroupés ici pour être mis à jour sans toucher aux vues. Ne publier que
    | des informations confirmées (partenariats, chiffres, lieux).
    */
    'organisation' => [
        'name' => '#JNUAF.ORG',
        'tagline' => "Organisation internationale pour la place des femmes dans le numérique",
    ],

    'history' => [
        ['period' => '2021 – 2025', 'title' => 'Naissance en Côte d\'Ivoire',
            'text' => "La JNUAF naît à l'initiative de la Fondation NAHOMIE 9.1, de la Communauté J'aime Cocody et de la Communauté Virtuelle de Côte d'Ivoire, puis grandit d'édition en édition."],
        ['period' => '2026', 'title' => 'Première grande édition en France',
            'text' => 'Le modèle est éprouvé hors de Côte d\'Ivoire : une communauté franco-africaine se constitue autour de la JNUAF.'],
        ['period' => '2026 – 2027', 'title' => 'Structuration de #JNUAF.ORG',
            'text' => "Statuts, règlement intérieur, gouvernance, équipes opérationnelles et Points focaux : la JNUAF devient une organisation."],
        ['period' => '2027', 'title' => 'Déploiement territorial et international',
            'text' => 'Programmes tout au long de l\'année et JNUAF 2027, le grand temps fort, dans plusieurs territoires d\'Afrique et d\'Europe.'],
    ],

    // Modèle d'intervention du Plan d'action 2026-2027.
    'model' => [
        ['verb' => 'Sensibiliser', 'text' => 'Faire découvrir les métiers du numérique aux collégiennes, lycéennes et étudiantes.'],
        ['verb' => 'Inspirer', 'text' => 'Donner à voir des parcours de femmes du numérique : role models et témoignages.'],
        ['verb' => 'Former', 'text' => 'Proposer formations, ateliers et ressources, notamment en IA et en tech.'],
        ['verb' => 'Connecter', 'text' => 'Relier participantes, mentors, entreprises et partenaires dans un réseau international.'],
        ['verb' => 'Insérer', 'text' => 'Accompagner vers les stages, l\'alternance et l\'emploi grâce à la Boîte à CV et aux entreprises partenaires.'],
    ],

    'programs' => [
        ['name' => 'Tournée du Numérique', 'tag' => 'Sensibiliser', 'text' => 'Interventions dans les collèges, lycées et établissements pour susciter des vocations.'],
        ['name' => 'JNUAF Learn', 'tag' => 'Former', 'text' => 'Parcours de formation et ressources en ligne autour du numérique et de l\'IA.'],
        ['name' => 'Webinaires', 'tag' => 'Inspirer', 'text' => 'Rendez-vous en ligne avec des expertes et des professionnelles, ouverts à toutes.'],
        ['name' => 'Mentorat & Role Models', 'tag' => 'Connecter', 'text' => 'Accompagnement individuel ou en groupe par des femmes du secteur.'],
        ['name' => 'Boîte à CV', 'tag' => 'Insérer', 'text' => 'Mise en relation des candidates avec les entreprises partenaires pour stages et emplois.'],
        ['name' => 'Événements territoriaux', 'tag' => 'Rassembler', 'text' => 'Rencontres locales animées par les Points focaux, jusqu\'au grand temps fort JNUAF 2027.'],
    ],

    'governance' => [
        ['level' => 'Gouvernance', 'body' => 'Bureau exécutif / Direction', 'text' => 'Orientations stratégiques, secrétariat général, représentation et respect des statuts.'],
        ['level' => 'Pilotage opérationnel', 'body' => 'Bureau de Pilotage', 'text' => 'Coordination du plan d\'action, des équipes et des territoires.'],
        ['level' => 'Déploiement territorial', 'body' => 'Points focaux / délégations', 'text' => 'Coordination locale, mobilisation, partenariats, bénévoles et reporting.'],
    ],

    // Les équipes opérationnelles reprennent les pôles du comité, hors rôles territoriaux.
    'teams' => [
        'Programmes' => 'Conception et animation des programmes et du temps fort.',
        'Communication & Médias' => 'Image, réseaux sociaux, relations presse, contenus.',
        'Partenariats & Sponsoring' => 'Relations entreprises, institutions et bailleurs.',
        'Mobilisation & Communauté' => 'Bénévoles, participantes, ambassadrices.',
        'Tech & Outils numériques' => 'Plateformes, données et support technique.',
        'Logistique & Opérations' => 'Lieux, matériel, accueil et déroulé des événements.',
        'Suivi, Évaluation & Reporting' => 'Indicateurs d\'impact, bilans et rapports.',
    ],

    'partners' => [
        'types' => [
            'sponsoring' => 'Sponsoring / soutien financier',
            'skills' => 'Mécénat de compétences (mentors, formateurs)',
            'venue' => "Accueil d'un événement ou d'une étape de la Tournée",
            'equipment' => 'Équipements, logistique, matériel',
            'jobs' => 'Offres de stages et d\'emplois (Boîte à CV)',
            'media' => 'Partenariat média / communication',
            'institution' => 'Partenariat institutionnel (école, collectivité, bailleur)',
            'other' => 'Autre',
        ],
        'benefits' => [
            'Visibilité auprès d\'une communauté franco-africaine engagée pour l\'égalité dans le numérique.',
            'Accès à un vivier de talents féminins : étudiantes, jeunes diplômées, reconversions.',
            'Engagement concret de vos équipes : mentorat, interventions, jurys.',
            'Contribution mesurable à vos objectifs RSE et diversité, avec un reporting d\'impact.',
        ],
    ],

    'privacy' => [
        // Inscriptions anonymisées N mois après la date de l'événement (commande jnuaf:purge-data).
        'retention_months' => 12,
        // Candidatures au comité supprimées N mois après leur décision (retenue / non retenue).
        'committee_retention_months' => 6,
    ],

    // Adresse supplémentaire prévenue des nouvelles candidatures et demandes partenaires
    // (en plus des comptes administrateurs).
    'notify_email' => env('JNUAF_NOTIFY_EMAIL'),

    // Mentions légales et politique de confidentialité (modifiables dans Admin > Paramètres).
    'legal' => [
        'controller' => "Association JNUAF - Journ\u{e9}e du Num\u{e9}rique au F\u{e9}minin, 13 avenue Morane-Saulnier, 78140 V\u{e9}lizy-Villacoublay",     // responsable du traitement
        'privacy_email' => 'team@jnuaf.org',  // contact protection des données
        'publisher' => "Association JNUAF - Journ\u{e9}e du Num\u{e9}rique au F\u{e9}minin, 13 avenue Morane-Saulnier, 78140 V\u{e9}lizy-Villacoublay (forme juridique et n\u{b0} RNA \u{e0} compl\u{e9}ter)",      // éditeur du site
        'director' => 'Amani Kouadio Samuel',       // directeur·rice de la publication
        'host' => "LWS (Ligne Web Services), SAS au capital de 500 000 euros, RCS Paris B 851 993 683, 10 rue Penthi\u{e8}vre, 75008 Paris, France - https://www.lws.fr (t\u{e9}l\u{e9}phone \u{e0} compl\u{e9}ter)",           // hébergeur
    ],
];
