<?php

namespace App\Auth;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Custom UserProvider that validates passwords using MD5.
 * PHPixie stored passwords as md5($password) — intentionally weak, preserved as-is.
 */
class Md5UserProvider extends EloquentUserProvider
{
    public function validateCredentials(Authenticatable $user, array $credentials): bool
    {
        $plain = $credentials['password'];
        return md5($plain) === $user->getAuthPassword();
    }
}
