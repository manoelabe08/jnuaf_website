
@php

    $links = array_filter(

        config('jnuaf_social.links', []),

        fn ($link) => $link['footer'] ?? true

    );

@endphp



@if (count($links) > 0)

    <nav class="social-links" aria-label="Nos réseaux et communautés">

        <p class="social-title">Suivez-nous</p>



        <ul>

            @foreach ($links as $link)

                <li>

                    <a href="{{ $link['url'] }}"

                       target="_blank"

                       rel="noopener noreferrer"

                       aria-label="{{ $link['label'] }} (s’ouvre dans un nouvel onglet)">

                        {{ $link['label'] }}

                    </a>

                </li>

            @endforeach

        </ul>

    </nav>

@endif

