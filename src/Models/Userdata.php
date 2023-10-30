<?php

namespace IlBronza\AccountManager\Models;

use IlBronza\CRUD\Models\BaseModel;
use IlBronza\CRUD\Traits\Media\InteractsWithMedia;
use IlBronza\CRUD\Traits\Model\CRUDUseUuidTrait;
use IlBronza\CRUD\Traits\Model\PackagedModelsTrait;
use Illuminate\Support\Facades\Session;
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
}
