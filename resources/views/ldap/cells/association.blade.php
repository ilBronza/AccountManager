@if($entry->local_user_id)
    <form method="POST" action="{{ route(config('accountmanager.routePrefix').'ldapUsers.unlink', $entry->id) }}">
        @csrf @method('DELETE')
        <input type="hidden" name="user_id" value="{{ $entry->local_user_id }}">
        <button class="uk-button uk-button-default uk-button-small" type="submit">@lang('accountmanager::directory.unlink')</button>
    </form>
@elseif(! $entry->missing)
    <form method="POST" action="{{ route(config('accountmanager.routePrefix').'ldapUsers.link', $entry->id) }}" class="uk-width-large">
        @csrf
        <label class="uk-hidden-visually" for="ldap-local-user-{{ $entry->id }}">@lang('accountmanager::directory.choose_user')</label>
        <select class="uk-select" id="ldap-local-user-{{ $entry->id }}" name="user_id" required>
            <option value="">@lang('accountmanager::directory.choose_user')</option>
            @foreach($localUsers as $localUser)
                <option value="{{ $localUser->getKey() }}">#{{ $localUser->getKey() }} — {{ $localUser->name }} — {{ $localUser->email }} — {{ $localUser->roles->pluck('name')->implode(', ') ?: __('accountmanager::directory.no_roles') }}</option>
            @endforeach
        </select>
        <button class="uk-button uk-button-primary uk-button-small uk-margin-small-top" type="submit">@lang('accountmanager::directory.link')</button>
    </form>
@endif
