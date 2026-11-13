<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top:24px;border-top:1px solid #dfe8e1">

<tr><td style="padding:16px 0 4px;font:13px/1.6 Arial,Helvetica,sans-serif;color:#4a5a4f;text-align:center">

@foreach (config('jnuaf.contacts', []) as $c)

{{ $c['label'] }} : <a href="tel:{{ preg_replace('/[^+0-9]/', '', $c['phone']) }}" style="color:#14592A;text-decoration:none">{{ $c['phone'] }}</a>@if (! $loop->last)<br>@endif

@endforeach

</td></tr>

<tr><td style="padding:6px 0 0;font:13px/1.8 Arial,Helvetica,sans-serif;color:#4a5a4f;text-align:center">

<strong style="color:#14592A">Suivez-nous</strong><br>

@foreach (config('jnuaf_social.links', []) as $l)

<a href="{{ $l['url'] }}" style="color:#14592A;text-decoration:underline">{{ $l['label'] }}</a>@if (! $loop->last) &middot; @endif

@endforeach

</td></tr>

</table>

