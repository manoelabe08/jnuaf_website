
{!! $body !!}



@if (! empty($attachmentNames))

Documents joints : {{ implode(', ', $attachmentNames) }}



@endif

Cordialement,

L’équipe de la JNUAF

-- 

Journée du Numérique au Féminin · team@jnuaf.org

@include('mail.partials.footer-text')
