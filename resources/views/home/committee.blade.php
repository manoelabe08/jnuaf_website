@php($ce = $errors->committee)
<section id="comite" class="recruit">
  <div class="wrap">
    <button class="poster" type="button" data-lb-src="{{ asset('images/affiche-comite.jpg') }}" data-lb-title="Affiche : rejoignez le comité d'organisation" data-lb-ed="2026-2027" aria-label="Agrandir l'affiche de recrutement">
      <img src="{{ asset('images/affiche-comite.jpg') }}" alt="Affiche : Rejoignez le comité d'organisation de la JNUAF" loading="lazy">
    </button>
    <div class="recruit-body">
      <p class="eyebrow">Recrutement en cours</p>
      <h2>Rejoignez <span class="o">le comité</span> d'organisation</h2>
      <p class="lede">La JNUAF recrute des volontaires en Côte d'Ivoire et en France dans le cadre du plan d'action 2026-2027, pour préparer un événement inspirant, impactant et porteur d'avenir.</p>
      <div class="cond"><p><b>Conditions :</b> un CV et une lettre de motivation. Déposez-les ci-dessous : votre candidature arrive directement au Bureau de Pilotage.</p></div>

      <form class="form-card cand" id="candidature" method="POST" action="{{ route('committee.store') }}" enctype="multipart/form-data" data-validate>
        @csrf
        @if (session('committee'))
          <div class="confirm" role="status">
            <strong>Candidature envoyée, merci !</strong>
            <span>Pôle souhaité : {{ session('committee.pole') }}. Un email de confirmation vient de vous être envoyé ; le Bureau de Pilotage reviendra vers vous.</span>
          </div>
        @elseif ($ce->any())
          <div class="alert" role="alert">Votre candidature n'a pas pu être envoyée. Corrigez les champs signalés.</div>
        @endif

        <div class="hp" aria-hidden="true"><label>Site web <input name="website" tabindex="-1" autocomplete="off"></label></div>
        <div class="row">
          <div @class(['field', 'invalid' => $ce->has('full_name')])><label for="c-nom">Nom et prénom *</label><input id="c-nom" name="full_name" value="{{ old('full_name') }}" autocomplete="name" placeholder="Ex. Aya Kouassi" required minlength="3"><span class="err">{{ $ce->first('full_name') }}</span></div>
          <div @class(['field', 'invalid' => $ce->has('email')])><label for="c-email">Email *</label><input id="c-email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" placeholder="nom@domaine.ci" required><span class="err">{{ $ce->first('email') }}</span></div>
        </div>
        <div class="row">
          <div @class(['field', 'invalid' => $ce->has('phone')])><label for="c-tel">WhatsApp *</label><input id="c-tel" name="phone" type="tel" value="{{ old('phone') }}" placeholder="+225 07 …" required pattern="\+?[0-9 ().\-]{8,20}"><span class="err">{{ $ce->first('phone') }}</span></div>
          <div @class(['field', 'invalid' => $ce->has('country')])><label for="c-pays">Pays *</label>
            <select id="c-pays" name="country">@foreach (config('jnuaf.committee.countries') as $country)<option @selected(old('country') === $country)>{{ $country }}</option>@endforeach</select><span class="err">{{ $ce->first('country') }}</span></div>
        </div>
        <div @class(['field', 'invalid' => $ce->has('pole')])><label for="c-pole">Pôle souhaité *</label>
          <select id="c-pole" name="pole">@foreach (config('jnuaf.committee.poles') as $pole)<option @selected(old('pole') === $pole)>{{ $pole }}</option>@endforeach</select><span class="err">{{ $ce->first('pole') }}</span></div>
        <div class="row">
          <div @class(['field', 'invalid' => $ce->has('cv')])><label for="c-cv">CV (PDF, 2 Mo max) *</label><input id="c-cv" name="cv" type="file" accept="application/pdf" required data-max-kb="{{ config('jnuaf.committee.max_file_kb') }}"><span class="err">{{ $ce->first('cv') }}</span></div>
          <div @class(['field', 'invalid' => $ce->has('letter')])><label for="c-lm">Lettre de motivation (PDF, 2 Mo max) *</label><input id="c-lm" name="letter" type="file" accept="application/pdf" required data-max-kb="{{ config('jnuaf.committee.max_file_kb') }}"><span class="err">{{ $ce->first('letter') }}</span></div>
        </div>
        
<div @class(['field', 'invalid' => $ce->has('photo')])>

          <label for="c-photo">Photo de profil (portrait, JPG, PNG ou WebP, 8 Mo max) *</label>

          <input id="c-photo" name="photo" type="file" accept="image/jpeg,image/png,image/webp" data-max-kb="8192" required>

          <span class="note">Elle servira &agrave; votre carte de membre et &agrave; votre profil si vous &ecirc;tes retenu(e).</span>

          <span class="err">{{ $ce->first('photo') }}</span>

        </div> 
<label class="consent" for="c-photo-public"><input type="checkbox" id="c-photo-public" name="photo_public" value="1" @checked(old('photo_public'))> <span>(Facultatif) J'accepte que ma photo, mon nom et mon p&ocirc;le soient affich&eacute;s publiquement sur la page &Eacute;quipe du site si ma candidature est retenue. Je peux retirer cet accord &agrave; tout moment.</span></label>

        <label class="consent" for="c-consent"><input type="checkbox" id="c-consent" name="consent" value="1" @checked(old('consent')) required> <span>J'accepte que ma candidature soit conservée par le Bureau de Pilotage pendant la durée du recrutement.</span></label>
        @if ($ce->has('consent'))<span class="err" style="color:var(--orange);font-weight:600;font-size:.8rem">{{ $ce->first('consent') }}</span>@endif
        <button class="btn btn-green" type="submit">Envoyer ma candidature</button>
      </form>

      <p class="note-dark">Vous préférez WhatsApp ? Les numéros restent disponibles :</p>
      <div class="contact-strip">
        @foreach (config('jnuaf.contacts') as $key => $c)
          <div class="contact"><small>{{ $c['label'] }}</small><span class="num" id="num-{{ $key }}">{{ $c['phone'] }}</span><button type="button" data-copy="num-{{ $key }}">Copier le numéro</button><a href="https://wa.me/{{ $c['whatsapp'] }}" rel="noopener" target="_blank">Ouvrir WhatsApp ↗</a></div>
        @endforeach
      </div>
      <p class="script" style="font-size:1.6rem">Intéressé(e) ? <span class="o">Faites partie de l'aventure !</span></p>
    </div>
  </div>
</section>
