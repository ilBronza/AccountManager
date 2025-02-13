@extends('app')

@section('content')

	<div class="login-container uk-flex uk-flex-middle register">
		<div class="uk-margin-auto">
			<div class="uk-width-medium">

				<img
						class="uk-margin-bottom"
						width="100%"
						src="{{ config('mail.logo.path', config('menu.logo.path')) }}"
						alt="">

				<form class="uk-form uk-form-vertical uk-text-left" method="POST" action="{{ route('register') }}">
					@csrf

					<div class="uk-margin">
						<label class="uk-form-label" for="name">@lang('accountmanager::auth.name')</label>
						<div class="uk-form-controls">
							<input
									class="uk-input @error('name') uk-form-danger @enderror"
									name="name"
									id="name"
									value="{{ old('name') }}"
									type="text"
									placeholder="@lang('accountmanager::auth.name')"
							>

							@error('name')
							<span class="uk-text-danger">
                                <strong>{{ $message }}</strong>
                            </span>
							@enderror
						</div>
					</div>

					<div class="uk-margin">
						<label class="uk-form-label" for="email">@lang('accountmanager::auth.email')</label>
						<div class="uk-form-controls">
							<input
									class="uk-input @error('email') uk-form-danger @enderror"
									name="email"
									id="email"
									value="{{ old('email') }}"
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
						<label class="uk-form-label"
							   for="password_confirmation">@lang('accountmanager::auth.passwordConfirmation')</label>
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
							@lang('accountmanager::auth.register')
						</button>
					</div>

					<div class="uk-margin">
						<a href="{{ route('login') }}">
							@lang('accountmanager::auth.alreadyRegisteredGoToLogin')
						</a>
					</div>

				</form>

			</div>
		</div>
	</div>

@endsection