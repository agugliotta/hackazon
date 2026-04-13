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
        $plain    = $credentials['password'];
        $stored   = $user->getAuthPassword();

        // Legacy PHPixie format: "md5hash:salt"  →  md5($plain . $salt) == $md5hash
        if (str_contains($stored, ':')) {
            [$hash, $salt] = explode(':', $stored, 2);
            return md5($plain . $salt) === $hash;
        }

        // New registrations store plain md5($password)
        return md5($plain) === $stored;
    }

    /**
     * Prevent Laravel from rehashing the MD5 password to bcrypt after login.
     * The intentionally weak MD5 storage is a preserved vulnerability.
     */
    public function rehashPasswordIfRequired(Authenticatable $user, array $credentials, bool $force = false): void
    {
        // No-op: do not rehash — MD5 weak storage is intentional
    }
}
