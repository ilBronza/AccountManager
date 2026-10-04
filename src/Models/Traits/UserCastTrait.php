<?php

namespace IlBronza\AccountManager\Models\Traits;

use IlBronza\FormField\Casts\JsonFieldCast;

trait UserCastTrait
{
	public function initializeUserCastTrait()
	{
        $this->makeHidden(['password', 'remember_token', 'github_token', 'github_refresh_token']);
        $this->mergeCasts([
            'allowed_ips' => JsonFieldCast::class
        ]);
	}
}