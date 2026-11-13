<div class="row">
  <label>Titre<input name="title" value="{{ $p?->title }}" required></label>
  <label>Édition<input name="edition" value="{{ $p?->edition ?? config('jnuaf.edition') }}" required></label>
  <label>Format<select name="size"><option value="">Standard</option><option value="wide" @selected($p?->size === 'wide')>Large (2 colonnes)</option><option value="tall" @selected($p?->size === 'tall')>Haute (2 lignes)</option></select></label>
  <label>Ordre<input type="number" name="position" min="0" value="{{ $p?->position ?? 0 }}" required></label>
</div>
<div class="row">
  <label>Photo (5 Mo max)<input type="file" name="photo" accept="image/*"></label>
  <label class="check"><input type="checkbox" name="is_published" value="1" @checked($p?->is_published ?? true)> Publier</label>
</div>
