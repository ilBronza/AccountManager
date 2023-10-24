@extends('app')

@section('content')

<div uk-height-viewport>
    <div class="uk-position-center">
        <div class="uk-width-medium">
            
            <img
                class="uk-margin-bottom"
                width="100%"
                src="/img/login.gif"
                alt="">

            <form class="uk-form uk-form-vertical uk-text-left" method="POST" action="{{ route('password.update') }}">
                @csrf

                <input type="hidden" name="token" value="{{ $request->route('token') }}">

                <div class="uk-margin">
                    <label class="uk-form-label" for="email">@lang('accountmanager::auth.email')</label>
                    <div class="uk-form-controls">
                        <input
                            class="uk-input @error('email') uk-form-danger @enderror"
                            name="email"
                            id="email"
                            value="{{ old('email', $request->email) }}"
                            type="text"
                            placeholder="@lang('accountmanager::auth.email')"
                            >

                        @error('email')
                            <span class="uk-text-danger">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                </div>

                <div class="uk-margin">
                    <label class="uk-form-label" for="password">@lang('accountmanager::auth.password')</label>
                    <div class="uk-form-controls">
                        <input
                            class="uk-input @error('password') uk-form-danger @enderror"
                            type="password"
                            id="password"
                            name="password"
                            required autocomplete="current-password"
                            placeholder="@lang('accountmanager::auth.password')"
                            >
                        @error('password')
                            <span class="uk-text-danger">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                </div>

                <div class="uk-margin">
                    <label class="uk-form-label" for="password_confirmation">@lang('accountmanager::auth.passwordConfirmation')</label>
                    <div class="uk-form-controls">
                        <input
                            class="uk-input @error('password_confirmation') uk-form-danger @enderror"
                            type="password"
                            id="password_confirmation"
                            name="password_confirmation"
                            required autocomplete="current-password"
                            placeholder="@lang('accountmanager::auth.passwordConfirmation')"
                            >
                        @error('password_confirmation')
                            <span class="uk-text-danger">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                </div>

                <div>
                    <button
                        type="sumbit"
                        class="uk-width-1-1 uk-button uk-button-primary uk-button-medium">
                        @lang('accountmanager::auth.resetPassword')
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection