<?php

namespace IlBronza\AccountManager\Models;

use App\Models\User as BaseUser;
use IlBronza\AccountManager\Models\Traits\UserPermissionsTrait;
use IlBronza\AccountManager\Models\Traits\UserUserdataTrait;
use IlBronza\AccountManager\Models\Userdata;
use IlBronza\Buttons\Button;
use IlBronza\CRUD\Models\Casts\ExtraField;
use IlBronza\CRUD\Models\Scopes\ActiveScope;
use IlBronza\CRUD\Providers\RouterProvider\IbRouter;
use IlBronza\CRUD\Traits\Model\CRUDModelExtraFieldsTrait;
use IlBronza\CRUD\Traits\Model\CRUDModelTrait;
use IlBronza\CRUD\Traits\Model\CRUDRelationshipModelTrait;
use IlBronza\CRUD\Traits\Model\PackagedModelsTrait;
use IlBronza\Notifications\Traits\ExtendedNotifiable;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends BaseUser
{
	use PackagedModelsTrait {
		PackagedModelsTrait::getRouteBaseNamePrefix insteadof CRUDModelTrait;
	}

	use SoftDeletes;

	use CRUDModelTrait;
	use CRUDRelationshipModelTrait;



	use UserPermissionsTrait;
	use UserUserdataTrait;



	static $packageConfigPrefix = 'accountmanager';
	static $modelConfigPrefix = 'user';

	static $deletingRelationships = [
		'userdata'
	];

	protected $guard_name = 'web';



	use CRUDModelExtraFieldsTrait;




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
					if($user->getKey() == \Auth::id())
						abort(403, 'Non puoi disattivare te stesso');

		});
	}

	protected static function booted()
	{
		static::addGlobalScope(new ActiveScope);
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
			return $userdata->getName();

		return $this->getName();
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