<?php
/**
 * Migrated from App\Admin\Controller\Home (PHPixie) to Laravel 13.
 */

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use VulnModule\Storage\PHPFileReader;
use VulnModule\VulnerabilityMatrixRenderer;

class HomeController extends AdminController
{
    public function index(Request $request)
    {
        $reader = new PHPFileReader($this->vulnConfigDir);
        $matrixRenderer = new VulnerabilityMatrixRenderer($reader);
        $matrix = $matrixRenderer->render();

        return $this->adminView('admin.vulnerability.matrix2', [
            'matrix'     => $matrix['html'],
            'message'    => 'Index page',
            'pageTitle'  => 'Vulnerability Matrix',
            'pageHeader' => 'Dashboard',
        ]);
    }
}
