<?php

namespace IlBronza\AccountManager\Models;

use IlBronza\AccountManager\Models\User;
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
	use PackagedModelsTrait;

	public function getTable()
	{
		return config('accountmanager.models.userdata.table');
	}

	static $packageConfigPrefix = 'accountmanager';
	static $modelConfigPrefix = 'userdata';
	protected $primaryKey = 'user_id';

	static $deletingRelationships = [];

	use InteractsWithMedia;

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

        static::creating(function (Model $model) {
            if (empty($model->id)) { // if it's not empty, then we want to use a specific id
                $model->id = (string) Uuid::uuid4();
            }
        });
	}

	static function getByFiscalCode(string $fiscalCode) : ? static
	{
		return static::where('fiscal_code', $fiscalCode)->first();
	}

	public function getAvatarDisk()
	{
		return 'public';
	}

	public function getAvatarImage()
	{
		foreach($this->media as $media)
			return $media->getFullUrl();

		return 'https://randomuser.me/api/portraits/men/97.jpg';
	}

	public function user()
	{
		return $this->belongsTo(User::getProjectClassName())->withoutGlobalScope(ActiveScope::class);
	}

	public function getFirstName() : ? string
	{
		return $this->first_name;
	}

	public function getSurname() : ? string
	{
		return $this->surname;
	}

	public function getName() : string
	{
		return "{$this->getFirstName()} {$this->getSurname()}";
	}

	public function getUser() : ? User
	{
		return $this->user;
	}

	static function getByUser($user) : Userdata
	{
		if(class_basename($user) == 'User')
			$user = $user->getKey();

		return static::where('user_id', $user)->first();
	}

	public function getUserKey() : string
	{
		return $this->user_id;
	}

	public function getEditURL(array $data = [])
	{
		return app('accountmanager')->route('userdatas.edit', ['user' => $this->getUserKey()]);		
	}

	public function getIndexUrl(array $data = []) : string
	{
		return app('accountmanager')->route('users.index');
	}
}
