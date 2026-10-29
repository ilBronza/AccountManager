<?php

namespace IlBronza\AccountManager\Auth\Ldap;

interface DirectoryReader
{
    /** @return iterable<DirectoryUser> All AD user accounts under the configured Base DN. */
    public function users(): iterable;
}
