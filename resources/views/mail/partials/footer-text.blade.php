@foreach (config('jnuaf.contacts', []) as $c)

{!! $c['label'] !!} : {!! $c['phone'] !!}

@endforeach

{!! "Suivez-nous :" !!}

@foreach (config('jnuaf_social.links', []) as $l)

{!! $l['label'] !!} : {!! $l['url'] !!}

@endforeach

