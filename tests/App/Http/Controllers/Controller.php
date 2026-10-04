<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

// Minimal host application controller required by IlBronza\CRUD\CRUD.
class Controller extends BaseController
{
	use AuthorizesRequests;
	use ValidatesRequests;
}
