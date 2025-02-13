@extends('app')

@section('content')

	<div class="login-container uk-flex uk-flex-middle verify-email">
		<div class="uk-margin-auto">
			<div class="uk-width-medium">

				<img
						class="uk-margin-bottom"
						width="100%"
						src="{{ config('mail.logo.path', config('menu.logo.path')) }}"
						alt="">

				<p>@lang('accountmanager::auth.thanksForSigninEmailConfirmationText')</p>

				@if (session('status') == 'verification-link-sent')
					<div class="uk-alert uk-alert-success">
						@lang('accountmanager::auth.aNewConfirmationLinkHasBeenSent')
					</div>
				@endif


				<form class="uk-form uk-form-vertical uk-text-left" method="POST"
					  action="{{ route('verification.send') }}">
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
