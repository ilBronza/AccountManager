<?php

namespace IlBronza\AccountManager\Models\Traits;

use Carbon\Carbon;

use IlBronza\Addresses\Models\City;

use function in_array;
use function is_numeric;
use function substr;

trait FiscalCodeTrait
{
	static array $months = [
		'A' => 1,
		'B' => 2,
		'C' => 3,
		'D' => 4,
		'E' => 5,
		'H' => 6,
		'L' => 7,
		'M' => 8,
		'P' => 9,
		'R' => 10,
		'S' => 11,
		'T' => 12
	];


	public function getFiscalCode() : ? string
	{
		return $this->fiscal_code;
	}
	public function getSexAttribute() : ? string
	{
		if(! $fiscalCode = $this->getFiscalCode())
			return null;

		return $this->getSexByFiscalCode($fiscalCode);
	}

	public function getBirthDateDayByFiscalCode($fiscalCode) : int
	{
		$day = substr($fiscalCode, 9, 2);

		if($day > 31)
			$day -= 40;

		return $day;
	}

	public function getBirthDateMonthByFiscalCode($fiscalCode) : ? int
	{
		$month = substr($fiscalCode, 8, 1);

		return self::$months[$month] ?? null;
	}

	public function getBirthDateYearByFiscalCode(string $fiscalCode) : ? int
	{
		$year = substr($fiscalCode, 6, 2);

		if(! is_numeric($year))
			return null;

		if($year > date('y'))
			return '19' . $year;

		return $year = '20' . $year;
	}

	public function getBirtyCityByFiscalCode(string $fiscalCode) : ? City
	{
		$cityCode = substr($fiscalCode, 11, 4);

		return City::getProjectClassname()::where('belfiore', $cityCode)->first();
	}

	public function getBirthCity() : ? City
	{
		if(! $fiscalCode = $this->getFiscalCode())
			return null;

		return $this->getBirtyCityByFiscalCode($fiscalCode);
	}

	public function getBirthCityAttribute() : ? string
	{
		if(! $city = $this->getBirthCity())
			return null;

		return $city->name;
	}

	public function getBirthZipAttribute() : ? string
	{
		if(! $city = $this->getBirthCity())
			return null;

		return $city->zip;
	}

	public function getBirthState() : ? string
	{
		return $this->birth_state;
	}

	public function getBirthStateAttribute() : string
	{
		if(! $fiscalCode = $this->getFiscalCode())
			return null;

		return $this->getBirthStateByFiscalCode($fiscalCode);
	}

	public function getBirthProvinceAttribute() : ? string
	{
		if(! $city = $this->getBirthCity())
			return null;

		return $city->province_slug;
	}

	public function getBirthDateAttribute() : ? Carbon
	{
		if(! $fiscalCode = $this->getFiscalCode())
			return null;

		return $this->getBirthDateByFiscalCode($fiscalCode);
	}

	/**
	 * @param  string  $fiscalCode
	 *
	 * @return string
	 */
	public function getSexByFiscalCode(string $fiscalCode) : string
	{
		return substr($fiscalCode, 9, 2) > 31 ? "f" : "m";
	}

	/**
	 * @param  string  $fiscalCode
	 *
	 * @return mixed|string|null
	 */
	public function getBirthStateByFiscalCode(string $fiscalCode) : mixed
	{
		if (! $stateChar = substr($fiscalCode, 11, 1))
			return null;

		if ($stateChar != 'Z')
			return 'Italia';

		if (! $city = $this->getBirthCity())
			return 'Estero';

		return $city->name;
	}

	public function stateIsEuState(string $state) : bool
	{
		return in_array(strtolower($state), $this->getEuStatesArray());
	}

	public function isBornInEu() : ? bool
	{
		if (! $state = $this->getBirthState())
			return null;

		return $this->stateIsEuState($state);
	}

	/**
	 * @param  string  $fiscalCode
	 *
	 * @return Carbon|false
	 */
	public function getBirthDateByFiscalCode(string $fiscalCode) : Carbon|null
	{
		if(! $day = $this->getBirthDateDayByFiscalCode($fiscalCode))
			return null;

		if(! $month = $this->getBirthDateMonthByFiscalCode($fiscalCode))
			return null;

		if(! $year = $this->getBirthDateYearByFiscalCode($fiscalCode))
			return null;

		return Carbon::createFromFormat('d-m-Y', "$day-$month-$year");
	}

	/**
	 * @return string[]
	 */
	public function getEuStatesArray() : array
	{
		return [
			'austria',
			'belgio',
			'bulgaria',
			'cipro',
			'croazia',
			'danimarca',
			'estonia',
			'finlandia',
			'francia',
			'germania',
			'grecia',
			'irlanda',
			'italia',
			'lettonia',
			'lituania',
			'lussemburgo',
			'malta',
			'olanda',
			'polonia',
			'portogallo',
			'repubblica Ceca',
			'romenia',
			'slovacchia',
			'slovenia',
			'spagna',
			'svezia',
			'ungheria'
		];
	}
}