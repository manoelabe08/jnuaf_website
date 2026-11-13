<div class="field">
  <label>Édition</label>
  <input name="edition" value="{{ $lot['edition'] ?? '2027' }}" required maxlength="20">
</div>
<div class="field">
  <label>Titre attribué aux nouvelles photos</label>
  <input name="title" value="{{ $lot['title'] ?? 'JNUAF Paris' }}" required maxlength="150">
</div>
<div class="field">
  <label>Format</label>
  <select name="size">
    <option value="" @selected(empty($lot['size']))>Standard</option>
    <option value="wide" @selected(($lot['size'] ?? '') === 'wide')>Large</option>
    <option value="tall" @selected(($lot['size'] ?? '') === 'tall')>Haut</option>
  </select>
</div>
<label><input type="checkbox" name="is_published" value="1" @checked($lot['is_published'] ?? false)> Publier les nouvelles photos après import</label>
<label><input type="checkbox" name="auto_sync" value="1" @checked($lot['auto_sync'] ?? false)> Inclure ce lot dans la tâche automatique (cron à configurer sur LWS)</label>
