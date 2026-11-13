<div class="row">
  <label>Code (3 lettres)<input name="code" value="{{ $t?->code }}" maxlength="3" required style="text-transform:uppercase" placeholder="ABJ"></label>
  <label>Ville<input name="city" value="{{ $t?->city }}" required></label>
  <label>Pays<input name="country" value="{{ $t?->country }}" required></label>
</div>
<div class="row">
  <label>Capacité (places)<input type="number" name="capacity" min="1" value="{{ $t?->capacity }}" placeholder="illimitée"></label>
  <label>Ordre d'affichage<input type="number" name="position" min="0" value="{{ $t?->position ?? 0 }}" required></label>
  <label class="check"><input type="checkbox" name="is_active" value="1" @checked($t?->is_active ?? true)> Actif sur le site</label>
</div>
