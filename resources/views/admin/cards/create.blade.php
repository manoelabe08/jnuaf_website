
@extends('layouts.admin')

@section('title', 'Émettre une carte')



@section('content')

<div class="top">

  <div>

    <h1>Émettre une carte de membre</h1>

    <p class="muted" style="margin:0">La catégorie, le nom et l'email sont repris automatiquement du compte, de la candidature ou de l'inscription.</p>

  </div>

  <a class="btn ghost" href="{{ route('admin.cards.index') }}">Retour</a>

</div>



@if ($errors->any())

  <div class="card" role="alert" style="border:1px solid #B3261E;margin-bottom:16px">

    @foreach ($errors->all() as $message)

      <div>{{ $message }}</div>

    @endforeach

  </div>

@endif




@if ($errors->any())

  <p class="muted" style="margin:0 0 12px">Après une erreur, le navigateur efface le fichier choisi : sélectionnez de nouveau la photo avant de valider.</p>

@endif


<form class="card form" method="POST" action="{{ route('admin.cards.store') }}" enctype="multipart/form-data">

  @csrf



  <label>Personne

    <select name="person" id="person" required>

      <option value="">— Choisir —</option>

      @foreach (collect($candidates)->groupBy('group') as $group => $items)

        <optgroup label="{{ $group }}">

          @foreach ($items as $c)

            <option value="{{ $c['key'] }}"

                    data-territory="{{ $c['territory_id'] }}"

                    data-role="{{ $c['role'] }}"

                    {{ old('person') === $c['key'] ? 'selected' : '' }}

                    {{ $c['issued'] ? 'disabled' : '' }}>

              {{ $c['label'] }}{{ $c['issued'] ? ' (carte déjà émise)' : '' }}

            </option>

          @endforeach

        </optgroup>

      @endforeach

    </select>

  </label>



  <label>Territoire

    <select name="territory_id" id="territory">

      <option value="">Siège (aucun territoire)</option>

      @foreach ($territories as $t)

        <option value="{{ $t->id }}" {{ (string) old('territory_id') === (string) $t->id ? 'selected' : '' }}>

          {{ $t->city }} ({{ $t->code }}) · {{ $t->country }}

        </option>

      @endforeach

    </select>

  </label>

  <p class="muted" style="font-size:.85rem;margin:0">Il détermine l'indicatif du numéro de membre. Obligatoire sauf pour le Bureau de Pilotage.</p>



  <label>Rôle affiché sur la carte (facultatif)

    <input type="text" name="role_label" id="role" maxlength="120" value="{{ old('role_label') }}">

  </label>



  <label>Photo (JPG ou PNG, 4 Mo maximum)

    <input type="file" name="photo" accept="image/jpeg,image/png">

  </label>

  <p class="muted" style="font-size:.85rem;margin:0">

    La photo est recadrée au format portrait puis stockée dans l'espace privé du serveur, hors du site public.

    N'ajoutez une photo qu'avec l'accord de la personne. Sans photo, ses initiales sont imprimées.

  </p>



  <button class="btn o" type="submit">Émettre la carte</button>

</form>

@endsection



@push('scripts')

<script>

(function () {

  const person = document.getElementById('person');

  const territory = document.getElementById('territory');

  const role = document.getElementById('role');



  person.addEventListener('change', function () {

    const option = person.options[person.selectedIndex];

    if (!option || !option.value) return;

    territory.value = option.dataset.territory || '';

    role.placeholder = option.dataset.role || '';

  });

})();

</script>

@endpush

