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

            <p>@lang('accountManager::auth.thanksForSigninEmailConfirmationText')</p>

            @if (session('status') == 'verification-link-sent')
            <div class="uk-alert uk-alert-success">
                @lang('accountManager::auth.aNewConfirmationLinkHasBeenSent')
            </div>
            @endif


            <form class="uk-form uk-form-vertical uk-text-left" method="POST" action="{{ route('verification.send') }}">
                @csrf

                <div>
                    <button
                        type="sumbit"
                        class="uk-width-1-1 uk-button uk-button-primary uk-button-medium">
                        @lang('accountManager::auth.resendVerificationEmail')
                    </button>
                </div>
            </form>

            <form class="uk-form uk-form-vertical uk-text-left" method="POST" action="{{ route('logout') }}">
                @csrf

                <div>
                    <button
                        type="sumbit"
                        class="uk-width-1-1 uk-button uk-button-primary uk-button-medium">
                        @lang('accountManager::auth.logout')
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>

@endsection