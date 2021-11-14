@extends('uikittemplate::app')

@section('content')

<div uk-height-viewport>
    <div class="uk-position-center">
        <div class="uk-width-medium">
            
            <img
                class="uk-margin-bottom"
                width="100%"
                src="/img/login.gif"
                alt="">

            <p class="uk-text-small">{{ __('accountManager::auth.resetPasswordDescription') }}</p>

            <form class="uk-form uk-form-vertical uk-text-left" method="POST" action="{{ route('password.email') }}">
                @csrf

                <div class="uk-margin">
                    <label class="uk-form-label" for="email">@lang('accountManager::auth.email')</label>
                    <div class="uk-form-controls">
                        <input
                            class="uk-input @error('email') uk-form-danger @enderror"
                            name="email"
                            id="email"
                            value="{{ old('email') }}"
                            type="text"
                            placeholder="{{ __('Email') }}"
                            >

                        @error('email')
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
                        @lang('accountManager::auth.sendResetPasswordLink')
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection