<?php

namespace IlBronza\AccountManager\Models\Traits;

use IlBronza\FormField\Casts\JsonFieldCast;

trait UserCastTrait
{
	public function initializeUserCastTrait()
	{
        $this->mergeCasts([
            'allowed_ips' => JsonFieldCast::class
        ]);
	}
}