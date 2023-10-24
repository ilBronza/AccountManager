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

            <p>@lang('accountmanager::auth.thanksForSigninEmailConfirmationText')</p>

            @if (session('status') == 'verification-link-sent')
            <div class="uk-alert uk-alert-success">
                @lang('accountmanager::auth.aNewConfirmationLinkHasBeenSent')
            </div>
            @endif


            <form class="uk-form uk-form-vertical uk-text-left" method="POST" action="{{ route('verification.send') }}">
                @csrf

                <div>
                    <button
                        type="sumbit"
                        class="uk-width-1-1 uk-button uk-button-primary uk-button-medium">
                        @lang('accountmanager::auth.resendVerificationEmail')
                    </button>
                </div>
            </form>

            <form class="uk-form uk-form-vertical uk-text-left" method="POST" action="{{ route('logout') }}">
                @csrf

                <div>
                    <button
                        type="sumbit"
                        class="uk-width-1-1 uk-button uk-button-primary uk-button-medium">
                        @lang('accountmanager::auth.logout')
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>

@endsection