<div class="row">
  <label>Nom<input name="name" value="{{ $s?->name }}" required></label>
  <label>Session<input name="session" value="{{ $s?->session }}" placeholder="Panel d'ouverture" required></label>
  <label>Territoire<select name="territory_id"><option value="">Tous</option>@foreach ($territories as $t)<option value="{{ $t->id }}" @selected($s?->territory_id === $t->id)>{{ $t->city }}</option>@endforeach</select></label>
  <label>Ordre<input type="number" name="position" min="0" value="{{ $s?->position ?? 0 }}" required></label>
</div>
<label>Biographie (affichée sur le site : les 3 premières lignes sur la carte, le texte complet en cliquant)
  <textarea name="bio" maxlength="1500" rows="5" placeholder="Parcours, fonction actuelle, domaines d'expertise, engagements…">{{ $s?->bio }}</textarea>
</label>
<div class="row">
  <label>Photo (JPG, PNG ou WebP, 8 Mo max ; recadrée au carré automatiquement)<input type="file" name="photo" accept="image/jpeg,image/png,image/webp"></label>
  @if ($s?->photo_path)
    <label class="check"><input type="checkbox" name="remove_photo" value="1"> Retirer la photo actuelle</label>
  @endif
  <label class="check"><input type="checkbox" name="is_published" value="1" @checked($s?->is_published)> Publier sur le site</label>
</div>
