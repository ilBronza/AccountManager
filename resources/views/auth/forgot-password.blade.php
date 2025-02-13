@extends('app')

@section('content')

	<div class="login-container  uk-flex uk-flex-middle forgot-password">
		<div class="uk-margin-auto">
			<div class="uk-width-medium">

				<img
						class="uk-margin-bottom"
						width="100%"
						src="{{ config('mail.logo.path', config('menu.logo.path')) }}"
						alt="">

				<p class="uk-text-small">{{ trans('accountmanager::auth.resetPasswordDescription') }}</p>

				<form class="uk-form uk-form-vertical uk-text-left" method="POST"
					  action="{{ route('password.email') }}">
					@csrf

					<div class="uk-margin">
						<label class="uk-form-label" for="email">@lang('accountmanager::auth.email')</label>
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
							@lang('accountmanager::auth.sendResetPasswordLink')
						</button>
					</div>
				</form>
			</div>
		</div>
	</div>

@endsection