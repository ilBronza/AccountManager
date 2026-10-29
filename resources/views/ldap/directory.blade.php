@extends('uikittemplate::app')

@section('content')
<div class="uk-container uk-container-expand uk-margin-top">
    <div class="uk-flex uk-flex-between uk-flex-middle uk-flex-wrap uk-margin-bottom">
        <h1 class="uk-h3 uk-margin-remove">@lang('accountmanager::directory.title')</h1>
        <a class="uk-button uk-button-default uk-button-small" href="{{ route(config('accountmanager.routePrefix').'users.index') }}">@lang('accountmanager::directory.manage_users')</a>
    </div>
    <p>@lang('accountmanager::directory.description')</p>
    @if(session('status')) <div class="uk-alert-success" role="status" uk-alert>{{ session('status') }}</div> @endif
    @if($errors->any())
        <div class="uk-alert-danger" role="alert" uk-alert><ul class="uk-list">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif
    @unless(config('accountmanager_ldap.enabled'))
        <div class="uk-alert-primary" uk-alert>@lang('accountmanager::directory.login_disabled')</div>
    @endunless
    @if(! $ready)
        <div class="uk-alert-warning" role="status" uk-alert>@lang('accountmanager::directory.migration_required')</div>
    @else
        <ul class="uk-subnav uk-subnav-pill">
            @foreach(['unlinked', 'linked', 'all'] as $tab)
                <li class="{{ $status === $tab ? 'uk-active' : '' }}"><a href="{{ route(config('accountmanager.routePrefix').'ldapUsers.index', ['status' => $tab, 'q' => $search]) }}">{{ __('accountmanager::directory.'.$tab) }} ({{ $counts[$tab] }})</a></li>
            @endforeach
        </ul>
        <form method="GET" action="{{ route(config('accountmanager.routePrefix').'ldapUsers.index') }}" class="uk-flex uk-flex-middle uk-margin-bottom">
            <input type="hidden" name="status" value="{{ $status }}">
            <label class="uk-hidden-visually" for="ldap-search">@lang('accountmanager::directory.search')</label>
            <input class="uk-input uk-width-large" type="search" id="ldap-search" name="q" value="{{ $search }}" maxlength="255" placeholder="{{ __('accountmanager::directory.search') }}">
            <button class="uk-button uk-button-default uk-margin-small-left" type="submit">@lang('accountmanager::directory.filter')</button>
        </form>
        <div class="uk-overflow-auto">
        <table class="uk-table uk-table-divider uk-table-middle uk-table-small">
            <thead><tr><th>@lang('accountmanager::directory.directory_user')</th><th>@lang('accountmanager::directory.state')</th><th>@lang('accountmanager::directory.local_user')</th><th>@lang('accountmanager::directory.association')</th></tr></thead>
            <tbody>
            @forelse($entries as $entry)
                <tr>
                    <td>
                        <strong>{{ $entry->name ?: $entry->samaccountname ?: $entry->userprincipalname }}</strong>
                        <div>{{ $entry->samaccountname }}</div>
                        <div class="uk-text-small uk-text-muted">{{ $entry->email ?: $entry->userprincipalname }}</div>
                        <div class="uk-text-small uk-text-muted">@lang('accountmanager::directory.last_seen'): {{ $entry->last_seen_at }}</div>
                    </td>
                    <td>
                        <div><span class="uk-label {{ $entry->enabled ? 'uk-label-success' : 'uk-label-warning' }}">{{ __('accountmanager::directory.'.($entry->enabled ? 'active' : 'disabled')) }}</span></div>
                        @if(config('accountmanager_ldap.required_group_dn'))<div class="uk-text-small uk-margin-small-top">{{ __('accountmanager::directory.'.($entry->in_required_group ? 'in_group' : 'outside_group')) }}</div>@endif
                        @if($entry->missing)<div class="uk-text-danger uk-text-small">@lang('accountmanager::directory.missing')</div>@endif
                    </td>
                    <td>
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
                    </td>
                    <td>
                        @if($entry->local_user_id)
                            <form method="POST" action="{{ route(config('accountmanager.routePrefix').'ldapUsers.unlink', $entry->id) }}">
                                @csrf @method('DELETE')
                                <input type="hidden" name="user_id" value="{{ $entry->local_user_id }}">
                                <button class="uk-button uk-button-default uk-button-small" type="submit">@lang('accountmanager::directory.unlink')</button>
                            </form>
                        @elseif(! $entry->missing)
                            <form method="POST" action="{{ route(config('accountmanager.routePrefix').'ldapUsers.link', $entry->id) }}">
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
                    </td>
                </tr>
            @empty
                <tr><td colspan="4">@lang('accountmanager::directory.empty')</td></tr>
            @endforelse
            </tbody>
        </table>
        </div>
        <div class="uk-flex uk-flex-between uk-flex-middle uk-margin">
            <span>{{ __('accountmanager::directory.results', ['total' => $entries->total()]) }}</span>
            <div>
                @if($entries->previousPageUrl())<a class="uk-button uk-button-default uk-button-small" href="{{ $entries->previousPageUrl() }}">@lang('accountmanager::directory.previous')</a>@endif
                @if($entries->nextPageUrl())<a class="uk-button uk-button-default uk-button-small" href="{{ $entries->nextPageUrl() }}">@lang('accountmanager::directory.next')</a>@endif
            </div>
        </div>
        <p class="uk-text-small uk-text-muted">@lang('accountmanager::directory.policy_note')</p>
    @endif
</div>
@endsection
