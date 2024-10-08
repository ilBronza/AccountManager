<?php

namespace IlBronza\AccountManager\Models;

use IlBronza\CRUD\Models\BaseModel;
use IlBronza\CRUD\Models\Scopes\ActiveScope;
use IlBronza\CRUD\Traits\Media\InteractsWithMedia;
use IlBronza\CRUD\Traits\Model\CRUDUseUuidTrait;
use IlBronza\CRUD\Traits\Model\PackagedModelsTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Session;
use Ramsey\Uuid\Uuid;
use Spatie\MediaLibrary\HasMedia;

class Userdata extends BaseModel implements HasMedia
{
	use CRUDUseUuidTrait;

	static $packageConfigPrefix = 'accountmanager';
	use PackagedModelsTrait;

	static $modelConfigPrefix = 'userdata';
	static $deletingRelationships = [];
	protected $keyType = 'string';
	protected $primaryKey = 'user_id';

	static function getByFiscalCode(string $fiscalCode) : ?static
	{
		return static::where('fiscal_code', $fiscalCode)->first();
	}

	use InteractsWithMedia;

	static function getByUser($user) : Userdata
	{
		if (class_basename($user) == 'User')
			$user = $user->getKey();

		if ($result = static::where('user_id', $user)->first())
			return $result;

		$userdata = Userdata::getProjectClassName()::make();
		$userdata->user_id = $user;
		$userdata->save();

		return $userdata;
	}

	protected static function boot()
	{
		parent::boot();

		static::retrieved(function ($userdata)
		{
			Session::put('userdata', $userdata);
		});

		static::saved(function ($userdata)
		{
			Session::put('userdata', $userdata);
		});

		static::creating(function (Model $model)
		{
			if (empty($model->id))
			{ // if it's not empty, then we want to use a specific id
				$model->id = (string) Uuid::uuid4();
			}
		});
	}

	public function getTable() : string
	{
		return config('accountmanager.models.userdata.table');
	}

	public function getAvatarDisk()
	{
		return 'public';
	}

	public function getAvatarImage() : ?string
	{
		foreach ($this->media as $media)
			return $media->getFullUrl();

		return 'https://randomuser.me/api/portraits/men/97.jpg';
	}

	public function user()
	{
		return $this->belongsTo(User::getProjectClassName())->withoutGlobalScope(ActiveScope::class);
	}

	public function getShortName(bool $force = false) : ?string
	{
		if (($this->short_name) || ($force))
			return $this->short_name;

		if (($firstName = $this->getFirstName()) && ($surname = $this->getSurname()))
			return ucfirst(substr($firstName, 0, 2)) . ucfirst(substr($surname, 0, 2));

		$pieces = explode(" ", $this->getUser()->getName());

		return ucfirst(substr($pieces[0], 0, 2)) . ucfirst(substr($pieces[1] ?? $pieces[0], 0, 2));
	}

	public function getFirstName() : ?string
	{
		return $this->first_name;
	}

	public function getSurname() : ?string
	{
		return $this->surname;
	}

	public function getName() : ?string
	{
		return "{$this->getFirstName()} {$this->getSurname()}";
	}

	public function getUser() : ?User
	{
		return $this->user;
	}

	//	public function getEditURL(array $data = [])
	//	{
	//		return app('accountmanager')->route('userdatas.edit', ['user' => $this->getUserKey()]);
	//	}

	public function getUserKey() : string
	{
		return $this->user_id;
	}

	public function getIndexUrl(array $data = []) : string
	{
		return app('accountmanager')->route('users.index');
	}
}
