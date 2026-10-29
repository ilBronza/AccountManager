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
        {!! $table->renderPortion() !!}
        <p class="uk-text-small uk-text-muted">@lang('accountmanager::directory.policy_note')</p>
    @endif
</div>
@endsection
