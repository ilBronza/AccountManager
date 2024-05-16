<?php

namespace IlBronza\AccountManager\Models;

use App\Models\User as BaseUser;
use IlBronza\AccountManager\Models\Traits\PackageAccountModelsTrait;
use IlBronza\AccountManager\Models\Traits\UserPermissionsTrait;
use IlBronza\AccountManager\Models\Traits\UserUserdataTrait;
use IlBronza\AccountManager\Models\Userdata;
use IlBronza\Buttons\Button;
use IlBronza\CRUD\Models\Casts\ExtraField;
use IlBronza\CRUD\Models\Scopes\ActiveScope;
use IlBronza\CRUD\Providers\RouterProvider\IbRouter;
use IlBronza\CRUD\Traits\Model\CRUDModelExtraFieldsTrait;
use IlBronza\CRUD\Traits\Model\PackagedModelsTrait;
use IlBronza\Notifications\Traits\ExtendedNotifiable;

class User extends BaseUser
{
	use PackageAccountModelsTrait;

	use CRUDModelExtraFieldsTrait;

	use UserPermissionsTrait;
	use UserUserdataTrait;

	static $packageConfigPrefix = 'accountmanager';
	static $modelConfigPrefix = 'user';

	static $deletingRelationships = [
		'userdata'
	];

	protected $guard_name = 'web';

	public function getExtraFieldsClass() : string
	{
		return Userdata::getProjectClassName();
	}

	protected $casts = [
		'first_name' => ExtraField::class,
		'surname' => ExtraField::class,

		'fiscal_code' => ExtraField::class,
		'birth_date' => ExtraField::class,
		'gender' => ExtraField::class,

		'avatar' => ExtraField::class
	];

	protected static function boot()
	{
		parent::boot();

		static::saving(function ($user)
		{
			if($user->isDirty('active'))
				if($user->active == false)
					if(((\Auth::id())&&($user->getKey() == \Auth::id()))||($user->getKey() == 1))
						abort(403, 'Non puoi disattivare te stesso');

		});
	}

	public function scopeByRoles($query, array $roles = [])
	{
		if(! count($roles))
			return null;
		
		$query->whereHas('roles', function($query) use($roles)
		{
			$query->whereIn('name', $roles);
		});
	}

	public function scopeByRolesIds($query, array $rolesIds = [])
	{
		if(! count($rolesIds))
			return null;
		
		$query->whereHas('roles', function($query) use($rolesIds)
		{
			$query->whereIn('id', $rolesIds);
		});
	}

	public function setPasswordAttribute($value)
	{
		if($value)
			$this->attributes['password'] = $value;
	}

	public function getShortPrivacyName()
	{
		$pieces = explode(" ", $this->name);

		return implode(" ", [
			$pieces[0],
			isset($pieces[1])? substr($pieces[1], 0, 1) : ''
		]);
	}

	public function getEmail() : string
	{
		return $this->email;
	}

	public function getFullName() : string
	{
		if($userdata = $this->getUserdata())
			if($name = trim($userdata->getName()))
				return $name;

		return $this->getName();
	}

	public function getShortName()
	{
		if(! $userdata = $this->getUserdata())
			return $this->getName();
		
		return $userdata->getShortName();
	}

	public function routeNotificationForSlack($notification)
	{
		return 'https://hooks.slack.com/services/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';
	}

	public function getDuplicateUrl()
	{
		return IbRouter::route(app('accountmanager'), 'accountmanager.duplicate', ['user' => $this]);
		return route('accountmanager.duplicate', ['user' => $this]);
	}
}