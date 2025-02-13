@extends('app')

@section('content')

	<div class="login-container uk-flex uk-flex-middle">
		<div class="uk-margin-auto">
			@if(session('errors'))
				<p class="uk-text-danger">{{session('errors')->first('error');}}</p>
			@endif
			<div class="uk-width-medium">

				<img
						class="uk-margin-bottom"
						width="100%"
						src="{{ config('mail.logo.path', config('menu.logo.path')) }}"
						alt="">

				<form class="uk-form uk-form-vertical uk-text-left" method="POST" action="{{ route('login') }}">
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

					<div class="uk-width-1-1">
						<label class="uk-form-label" for="remember_me">
							<input
									id="remember_me"
									type="checkbox"
									class="uk-checkbox"
									name="remember"
							/>
							@lang('accountmanager::auth.rememberMe')
						</label>
					</div>

					<div>
						<button
								type="sumbit"
								class="uk-width-1-1 uk-button uk-button-primary uk-button-medium">
							Login
						</button>
					</div>

					<div class="uk-margin">
						@if (Route::has('password.request'))
							<a
									href="{{ route('password.request') }}"
							>
								@lang('accountmanager::auth.forgottenPassword')
							</a>
						@endif
					</div>


					<div class="uk-form-row uk-text-small">
					</div>
					<div>
						@if (Route::has('password.request'))
							<a
									class="uk-width-1-1"
									href="{{ route('password.request') }}"
							>

							</a>
						@endif
					</div>
				</form>

			</div>
		</div>
	</div>

@endsection