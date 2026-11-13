<div class="row">
  <label>Nom<input name="name" value="{{ $user?->name }}" required></label>
  <label>Email<input type="email" name="email" value="{{ $user?->email }}" required></label>
</div>
<div class="row">
  <label>Rôle<select name="role">@foreach ($roles as $v => $l)<option value="{{ $v }}" @selected(($user?->role->value ?? 'coordinator') === $v)>{{ $l }}</option>@endforeach</select></label>
  <label>Territoire (coordination)<select name="territory_id"><option value="">—</option>@foreach ($territories as $t)<option value="{{ $t->id }}" @selected($user?->territory_id === $t->id)>{{ $t->city }}</option>@endforeach</select></label>
</div>
