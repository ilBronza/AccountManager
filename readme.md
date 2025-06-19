# AccountManager

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Total Downloads][ico-downloads]][link-downloads]
[![Build Status][ico-travis]][link-travis]
[![StyleCI][ico-styleci]][link-styleci]

This is where your description should go. Take a look at [contributing.md](contributing.md) to see a to do list.

## Installation


Before install CRUD Package, (https://packagist.org/packages/ilbronza/crud)
following the readme instructions


Before install activity log 
(https://spatie.be/docs/laravel-activitylog/v4/introduction)
USE php artisan vendor:publish to publish activity-log migrations and config. Installation is bugged

Install laravel breeze

Via Composer

This package also requires the Laravel UI scaffolding package. Install it first with:

```bash
composer require laravel/ui
```

``` bash

composer require ilbronza/accountmanager

$ php artisan vendor:publish --tag=accountmanager.migrations
$ php artisan migrate
$ php artisan vendor:publish --tag=accountmanager.views --force
$ php artisan vendor:publish --tag=accountmanager.authControllers

```

Add the default Laravel authentication routes in your **routes/web.php** file:

```php
Auth::routes();
```

Edit config.permissions

``` bash
'permission' => IlBronza\AccountManager\Models\Permission::class,
'role' => IlBronza\AccountManager\Models\Role::class,
```

Edit config.auth

``` bash
'users' => [
    'driver' => 'eloquent',
    'model' => IlBronza\AccountManager\Models\User::class,
],
```




Edit App\Models\User, add AccountManagerUserPermissionsTrait 
``` bash
    use AccountManagerUserPermissionsTrait;
```

Edit bootstrap/app.php

```php
$middleware->alias([
    'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
    'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
    'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
]);
$middleware->append(StartSession::class);
$middleware->append(IframeCheckerMiddleware::class);
//
```


## Usage

## Change log

Please see the [changelog](changelog.md) for more information on what has changed recently.

## Testing

``` bash
$ composer test
```

## Contributing

Please see [contributing.md](contributing.md) for details and a todolist.

## Security

If you discover any security related issues, please email author email instead of using the issue tracker.

## Credits

- [author name][link-author]
- [All Contributors][link-contributors]

## License

license. Please see the [license file](license.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/ilbronza/accountmanager.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/ilbronza/accountmanager.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/ilbronza/accountmanager/master.svg?style=flat-square
[ico-styleci]: https://styleci.io/repos/12345678/shield

[link-packagist]: https://packagist.org/packages/ilbronza/accountmanager
[link-downloads]: https://packagist.org/packages/ilbronza/accountmanager
[link-travis]: https://travis-ci.org/ilbronza/accountmanager
[link-styleci]: https://styleci.io/repos/12345678
[link-author]: https://github.com/ilbronza
[link-contributors]: ../../contributors
