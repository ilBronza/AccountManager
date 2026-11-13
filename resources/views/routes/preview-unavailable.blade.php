<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <title>@lang('accountmanager::route-access.preview')</title>
</head>
<body>
    <p>{{ __('accountmanager::route-access.no_example', ['route' => $route->name ?? $route->uri]) }}</p>
</body>
</html>
