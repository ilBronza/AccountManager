<?php

namespace IlBronza\AccountManager\Models;

use App\Models\User as BaseUser;
use Auth;
use IlBronza\AccountManager\Models\Userdata;
use IlBronza\AccountManager\Traits\AccountManagerUserPermissionsTrait;
use IlBronza\Buttons\Button;
use IlBronza\CRUD\Traits\Model\CRUDModelTrait;
use IlBronza\CRUD\Traits\Model\CRUDRelationshipModelTrait;
use IlBronza\Notifications\Traits\ExtendedNotifiable;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends BaseUser
{
	static $deletingRelationships = [];

    protected $guard_name = 'web';

	use SoftDeletes;

	use AccountManagerUserPermissionsTrait;

	use CRUDModelTrait;
	use CRUDRelationshipModelTrait;

	public function userdata()
	{
		return $this->hasOne(Userdata::class);
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

	public function getAvatarImage()
	{
		if(! $userdata = $this->getUserData())
			return 'https://randomuser.me/api/portraits/men/97.jpg';

		return $userdata->getAvatarImage();
	}

	public function refreshUserdata()
	{
		return Userdata::find(Auth::id());
	}

	public function getUserData()
	{
		if($userdata = session('userdata', null))
			return $userdata;

			// return Userdata::hydrate($parameters);
		if($userdata = Userdata::find(Auth::id()))
			return $userdata;

		Auth::user()->userdata()->save(Userdata::make());

		return $this->getUserData();
	}
	// use ExtendedNotifiable;

	public function routeNotificationForSlack($notification)
    {
        return 'https://hooks.slack.com/services/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';
    }

    public function getDuplicateUrl()
    {
    	return route('accountManager.duplicate', ['user' => $this]);
    }

    static function getDeactivateSelectedButton()
    {
        $button = Button::create([
        	'href' => route('users.activate'),
        	'text' => 'users.activate',
        	'icon' => 'list'
        ]);

        //table id as parameter
        $button->setAjaxTableButton();

        return $button;    	
    }
}