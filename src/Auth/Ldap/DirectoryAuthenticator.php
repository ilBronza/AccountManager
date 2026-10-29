<?php

namespace IlBronza\AccountManager\Auth\Ldap;

interface DirectoryAuthenticator
{
    public function authenticate(string $username, string $password): ?DirectoryIdentity;

    // Service-account lookup, used only by the explicit administrator CLI command.
    public function find(string $username): ?DirectoryIdentity;
}
