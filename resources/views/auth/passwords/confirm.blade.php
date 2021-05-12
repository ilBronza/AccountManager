@extends('uikittemplate::app')

@section('content')

<div uk-height-viewport>
    <div class="uk-position-center">
        <div class="uk-width-medium">
            
            <img
                class="uk-margin-bottom"
                width="100%"
                src="https://www.idealpacksrl.it/img/logo_idealpack_topmenu.png"
                alt="">

            <p class="uk-text-small">{{ __('accountManager::auth.confirmPasswordText') }}</p>

            <form class="uk-form uk-form-vertical uk-text-left" method="POST" action="{{ route('password.confirm') }}">
                @csrf

                <div class="uk-margin">
                    <label class="uk-form-label" for="password">@lang('accountManager::auth.password')</label>
                    <div class="uk-form-controls">
                        <input
                            class="uk-input @error('password') uk-form-danger @enderror"
                            type="password"
                            id="password"
                            name="password"
                            required autocomplete="current-password"
                            placeholder="{{ __('Password') }}"
                            >
                        @error('password')
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
                        @lang('accountManager::auth.confirm')
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>

@endsection