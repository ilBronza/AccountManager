<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head><meta charset="utf-8"><title>{{ __('accountmanager::auth.logout') }}</title></head>
<body>
    <form method="POST" action="{{ app('accountmanager')->route('accountmanager.logout') }}">
        @csrf
        <button type="submit">{{ __('accountmanager::auth.logout') }}</button>
    </form>
</body>
</html>
