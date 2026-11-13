@php($pe = $errors->partner)
<section id="partenaires" class="recruit">
  <div class="wrap partners-wrap">
    <div class="recruit-body">
      <p class="eyebrow">Devenir partenaire</p>
      <h2>Agissons <span class="o">ensemble</span></h2>
      <p class="lede">Entreprises, écoles, collectivités, institutions et bailleurs : il existe plusieurs façons de soutenir {{ config('jnuaf.organisation.name') }}, ponctuellement ou dans la durée.</p>
      <h3>Ce que vous y gagnez</h3>
      <ul class="benefits">
        @foreach (config('jnuaf.partners.benefits') as $b)<li>{{ $b }}</li>@endforeach
      </ul>
      <p class="note-dark">Nos besoins prioritaires : équipements et logistique pour la Tournée du Numérique et les événements territoriaux, communication, et entreprises prêtes à proposer stages et emplois.</p>
    </div>

    <form class="form-card cand" id="partner-form" method="POST" action="{{ route('partners.store') }}" data-validate>
      @csrf
      @if (session('partner'))
        <div class="confirm" role="status"><strong>Merci, votre proposition est bien transmise.</strong><span>L'équipe Partenariats &amp; Sponsoring reviendra vers {{ session('partner.organization') }} rapidement. Un email de confirmation vient de vous être envoyé.</span></div>
      @elseif ($pe->any())
        <div class="alert" role="alert">Votre demande n'a pas pu être envoyée. Corrigez les champs signalés.</div>
      @endif
      <div class="hp" aria-hidden="true"><label>Site web <input name="website" tabindex="-1" autocomplete="off"></label></div>
      <div class="row">
        <div @class(['field', 'invalid' => $pe->has('organization')])><label for="p-org">Organisation *</label><input id="p-org" name="organization" value="{{ old('organization') }}" autocomplete="organization" required minlength="2"><span class="err">{{ $pe->first('organization') }}</span></div>
        <div @class(['field', 'invalid' => $pe->has('country')])><label for="p-pays">Pays *</label><input id="p-pays" name="country" value="{{ old('country') }}" autocomplete="country-name" required><span class="err">{{ $pe->first('country') }}</span></div>
      </div>
      <div class="row">
        <div @class(['field', 'invalid' => $pe->has('contact_name')])><label for="p-nom">Nom du contact *</label><input id="p-nom" name="contact_name" value="{{ old('contact_name') }}" autocomplete="name" required minlength="3"><span class="err">{{ $pe->first('contact_name') }}</span></div>
        <div @class(['field', 'invalid' => $pe->has('job_title')])><label for="p-fct">Fonction</label><input id="p-fct" name="job_title" value="{{ old('job_title') }}" autocomplete="organization-title"><span class="err">{{ $pe->first('job_title') }}</span></div>
      </div>
      <div class="row">
        <div @class(['field', 'invalid' => $pe->has('email')])><label for="p-email">Email *</label><input id="p-email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required><span class="err">{{ $pe->first('email') }}</span></div>
        <div @class(['field', 'invalid' => $pe->has('phone')])><label for="p-tel">Téléphone</label><input id="p-tel" name="phone" type="tel" value="{{ old('phone') }}" autocomplete="tel" pattern="\+?[0-9 ().\-]{8,20}"><span class="err">{{ $pe->first('phone') }}</span></div>
      </div>
      <fieldset @class(['field', 'types', 'invalid' => $pe->has('types')])>
        <legend class="legend">Forme(s) de partenariat envisagée(s) *</legend>
        @foreach (config('jnuaf.partners.types') as $value => $label)
          <label class="consent"><input type="checkbox" name="types[]" value="{{ $value }}" @checked(in_array($value, old('types', [])))> <span>{{ $label }}</span></label>
        @endforeach
        <span class="err">{{ $pe->first('types') }}</span>
      </fieldset>
      <div @class(['field', 'invalid' => $pe->has('message')])><label for="p-msg">Votre projet *</label><textarea id="p-msg" name="message" required minlength="10" maxlength="3000" placeholder="Objectifs, territoires visés, calendrier…">{{ old('message') }}</textarea><span class="err">{{ $pe->first('message') }}</span></div>
      <label class="consent" for="p-consent"><input type="checkbox" id="p-consent" name="consent" value="1" @checked(old('consent')) required> <span>J'accepte que ces informations soient utilisées par {{ config('jnuaf.organisation.name') }} pour me recontacter au sujet de ce partenariat.</span></label>
      @if ($pe->has('consent'))<span class="err" style="color:var(--orange);font-weight:600;font-size:.8rem">{{ $pe->first('consent') }}</span>@endif
      <button class="btn btn-orange" type="submit">Proposer un partenariat</button>
    </form>
  </div>
</section>
