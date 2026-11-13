@php
    // Partie text/plain uniquement ; les données ne sont pas du HTML.
    $kind = $data['kind'] ?? 'generic_partner';
    $name = trim((string) ($data['name'] ?? ''));
    $adminMail = in_array($kind, ['admin_document', 'admin_revoked', 'generic_admin'], true);
    $lines = ['JNUAF — Espace documentaire', '', 'Bonjour'.(!$adminMail && $name !== '' ? ' '.$name : '').',', ''];
    switch ($kind) {
        case 'invitation':
            $lines[] = 'Votre espace documentaire partenaire est disponible.';
            $lines[] = 'Adresse email à utiliser : '.$data['email'];
            $lines[] = '';
            $lines[] = 'Accéder à mes documents : '.$data['url'];
            $lines[] = '';
            $lines[] = 'Ouvrez cet espace et demandez votre code de connexion. Ce lien est personnel : ne le transférez pas.';
            break;
        case 'code':
            $lines[] = 'Votre code de connexion : '.$data['code'];
            $lines[] = 'Valable '.($data['valid_minutes'] ?? 5).' minutes à compter de son émission, pour une seule utilisation.';
            $lines[] = 'Ne communiquez ce code à personne.';
            $lines[] = 'Si vous ne l’avez pas demandé, vous pouvez ignorer ce message et contacter le comité en cas de doute.';
            break;
        case 'partner_revoked':
            $lines[] = 'Votre précédent lien ne permet plus d’accéder à votre espace documentaire.';
            $lines[] = 'Veuillez contacter le comité pour recevoir un nouveau lien.';
            break;
        case 'admin_revoked':
            $lines[] = 'Un accès partenaire est à renouveler.';
            $lines[] = 'Partenaire : '.$data['name'];
            $lines[] = 'Email : '.$data['email'];
            $lines[] = 'Motif : '.$data['reason'];
            $lines[] = 'Gérer cet accès : '.$data['url'];
            break;
        case 'admin_document':
            $lines[] = 'Un premier accès à un document a été enregistré.';
            $lines[] = 'Partenaire : '.$data['name'];
            $lines[] = 'Email : '.$data['email'];
            $lines[] = 'Document : '.$data['document'];
            $lines[] = 'Accès : '.$data['action'];
            $lines[] = 'Date : '.$data['date'];
            $lines[] = 'Suivi : '.$data['url'];
            break;
        case 'generic_admin':
            $lines[] = 'Un événement a été enregistré dans un espace partenaire. Consultez l’historique pour ses détails.';
            $lines[] = 'Suivi : '.$data['url'];
            break;
        default:
            $lines[] = 'Une mise à jour concerne votre accès documentaire. Contactez le comité si vous avez besoin d’aide.';
    }
    $lines[] = '';
    $lines[] = 'Contact : '.config('partner_portal.contact_email');
    $lines[] = '';
    $lines[] = 'Le Bureau de Pilotage de la JNUAF';
    echo implode(PHP_EOL, $lines).PHP_EOL;
@endphp

@include('mail.partials.footer-text')
