<?php

namespace IlBronza\AccountManager\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\IpUtils;

class AuthenticateWithIp extends Authenticate
{
    public function handle($request, Closure $next, ...$guards)
    {
        $this->authenticate($request, $guards);

        $user = $request->user();
        $ip = $request instanceof Request ? $request->ip() : null;

        if (! $user)
            return $next($request);

        if ($this->isAllowedByUser($user, $ip))
            return $next($request);

        // If user is explicitly NOT allowed from remote, only internal IPs can pass (roles cannot override this).
        if (($user->allow_from_remote ?? false) !== true)
            abort(403, 'IP non autorizzato');

        // User is allowed from remote, but did not match their own allowed list:
        // allow if any role grants access (role list empty => allow, role list present => must match).
        if (method_exists($user, 'roles'))
            foreach ($user->roles as $role)
                if ($this->isAllowedByRole($role, $ip))
                    return $next($request);

        abort(403, 'IP non autorizzato');
    }

    //[{"ip":"146.241.33.114"}]

    protected function isAllowedByUser($user, ?string $ip): bool
    {
        $allowFromRemote = (($user->allow_from_remote ?? false) === true);

        if($this->isInternalIp($ip))
            return true;

        // Not allowed from remote => only internal IPs can pass.
        if (! $allowFromRemote)
            return false;

        $list = $this->parseAllowedIps($user->allowed_ips ?? null);
        if (! count($list))
            return true;

        return $this->ipMatchesList($ip, $list);
    }

    protected function isAllowedByRole($role, ?string $ip): bool
    {
        $allowFromRemote = (($role->allow_from_remote ?? false) === true);

        // Roles only matter if user is allowed from remote (enforced in handle()).
        // If role is NOT allowed from remote => it cannot grant access.
        if (! $allowFromRemote)
            return false;

        $list = $this->parseAllowedIps($role->allowed_ips ?? null);
        if (! count($list))
            return true;

        return $this->ipMatchesList($ip, $list);
    }

    protected function isInternalIp(?string $ip): bool
    {
        if (! $ip)
            return false;

        $internalCidrs = (array) config('accountmanager.ipAccess.internalCidrs', []);

        if (! count($internalCidrs))
            return false;

        return IpUtils::checkIp($ip, $internalCidrs);
    }

    protected function ipMatchesList(?string $ip, array $list): bool
    {
        if (! $ip)
            return false;

        if (! count($list))
            return false;

        return IpUtils::checkIp($ip, $list);
    }

    protected function parseAllowedIps(array $ips): array
    {
        $result = [];

        foreach($ips as $key => $parameters)
            $result[] = $parameters['ip'];

        return $result;

        if (! $text)
            return [];

        if (is_array($text))
            $text = implode("\n", $text);

        if (! is_string($text))
            return [];

        $text = str_replace(["\r\n", "\r"], "\n", $text);

        $rawPieces = array_merge(
            preg_split('/\n+/', $text) ?: [],
            preg_split('/,+/', $text) ?: []
        );

        $pieces = [];
        foreach ($rawPieces as $piece)
        {
            $piece = trim($piece);

            if (! $piece)
                continue;

            // allow inline comments: "1.2.3.4 # office"
            $piece = trim(explode('#', $piece, 2)[0]);

            if ($piece)
                $pieces[] = $piece;
        }

        return array_values(array_unique($pieces));
    }
}

