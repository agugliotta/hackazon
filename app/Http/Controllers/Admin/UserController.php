<?php
/**
 * Migrated from App\Admin\Controller\User (PHPixie) to Laravel 13.
 */

namespace App\Http\Controllers\Admin;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends CRUDController
{
    public string $modelNamePlural = 'Users';
    public string $modelName       = 'User';

    // ─── Admin login / logout ──────────────────────────────────────────────────

    public function login(Request $request)
    {
        // Skip the normal before() auth check for login
        try {
            \Illuminate\Support\Facades\DB::connection()->getPdo();
        } catch (\Exception $e) {
            return redirect('/install');
        }

        $returnUrl = $request->query('return_url', '');

        /** @var User|null $currentUser */
        $currentUser = Auth::user();
        if ($currentUser && $currentUser->roles()->where('name', 'admin')->exists()) {
            return redirect('/admin/');
        }

        $errorMessage = '';

        if ($request->isMethod('POST')) {
            $userModel = new User();
            $login     = $userModel->checkLoginUser($request->input('username', ''));
            $password  = $request->input('password', '');

            /** @var User|null $user */
            $user = $userModel->loadUserModel($login);

            if ($user && $user->active) {
                // Validate MD5 password — supports both plain md5 and legacy hash:salt format
                $stored = $user->password;
                $valid  = str_contains($stored, ':')
                    ? (function() use ($stored, $password) {
                        [$hash, $salt] = explode(':', $stored, 2);
                        return md5($password . $salt) === $hash;
                      })()
                    : $stored === md5($password);
                if ($valid) {
                    Auth::login($user);

                    if ($user->roles()->where('name', 'admin')->exists()) {
                        $user->last_login = date('Y-m-d H:i:s');
                        $user->save();

                        $target = $returnUrl ?: '/admin/';
                        return redirect($target);
                    }

                    Auth::logout();
                    session()->flash('error', "You don't have enough permissions to access admin area.");
                    $redir = '/admin/user/login' . ($returnUrl ? '?return_url=' . rawurlencode($returnUrl) : '');
                    return redirect($redir);
                }
            }

            // XSS: username echoed back without escaping — intentional (mirrors original)
            $this->viewData['username']     = $request->input('username');
            $this->viewData['errorMessage'] = 'Username or password are incorrect.';
        } else {
            $this->viewData['errorMessage'] = session()->pull('error', '');
        }

        $this->viewData['returnUrl']  = $returnUrl;
        $this->viewData['pageHeader'] = 'Login';
        $this->viewData['pageTitle']  = 'Admin Login';

        return view('admin.user.login', $this->viewData);
    }

    public function logout()
    {
        Auth::logout();
        return redirect('/admin/user/login');
    }

    // ─── List fields ───────────────────────────────────────────────────────────

    protected function getListFields(): array
    {
        return array_merge(
            $this->getIdCheckboxProp(),
            [
                'id',
                'username'       => ['max_length' => 30, 'type' => 'link'],
                'first_name'     => ['max_length' => 30],
                'last_name'      => ['max_length' => 30],
                'email',
                'oauth_provider',
                'created_on',
                'last_login',
                'photo' => [
                    'type'       => 'image',
                    'max_width'  => 40,
                    'max_height' => 30,
                    'dir_path'   => '/user_pictures/',
                    'is_link'    => true,
                ],
            ],
            $this->getEditLinkProp(),
            $this->getDeleteLinkProp()
        );
    }

    protected function getEditFields(): array
    {
        return [
            'id'             => [],
            'username'       => ['required' => true, 'max_length' => 64],
            'first_name',
            'last_name',
            'email'          => ['required' => true, 'data_type' => 'email'],
            'active'         => ['type' => 'boolean'],
            'user_phone',
            'oauth_provider',
            'oauth_uid',
            'rest_token',
            'photo'          => ['type' => 'image'],
            'created_on'     => ['data_type' => 'date'],
            'last_login',
        ];
    }

    // ─── Static helpers used by other admin controllers ────────────────────────

    /**
     * Returns [id => 'username (full name)'] map for select fields.
     * IDOR intentional — no ownership filter.
     */
    public static function getAvailableUsers(array $options = []): array
    {
        $results = ['' => '—'];
        foreach (User::orderBy('username', 'asc')->get() as $user) {
            $addons = trim(implode(' ', [$user->first_name ?? '', $user->last_name ?? '']));
            $results[$user->id] = $user->username . ($addons ? ' (' . $addons . ')' : '');
        }
        return $results;
    }

    /**
     * Returns [id => name] map for all roles.
     */
    public static function getRoleOptions(): array
    {
        $results = [];
        foreach (Role::orderBy('name', 'asc')->get() as $role) {
            $results[$role->id] = $role->name;
        }
        return $results;
    }

    /**
     * Returns the current roles of a user as [id => name].
     */
    public function getUserRolesOptions(User $user): array
    {
        $result = [];
        foreach ($user->roles as $role) {
            $result[$role->id] = $role->name;
        }
        return $result;
    }
}
