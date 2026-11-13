<label>Titre interne
    <input name="title" maxlength="150" required value="{{ old('title', $campaign->title ?? '') }}" placeholder="Ex. Actualités de novembre">
</label>
<label>Objet de l'email
    <input name="subject" maxlength="150" required value="{{ old('subject', $campaign->subject ?? '') }}">
</label>
<label>Contenu
    <textarea name="body" maxlength="30000" rows="14" required>{{ old('body', $campaign->body ?? '') }}</textarea>
</label>
<p class="muted" style="margin:0">Le texte et les retours à la ligne seront intégrés au modèle JNUAF. Le HTML saisi est affiché comme du texte, pas exécuté.</p>
<fieldset style="border:1px solid var(--line);border-radius:10px;padding:14px">
    <legend>Sources des destinataires</legend>
    @foreach ($sources as $value => $label)
        <label class="check"><input type="checkbox" name="sources[]" value="{{ $value }}" @checked(in_array($value, old('sources', $campaign->sources ?? ['manual','users']), true))> {{ $label }}</label>
    @endforeach
    <p class="muted" style="margin-bottom:0">Seuls les abonnés actifs de ces sources seront retenus, une seule fois par adresse. Un compte ou une inscription ne vaut pas abonnement automatique.</p>
</fieldset>
<label>Pièces jointes (facultatif)
    <input type="file" name="files[]" multiple accept=".pdf,.png,.jpg,.jpeg,.docx,.xlsx,.pptx">
</label>
<p class="muted" style="margin:0">PDF, images PNG/JPEG, DOCX, XLSX ou PPTX. 5 fichiers maximum, 5 Mo par fichier et 10 Mo au total. Les nouveaux fichiers s'ajoutent aux pièces existantes.</p>
