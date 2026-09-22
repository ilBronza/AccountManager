@extends('uikittemplate::app')

@section('content')

    <div class="login-container uk-flex uk-flex-middle confirm-password">
        <div class="uk-margin-auto">
            <div class="uk-width-medium uk-text-center">

                <img
                        class="uk-margin-bottom"
                        width="100%"
                        src="{{ config('mail.logo.path', config('menu.logo.path')) }}"
                        alt="">

                <p>@lang('accountmanager::auth.confirmPasswordText')</p>

                <form class="uk-form uk-form-vertical uk-text-left" method="POST"
                      action="{{ route('password.confirm') }}">
                    @csrf

                    <div class="uk-margin">
                        <label class="uk-form-label" for="password">@lang('accountmanager::auth.password')</label>
                        <div class="uk-form-controls">
                            <input
                                    class="uk-input @error('password') uk-form-danger @enderror"
                                    type="password"
                                    id="password"
                                    name="password"
                                    required
                                    autocomplete="current-password"
                                    placeholder="@lang('accountmanager::auth.password')"
                            >

                            @error('password')
                            <span class="uk-text-danger">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <button type="submit" class="uk-width-1-1 uk-button uk-button-primary uk-button-medium">
                            @lang('accountmanager::auth.passwordConfirmation')
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection
