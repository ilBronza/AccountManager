<strong>{{ $entry->name ?: $entry->samaccountname ?: $entry->userprincipalname }}</strong>
<div>{{ $entry->samaccountname }}</div>
<div class="uk-text-small uk-text-muted">{{ $entry->email ?: $entry->userprincipalname }}</div>
<div class="uk-text-small uk-text-muted">@lang('accountmanager::directory.last_seen'): {{ $entry->last_seen_at }}</div>
