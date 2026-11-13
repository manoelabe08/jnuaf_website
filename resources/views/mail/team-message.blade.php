
<x-mail-layout reply-address="team@jnuaf.org">

    <h1 style="margin:0 0 20px;color:#14592A;font-size:23px;line-height:1.35">

        {{ $subjectLine }}

    </h1>



    <div style="line-height:1.7;overflow-wrap:anywhere">

        {!! nl2br(e($body)) !!}

    </div>



    @if (! empty($attachmentNames))

        <div style="margin:22px 0;padding:14px 16px;background:#E3F1E5;border-radius:10px">

            <p style="margin:0 0 8px;font-weight:bold">

                Documents joints à ce message

            </p>



            <ul style="margin:0;padding-left:20px">

                @foreach ($attachmentNames as $name)

                    <li>{{ $name }}</li>

                @endforeach

            </ul>

        </div>

    @endif



    <p style="margin-top:24px">

        Cordialement,<br>

        L’équipe de la JNUAF

    </p>

</x-mail-layout>

