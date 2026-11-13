@php
  $profile = old('profile', 'participante');
  $branch = \App\Enums\Profile::tryFrom($profile)?->branch() ?? 'participante';
  $f = fn (string $name) => ['field', 'invalid' => $errors->has($name)];
@endphp
<section id="inscription" class="form-zone">
  <div class="wrap">
    <div class="sec-head">
      <p class="eyebrow">Inscription</p>
      <h2>Réservez <span class="o">votre place</span></h2>
      <p class="lede">Le formulaire change selon le profil choisi. Les champs marqués d'un astérisque sont obligatoires.</p>
      <p class="note">Un email de confirmation avec votre badge vous est envoyé dès l'envoi.</p>
    </div>

    @if (! $registrationsOpen)
      <div class="closed"><h3>Les inscriptions sont fermées.</h3><p>Suivez nos annonces pour la prochaine édition.</p></div>
    @elseif (session('registration'))
      <div class="form-card">
        <div class="confirm" role="status">
          @if (session('registration.waitlisted'))
            <strong>Merci {{ session('registration.first_name') }}, vous êtes sur liste d'attente.</strong>
            <span>Toutes les places de {{ session('registration.city') }} sont prises pour le moment. Dès qu'une place se libère, elle vous est attribuée automatiquement et votre badge arrive par email à <b>{{ session('registration.email') }}</b>.</span>
          @else
            <strong>Inscription confirmée, merci {{ session('registration.first_name') }} !</strong>
            <span>Un email de confirmation vient d'être envoyé à <b>{{ session('registration.email') }}</b> avec votre badge PDF et votre lien personnel pour modifier ou annuler votre inscription.</span>
            <span>Identifiant de badge : <code>{{ session('registration.badge_code') }}</code></span>
          @endif
        </div>
        <a class="btn btn-orange" href="{{ route('home') }}#inscription">Inscrire une autre personne</a>
      </div>
    @else
      <form class="form-card" id="regForm" method="POST" enctype="multipart/form-data" action="{{ route('registration.store') }}" novalidate data-validate>
        @csrf
        @if ($errors->any())
          <div class="alert" role="alert">
            @if ($errors->has('email') && str_contains($errors->first('email'), 'existe déjà'))
              <strong>{{ $errors->first('email') }}</strong>
            @else
              Votre inscription n'a pas pu être envoyée. Corrigez les champs signalés.
            @endif
          </div>
        @endif
        <div class="hp" aria-hidden="true"><label>Site web <input name="website" tabindex="-1" autocomplete="off"></label></div>

        <div class="field">
          <span class="legend">Je m'inscris en tant que *</span>
          <div class="seg" role="radiogroup">
            @foreach ($profiles as $p)
              <input type="radio" name="profile" id="pr-{{ $p->value }}" value="{{ $p->value }}" data-branch="{{ $p->branch() }}" @checked($profile === $p->value)><label for="pr-{{ $p->value }}">{{ $p->label() }}</label>
            @endforeach
          </div>
        </div>
        <div class="row">
          <div @class($f('last_name'))><label for="f-nom">Nom *</label><input id="f-nom" name="last_name" value="{{ old('last_name') }}" autocomplete="family-name" required minlength="2"><span class="err">@error('last_name'){{ $message }}@enderror</span></div>
          <div @class($f('first_name'))><label for="f-prenom">Prénom *</label><input id="f-prenom" name="first_name" value="{{ old('first_name') }}" autocomplete="given-name" required minlength="2"><span class="err">@error('first_name'){{ $message }}@enderror</span></div>
        </div>
        <div class="row">
          <div @class($f('email'))><label for="f-email">Email *</label><input id="f-email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" placeholder="nom@domaine.ci" required><span class="err">@error('email'){{ $message }}@enderror</span></div>
          <div @class($f('phone'))><label for="f-tel">Téléphone *</label><input id="f-tel" name="phone" type="tel" value="{{ old('phone') }}" autocomplete="tel" placeholder="+225 07 …" required pattern="\+?[0-9 ().\-]{8,20}"><span class="err">@error('phone'){{ $message }}@enderror</span></div>
        </div>
        <div class="row">
          <div @class($f('territory_id'))><label for="f-terr">Territoire *</label>
            <select id="f-terr" name="territory_id" required>
              @foreach ($territories as $t)<option value="{{ $t->id }}" @selected(old('territory_id') !== null ? (int) old('territory_id') === $t->id : strtoupper((string) request('territoire')) === $t->code)>{{ $t->city }} ({{ $t->country }}){{ in_array($t->id, $fullTerritoryIds) ? ' · complet, liste d\'attente' : '' }}</option>@endforeach
            </select><span class="err">@error('territory_id'){{ $message }}@enderror</span></div>
          <div @class($f('city'))><label for="f-ville">Ville *</label><input id="f-ville" name="city" value="{{ old('city') }}" required minlength="2"><span class="err">@error('city'){{ $message }}@enderror</span></div>
        </div>

        <div class="branch" data-branch="participante" @if ($branch !== 'participante') hidden @endif>
          <span class="legend">Participante</span>
          <div @class($f('organization'))><label for="f-univ">Université / structure</label><input id="f-univ" name="organization" value="{{ old('organization') }}"><span class="err">@error('organization'){{ $message }}@enderror</span></div>
          <div @class($f('motivation'))><label for="f-motiv">Motivation et attentes</label><textarea id="f-motiv" name="motivation" maxlength="1500">{{ old('motivation') }}</textarea><span class="err">@error('motivation'){{ $message }}@enderror</span></div>
        </div>
        <div class="branch" data-branch="benevole" @if ($branch !== 'benevole') hidden @endif>
          <span class="legend">Bénévole</span>
          <div @class($f('skills'))><label for="f-comp">Compétences</label><input id="f-comp" name="skills" value="{{ old('skills') }}" placeholder="Accueil, logistique, communication…"><span class="err">@error('skills'){{ $message }}@enderror</span></div>
          <div @class($f('availability'))><label for="f-dispo">Disponibilités</label><input id="f-dispo" name="availability" value="{{ old('availability') }}" placeholder="Jours et créneaux"><span class="err">@error('availability'){{ $message }}@enderror</span></div>
          <div @class($f('experience'))><label for="f-exp">Expérience</label><textarea id="f-exp" name="experience" maxlength="1500" placeholder="Événements auxquels vous avez déjà contribué">{{ old('experience') }}</textarea><span class="err">@error('experience'){{ $message }}@enderror</span></div>
        </div>
        <div class="branch" data-branch="speaker" @if ($branch !== 'speaker') hidden @endif>
          <span class="legend">Mentor / Intervenante</span>
          <div @class($f('expertise'))><label for="f-dom">Domaine d'expertise *</label><input id="f-dom" name="expertise" value="{{ old('expertise') }}" placeholder="Ex. cybersécurité, data, développement web"><span class="err">@error('expertise'){{ $message }}@enderror</span></div>
          <div @class($f('bio'))><label for="f-bio">Biographie courte *</label><textarea id="f-bio" name="bio" maxlength="1500" placeholder="3 à 5 lignes">{{ old('bio') }}</textarea><span class="err">@error('bio'){{ $message }}@enderror</span></div>
          <div @class($f('topic'))><label for="f-theme">Thématique proposée <span data-only="intervenante">*</span></label><input id="f-theme" name="topic" value="{{ old('topic') }}" placeholder="Titre de la session"><span class="err">@error('topic'){{ $message }}@enderror</span></div>

          <div @class($f('photo'))><label for="f-photo">Photo (portrait, JPG, PNG ou WebP, 8 Mo max, facultatif)</label><input id="f-photo" name="photo" type="file" accept="image/jpeg,image/png,image/webp"><span class="note">Utilis&eacute;e uniquement par l&#39;organisation pour pr&eacute;parer votre pr&eacute;sentation et votre badge.</span><span class="err">@error('photo'){{ $message }}@enderror</span></div>

          <div @class($f('cv'))><label for="f-cv">CV (PDF, 4 Mo max, facultatif)</label><input id="f-cv" name="cv" type="file" accept="application/pdf"><span class="err">@error('cv'){{ $message }}@enderror</span></div>
        </div>

        <div @class($f('accessibility_needs'))><label for="f-access">Besoins spécifiques (accessibilité, facultatif)</label><input id="f-access" name="accessibility_needs" value="{{ old('accessibility_needs') }}" placeholder="Ex. accès en fauteuil, interprète LSF…"></div>
        <label class="consent" for="f-consent"><input type="checkbox" id="f-consent" name="consent_data" value="1" @checked(old('consent_data')) required> <span>J'accepte que mes données soient utilisées pour l'organisation de la JNUAF {{ config('jnuaf.edition') }} (<a href="#confidentialite">politique de confidentialité</a>). Je peux demander leur consultation ou leur suppression à tout moment. *</span></label>
        @error('consent_data')<span class="err" style="color:var(--orange);font-weight:600;font-size:.8rem">{{ $message }}</span>@enderror
        <label class="consent" for="f-image"><input type="checkbox" id="f-image" name="consent_image" value="1" @checked(old('consent_image'))> <span>J'autorise la JNUAF à publier des photos et vidéos sur lesquelles j'apparais (site, galerie, réseaux sociaux). Facultatif.</span></label>
        <button class="btn btn-orange" type="submit">Envoyer mon inscription</button>
      </form>
    @endif
  </div>
</section>
