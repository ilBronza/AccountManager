<?php

namespace IlBronza\AccountManager\Models\Traits;

use Auth;
use IlBronza\AccountManager\Models\Userdata;

trait UserUserdataTrait
{
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
		return Auth::user()->userdata()->save(
			Userdata::getProjectClassName()::make()
		);
	}

	public function findUserdata() : ? Userdata
	{
		return Userdata::getProjectClassName()::find(
			Auth::id()
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
		if($userdata = session('userdata', null))
			return $userdata;

			// return Userdata::hydrate($parameters);
		if($userdata = $this->findUserdata())
			return $userdata;

		return $this->createUserdata();
	}
}