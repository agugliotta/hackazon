<?php
/**
 * Migrated from App\Admin\EventListeners (PHPixie) to Laravel 13.
 * Original author: Nikolay Chervyakov, 28.08.2014
 *
 * In PHPixie this class attached listeners to the application event dispatcher.
 * In Laravel the equivalent gate is middleware, but to preserve the original
 * structure as a utility class this file is kept as-is.
 * The actual admin auth check is now done in AdminController::before().
 */

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EventListeners
{
    /**
     * Equivalent of the original hasAccessListener.
     * Returns true if the current request should be allowed into the admin area.
     *
     * Called by admin middleware (if registered) or can be used directly.
     * Preserves original logic:
     *  - Non-admin paths pass through.
     *  - /admin/user/login is always allowed (login page).
     *  - Unauthenticated → UnauthorizedException equivalent.
     *  - Authenticated but not admin → ForbiddenException equivalent.
     */
    public static function hasAccess(Request $request): bool
    {
        if (!$request->is('admin/*') && !$request->is('admin')) {
            return true;
        }

        // Always allow the login page
        if ($request->is('admin/user/login')) {
            return true;
        }

        $user = Auth::user();

        if (!$user) {
            return false;
        }

        return $user->roles()->where('name', 'admin')->exists();
    }

    /**
     * Returns the redirect URL for unauthorized/forbidden admin access.
     * Mirrors the original redirectUnauthorized logic.
     */
    public static function getUnauthorizedRedirect(Request $request): string
    {
        return '/admin/user/login?return_url=' . rawurlencode($request->getRequestUri());
    }
}
