@if($entry->local_user_id)
    @php($linkedUser = $linkedUsers->get((string) $entry->local_user_id))
    @if($linkedUser)
        <strong>{{ $linkedUser->name }}</strong> #{{ $linkedUser->getKey() }}<div class="uk-text-small">{{ $linkedUser->email }}</div>
        <div class="uk-text-small uk-margin-small-top">{{ $linkedUser->roles->pluck('name')->implode(', ') ?: __('accountmanager::directory.no_roles') }}</div>
        <a class="uk-text-small" href="{{ route(config('accountmanager.routePrefix').'users.edit', $linkedUser->getKey()) }}">@lang('accountmanager::directory.edit_user')</a>
    @else
        @lang('accountmanager::directory.unavailable_user') #{{ $entry->local_user_id }}
    @endif
@else
    <span class="uk-text-muted">@lang('accountmanager::directory.unlinked')</span>
@endif
