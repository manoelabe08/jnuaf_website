
<x-mail-layout>

    <h1 style="margin:0 0 20px;color:#14592A;font-size:21px;line-height:1.35">

        Audit : {{ $action }}

    </h1>



    <p style="margin:0 0 14px">

        <b>Réalisé par :</b> {{ $actor?->name ?? 'Inconnu' }} ({{ $actor?->email ?? '-' }})<br>

        <b>Date :</b> {{ $when }}<br>

        <b>Adresse IP :</b> {{ $ip ?? '-' }}

    </p>



    <div style="margin:18px 0;padding:14px 16px;background:#E3F1E5;border-radius:10px">

        @foreach($details as $label => $value)

        <p style="margin:0 0 4px"><b>{{ $label }} :</b> {{ $value }}</p>

        @endforeach

    </div>



    <p style="margin:0;color:#555;font-size:13px">

        Message de suivi automatique. Il ne contient aucun mot de passe.

    </p>

</x-mail-layout>

