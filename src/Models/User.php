<?php

namespace IlBronza\AccountManager\Models;

use App\Models\User as BaseUser;
use Auth;
use IlBronza\AccountManager\Models\Traits\PackageAccountModelsTrait;
use IlBronza\AccountManager\Models\Traits\UserCastTrait;
use IlBronza\AccountManager\Models\Traits\UserPermissionsTrait;
use IlBronza\AccountManager\Models\Traits\UserUserdataTrait;
use IlBronza\CRUD\Models\Casts\ExtraField;
use IlBronza\CRUD\Providers\RouterProvider\IbRouter;
use IlBronza\CRUD\Traits\Model\CRUDActiveScopeTrait;
use IlBronza\CRUD\Traits\Model\CRUDCacheTrait;
use IlBronza\CRUD\Traits\Model\CRUDModelExtraFieldsTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use function is_null;
use function trim;
class User extends BaseUser
{
	use HasFactory;
	use PackageAccountModelsTrait;

	use CRUDActiveScopeTrait;
	use CRUDModelExtraFieldsTrait;

	use UserPermissionsTrait;
	use UserUserdataTrait;
	use CRUDCacheTrait;
	use UserCastTrait;

	static $packageConfigPrefix = 'accountmanager';
	static $modelConfigPrefix = 'user';

	static $deletingRelationships = [
		'userdata'
	];

	protected $with = ['userdata'];

	protected $guard_name = 'web';

	protected $casts = [
		'first_name' => ExtraField::class,
		'surname' => ExtraField::class,

		'fiscal_code' => ExtraField::class,
		'birth_date' => ExtraField::class,
		'gender' => ExtraField::class,

		'avatar' => ExtraField::class,

		'allow_from_remote' => 'boolean'
	];


//	public function getExtraFieldsCasts() : array
//	{
//		return [
//			'first_name' => ExtraField::class,
//			'surname' => ExtraField::class,
//
//			'fiscal_code' => ExtraField::class,
//			'birth_date' => ExtraField::class,
//			'gender' => ExtraField::class,
//
//			'avatar' => ExtraField::class
//		];
//	}
//

protected static function boot()
{
	parent::boot();

	static::saving(function ($user)
	{
		if ($user->isDirty('active'))
			if ($user->active == false)
				if (((Auth::id()) && ($user->getKey() == Auth::id())) || ($user->getKey() == 1))
					abort(403, 'Non puoi disattivare te stesso');
	});
}

public static function shouldApplyActiveScope(): bool
{
	if (! Auth::check()) {
		return true;
	}

	$user = Auth::user();

	return ! (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin());
}

/**
 * Restituisce il nome della classe per i campi extra dell'utente.
 *
 * @return string
 */
public function getExtraFieldsClass() : ? string
{
	return Userdata::getProjectClassName();
}

/**
 * Scope per filtrare gli utenti in base ai nomi dei ruoli.
 *
 * @param \Illuminate\Database\Eloquent\Builder $query
 * @param array $roles
 * @return \Illuminate\Database\Eloquent\Builder|null
 */
public function scopeByRoles($query, array $roles = [])
{
	if (! count($roles))
		return null;

	$query->whereHas('roles', function ($query) use ($roles)
	{
		$query->whereIn('name', $roles);
	});
}

/**
 * Scope per filtrare gli utenti in base agli ID dei ruoli.
 *
 * @param \Illuminate\Database\Eloquent\Builder $query
 * @param array $rolesIds
 * @return \Illuminate\Database\Eloquent\Builder|null
 */
public function scopeByRolesIds($query, array $rolesIds = [])
{
	if (! count($rolesIds))
		return null;

	$query->whereHas('roles', function ($query) use ($rolesIds)
	{
		$query->whereIn('id', $rolesIds);
	});
}

public function latestAccessLog(): HasOne
{
	return $this->hasOne(UserAccessLog::class, 'user_id')->latestOfMany('visited_at');
}

public function getLastAccessAtAttribute(): ?\Illuminate\Support\Carbon
{
	return $this->latestAccessLog?->visited_at;
}

public function getLastAccessIpAttribute(): ?string
{
	return $this->latestAccessLog?->ip;
}

/**
 * Imposta la password dell'utente senza modificarla se vuota.
 *
 * @param string|null $value
 * @return void
 */
public function setPasswordAttribute($value)
{
	if ($value)
		$this->attributes['password'] = $value;
}

/**
 * Restituisce una forma breve del nome utente per scopi di privacy.
 *
 * @return string
 */
public function getShortPrivacyName()
{
	$pieces = explode(" ", $this->name);

	return implode(" ", [
		$pieces[0],
		isset($pieces[1]) ? substr($pieces[1], 0, 1) : ''
	]);
}

/**
 * Restituisce email dell'utente.
 *
 * @return string|null
 */
public function getEmail() : ?string
{
	return $this->email;
}

/**
 * Restituisce surname dell'utente.
 *
 * @return string|null
 */
public function getSurname() : ? string
{
	return $this->surname;
}

/**
 * Restituisce first name dell'utente.
 *
 * @return string|null
 */
public function getFirstName() : ? string
{
	return $this->first_name;
}

/**
 * Restituisce il nome completo invertito (es. Cognome Nome).
 *
 * @return string
 */
public function getFullInvertedName() : string
{
	if ($userdata = $this->getUserdata())
		if ($name = trim($userdata->getInvertedName()))
			return $name;

	return $this->getName();
}

/**
 * Restituisce il nome completo dell'utente.
 *
 * @return string
 */
public function getFullName() : string
{
	if ($userdata = $this->getUserdata())
		if ($name = trim($userdata->getName()))
			return $name;

	return $this->getName();
}

/**
 * Restituisce il nome completo in formato firma.
 *
 * @return string
 */
public function getSignatureFullName() : string
{
	if ($userdata = $this->getUserdata())
		if ($name = trim($userdata->getSignatureName()))
			return $name;

	return $this->getName();
}

/**
 * Restituisce una forma abbreviata del nome dell'utente.
 *
 * @return string
 */
public function getShortName()
{
	if (! $userdata = $this->getUserdata())
		return $this->getName();

	return $userdata->getShortName();
}

/**
 * Restituisce l'URL Slack per le notifiche.
 *
 * @param mixed $notification
 * @return string
 */
public function routeNotificationForSlack($notification)
{
	return 'https://hooks.slack.com/services/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';
}

/**
 * Restituisce una stringa con tutti i ruoli dell'utente.
 *
 * @return string
 */
public function getRolesString() : string
{
	return cache()->remember($this->cacheKey('rolesstring'), 3600, function (){
		return $this->roles->pluck('name')->implode(' ');
	});
}

/**
 * Restituisce l'URL per duplicare l'utente.
 *
 * @return string
 */
public function getDuplicateUrl()
{
	return IbRouter::route(app('accountmanager'), 'accountmanager.duplicate', ['user' => $this]);

	return route('accountmanager.duplicate', ['user' => $this]);
}

/**
 * Determina se l'utente passato può aggiornare questo utente.
 *
 * @param User|null $user
 * @return bool
 */
public function userCanUpdate(User $user = null)
{
	if(is_null($user))
		$user = Auth::user();

	if($user->getKey() == $this->getKey())
		return true;

	if(! is_null($result = $this->getBaseUserRightsResult($user)))
		return $result;

	return $this->user_id == $user->getKey();
}


    /**
     * Restituisce la factory per il model User.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public static function newFactory()
    {
        return \IlBronza\AccountManager\Database\Factories\UserFactory::new();
    }


}