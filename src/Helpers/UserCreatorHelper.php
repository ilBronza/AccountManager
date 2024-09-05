<?php

namespace IlBronza\AccountManager\Helpers;

use IlBronza\AccountManager\Models\User;
use Illuminate\Support\Facades\Hash;

use function implode;
use function request;

class UserCreatorHelper
{
	protected bool $active = false;

	protected string $firstName;
	protected ? string $surname;
	protected ? string $email;
	protected ? string $password;

	protected array $roles = [];
	protected array $permissions = [];


	public function isActive() : bool
	{
		return $this->active;
	}

	public function setActive(bool $active) : void
	{
		$this->active = $active;
	}
	public function getFirstName() : string
	{
		return $this->firstName;
	}

	public function setFirstName(string $firstName) : void
	{
		$this->firstName = $firstName;
	}

	public function getSurname() : ? string
	{
		return $this->surname;
	}

	public function setSurname(string $surname) : void
	{
		$this->surname = $surname;
	}

	public function getEmail() : ? string
	{
		return $this->email;
	}

	public function setEmail(string $email) : void
	{
		$this->email = $email;
	}

	public function provideEmail() : string
	{
		if($value = $this->getEmail())
			return $value;

		return $this->createFakeEmail();
	}

	public function createFakeEmail() : string
	{
		return $this->getCompositeName() . '@' . config('accountmanager.fakeEmailDomain', 'fake' . request()->getHost());
	}

	public function getPassword() :  ? string
	{
		if(isset($this->password))
			return $this->password;

		return null;
	}

	public function setPassword(string $password) : void
	{
		$this->password = $password;
	}

	public function providePassword() : string
	{
		if($value = $this->getPassword())
			return $value;

		return Hash::make(
			$this->getCompositeName()
		);
	}

	public function getRoles() : array
	{
		return $this->roles;
	}

	public function setRoles(array $roles) : void
	{
		$this->roles = $roles;
	}

	public function getPermissions() : array
	{
		return $this->permissions;
	}

	public function setPermissions(array $permissions) : void
	{
		$this->permissions = $permissions;
	}

	public function generateUser()
	{
		$user = User::getProjectClassName()::make();

		$user->email = $this->provideEmail();
		$user->name = $this->getCompositeName();

		$user->password = $this->providePassword();
		$user->active = $this->isActive();

		$user->save();

		$userdata = $user->getUserdata();

		$userdata->first_name = $this->getFirstName();
		$userdata->surname = $this->getSurname();

		$userdata->save();

		return $user;

	}

	static function createBySlimParameters(string $firstName, string $surname, string $email, bool $active = false) : User
	{
		$helper = new static();

		$helper->setFirstName($firstName);
		$helper->setSurname($surname);
		$helper->setEmail($email);

		return $helper->generateUser();
	}

	/**
	 * @param $name
	 * @param $surname
	 *
	 * @return string
	 */
	public function getCompositeName() : string
	{
		$pieces = [];

		if($this->getFirstName())
			$pieces[] = $this->getFirstName();

		if ($this->getSurname())
			$pieces[] = $this->getSurname();

		return implode("_", $pieces);
	}
}
