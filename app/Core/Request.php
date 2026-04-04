<?php

namespace App\Core;

/**
 * Compatibility stub replacing PHPixie's App\Core\Request.
 * Extends Laravel's Request; adds legacy method aliases used by
 * VulnModule form builders (getMethods()) and condition checks.
 *
 * Note: VulnModule type hints now use \Illuminate\Http\Request directly;
 * this class remains for code that still imports App\Core\Request explicitly.
 */
class Request extends \Illuminate\Http\Request
{
    /**
     * Alias for ajax() — used by IsAjax VulnModule condition.
     */
    public function is_ajax(): bool
    {
        return $this->ajax();
    }

    /**
     * Returns list of supported HTTP methods — used by VulnModule Method form.
     */
    public static function getMethods(): array
    {
        return ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'HEAD', 'OPTIONS'];
    }
}
