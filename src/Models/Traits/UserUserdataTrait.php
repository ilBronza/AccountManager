<?php

namespace IlBronza\AccountManager\Models\Traits;

use Auth;
use IlBronza\AccountManager\Models\Userdata;

trait UserUserdataTrait
{
	public function getEditUserdataUrl() : string
	{
		return app('accountmanager')->route('userdatas.edit', [
			'user' => $this->getKey()
		]);
	}

	public function userdata()
	{
		return $this->hasOne(Userdata::getProjectClassName());
	}

	public function getAvatarImage() : string
	{
		if(! $userdata = $this->getUserData())
			return 'https://randomuser.me/api/portraits/men/97.jpg';

		return $userdata->getAvatarImage();
	}

	public function createUserdata() : Userdata
	{
		return cache()->remember(
			$this->cacheKey('userdata'),
			3600,
			function()
			{
				return Auth::user()->userdata()->save(
					Userdata::getProjectClassName()::make()
				);
			}
		);
	}

	public function findUserdata() : ? Userdata
	{
		return cache()->remember(
			$this->cacheKey('userdata'),
			3600,
			function()
			{
				return Userdata::getProjectClassName()::find(
					$this->getKey()
				);
			}
		);
	}

	public function refreshUserdata() : Userdata
	{
		if($userdata = Userdata::getProjectClassName()::find(Auth::id()))
			return $userdata;

		return $this->createUserdata();
	}

	public function getUserData() : Userdata
	{
		if($this->relationLoaded('userdata'))
			if($this->userdata)
				return $this->userdata;

		if($userdata = cache('userdata' . $this->getKey(), null))
			return $userdata;

			// return Userdata::hydrate($parameters);
		if($userdata = $this->findUserdata())
			return $userdata;

		return $this->createUserdata();
	}
}