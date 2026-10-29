<?php

namespace IlBronza\AccountManager\Auth\Ldap;

use InvalidArgumentException;

final class DirectoryIdentity
{
    public readonly string $guid;

    public function __construct(
        public readonly string $connection,
        string $guid,
        public readonly ?string $name = null,
    ) {
        if ($connection === '' || strlen($connection) > 64 ||
            ! preg_match('/^[a-f0-9]{8}-(?:[a-f0-9]{4}-){3}[a-f0-9]{12}$/iD', $guid)) {
            throw new InvalidArgumentException('Invalid LDAP identity.');
        }

        $this->guid = strtolower($guid);
    }
}
