<?php

namespace IlBronza\AccountManager\Helpers;

class IpAddressHelper
{
    public static function isIpOrCidr(mixed $value): bool
    {
        if (! is_string($value)) {
            return false;
        }
        $parts = explode('/', $value);
        if (count($parts) > 2 || ! filter_var($parts[0], FILTER_VALIDATE_IP)) {
            return false;
        }
        if (count($parts) === 1) {
            return true;
        }
        $bits = str_contains($parts[0], ':') ? 128 : 32;

        return ctype_digit($parts[1]) && (int) $parts[1] <= $bits;
    }
}
