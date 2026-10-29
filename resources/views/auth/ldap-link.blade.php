@if (config('accountmanager_ldap.enabled', false) && Route::has('accountmanager.ldap.login'))
    <div class="uk-margin">
        <a class="uk-width-1-1 uk-button uk-button-default" href="{{ route('accountmanager.ldap.login') }}">
            @lang('accountmanager::ldap.submit')
        </a>
    </div>
@endif
