@extends('uikittemplate::app')

@section('content')
<div class="uk-container uk-container-small uk-margin-large-top">
    <div class="uk-width-medium uk-margin-auto">
        <h1 class="uk-h3">@lang('accountmanager::ldap.title')</h1>
        <form class="uk-form-stacked" method="POST" action="{{ route('accountmanager.ldap.authenticate') }}">
            @csrf
            <div class="uk-margin">
                <label class="uk-form-label" for="username">@lang('accountmanager::ldap.username')</label>
                <input class="uk-input @error('username') uk-form-danger @enderror" id="username" name="username" type="text" value="{{ old('username') }}" required maxlength="255" autocomplete="username" autofocus>
                @error('username') <p class="uk-text-danger" role="alert">{{ $message }}</p> @enderror
            </div>
            <div class="uk-margin">
                <label class="uk-form-label" for="password">@lang('accountmanager::ldap.password')</label>
                <input class="uk-input @error('password') uk-form-danger @enderror" id="password" name="password" type="password" required autocomplete="current-password">
                @error('password') <p class="uk-text-danger" role="alert">{{ $message }}</p> @enderror
            </div>
            <button class="uk-button uk-button-primary uk-width-1-1" type="submit">@lang('accountmanager::ldap.submit')</button>
        </form>
        <p><a href="{{ route('login') }}">@lang('accountmanager::ldap.local_login')</a></p>
    </div>
</div>
@endsection
