<?php
/**
 * Migrated from App\Admin\Controller (PHPixie) to Laravel 13.
 * Original author: Nikolay Chervyakov, 28.08.2014
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\BaseController;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Base admin controller. All admin controllers extend this.
 * Checks that the authenticated user has the 'admin' role before every action.
 */
class AdminController extends BaseController
{
    public string $root = '/admin';

    /** @var string Config dir for VulnModule — preserved from original */
    protected string $vulnConfigDir = '';

    /** @var User|null */
    protected ?User $user = null;

    /**
     * Shared view data bag (replaces PHPixie's $this->view->xxx = yyy pattern).
     * Controllers fill this array; Blade views receive it via compact/with.
     */
    protected array $viewData = [];

    protected function before(): mixed
    {
        // Redirect to install if DB is not reachable
        try {
            DB::connection()->getPdo();
        } catch (\Exception $e) {
            return redirect('/install');
        }

        /** @var User|null $user */
        $user = Auth::user();
        $this->user = $user;

        // Auth check — only the login action is exempt (handled by the User controller)
        if (!$user) {
            return redirect('/admin/user/login?return_url=' . rawurlencode(request()->getRequestUri()));
        }

        $hasAdmin = $user->roles()->where('name', 'admin')->exists();
        if (!$hasAdmin) {
            session()->flash('error', "You don't have permissions to access this resource.");
            return redirect('/admin/user/login?return_url=' . rawurlencode(request()->getRequestUri()));
        }

        // Resolve vuln config directory (Laravel base_path equivalent)
        $this->vulnConfigDir = base_path('assets/config/vuln');

        // Shared view data
        $this->viewData['adminRoot']    = $this->root;
        $this->viewData['sidebarLinks'] = $this->getSidebarLinks();
        $this->viewData['user']         = $user;
        $this->viewData['pageHeader']   = 'Dashboard';
        $this->viewData['returnUrl']    = '';

        return null;
    }

    /**
     * Returns array of sidebar navigation links (preserved exactly from original).
     */
    public function getSidebarLinks(): array
    {
        return [
            $this->root                      => ['label' => 'Dashboard',               'link_class' => 'fa fa-dashboard fa-fw'],
            $this->root . '/user'            => ['label' => 'Users',                   'link_class' => 'fa fa-user fa-fw'],
            $this->root . '/role'            => ['label' => 'Roles',                   'link_class' => 'fa fa-puzzle-piece fa-fw'],
            $this->root . '/category'        => ['label' => 'Product Categories',      'link_class' => 'fa fa-sitemap fa-fw'],
            $this->root . '/product'         => ['label' => 'Products',                'link_class' => 'fa fa-archive fa-fw'],
            $this->root . '/option'          => ['label' => 'Product Options',         'link_class' => 'fa fa-check-circle-o fa-fw'],
            $this->root . '/order'           => ['label' => 'Orders',                  'link_class' => 'fa fa-shopping-cart fa-fw'],
            $this->root . '/coupon'          => ['label' => 'Coupons',                 'link_class' => 'fa fa-percent fa-fw'],
            $this->root . '/enquiry'         => ['label' => 'Enquiries',               'link_class' => 'fa fa-life-saver fa-fw'],
            $this->root . '/faq'             => ['label' => 'Faq',                     'link_class' => 'fa fa-question-circle fa-fw'],
            $this->root . '/vulnerability'   => ['label' => 'Vulnerability Config',    'link_class' => 'fa fa-question-circle fa-fw'],
        ];
    }

    /**
     * Helper: return a view with shared admin data merged in.
     */
    protected function adminView(string $view, array $data = [])
    {
        return view($view, array_merge($this->viewData, $data));
    }

    /**
     * Emit a JSON response (mirrors PHPixie's jsonResponse()).
     */
    protected function jsonResponse(array $data)
    {
        return response()->json($data);
    }
}
