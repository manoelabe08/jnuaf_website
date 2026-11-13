<input type="hidden" name="territory_id" value="{{ $item?->territory_id ?? $territoryId }}">
<div class="row">
  <label>Créneau<input name="slot" value="{{ $item?->slot }}" placeholder="Matin, 10h30…" required></label>
  <label>Type<input name="kind" value="{{ $item?->kind }}" placeholder="Atelier, Panel…" required list="kinds"></label>
  <label>Salle<input name="room" value="{{ $item?->room }}"></label>
  <label>Ordre<input type="number" name="position" min="0" value="{{ $item?->position ?? $items->count() }}" required></label>
</div>
<label>Titre<input name="title" value="{{ $item?->title }}" required></label>
<label>Description<textarea name="description">{{ $item?->description }}</textarea></label>
<datalist id="kinds"><option>Accueil</option><option>Plénière</option><option>Panel</option><option>Atelier</option><option>Mentorat</option><option>Réseau</option></datalist>
