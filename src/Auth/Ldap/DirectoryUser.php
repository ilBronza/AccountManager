<?php

namespace IlBronza\AccountManager\Auth\Ldap;

final class DirectoryUser
{
    public function __construct(
        public readonly DirectoryIdentity $identity,
        public readonly ?string $samAccountName,
        public readonly ?string $userPrincipalName,
        public readonly ?string $email,
        public readonly ?string $dn,
        public readonly bool $enabled,
        public readonly bool $inRequiredGroup,
    ) {}
}
