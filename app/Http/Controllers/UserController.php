<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use VulnModule\Config\Annotations as Vuln;

/**
 * Class UserController
 * @package App\Http\Controllers
 *
 * VULNERABILITY NOTES:
 * - action_login: reflected XSS via $username echoed back into view without escaping
 * - action_register: stored XSS via username/email stored without sanitization
 * - action_recover / action_newpassw: no CSRF check (intentional)
 * - checkLoginUser() / loadUserModel() pass raw input to DB — SQLi when vuln enabled
 */
class UserController extends PageController
{
    /**
     * @Vuln\Description("View: user/login.")
     */
    public function login(Request $request)
    {
        if (!is_null(Auth::user())) {
            return redirect('/account');
        }

        // $returnUrl taken from query — no sanitization (open redirect + XSS preserved)
        $returnUrl = $request->query('return_url', '');

        $errorMessage = '';
        $username     = '';

        if ($request->isMethod('POST')) {
            // checkLoginUser passes raw input through VulnService (SQLi when vuln enabled)
            $loginRaw = $request->input('username');
            $login    = User::checkLoginUser($loginRaw);
            $password = $request->input('password');

            $user = User::loadUserModel($login);

            if ($user && $user->active) {
                $logged = Auth::attempt(['username' => $login, 'password' => $password]);

                if ($logged) {
                    $user->last_login = date('Y-m-d H:i:s');
                    $user->save();

                    if ($returnUrl) {
                        // XSS: returnUrl is echoed into redirect — no escaping (reflected XSS preserved)
                        return redirect($returnUrl);
                    }

                    return redirect('/account');
                }
            }

            // Reflected XSS: $username echoed back into view without escaping
            $username     = $request->input('username');
            $errorMessage = "Username or password are incorrect.";
        }

        return $this->view('user.login', [
            'pageTitle'    => 'Login',
            'returnUrl'    => $returnUrl,
            'username'     => $username,    // XSS: not escaped in template (intentional)
            'errorMessage' => $errorMessage,
        ]);
    }

    public function logout(Request $request)
    {
        if (!is_null(Auth::user())) {
            Auth::logout();
        }
        return redirect('/');
    }

    /**
     * @Vuln\Description("View: user/password.")
     */
    public function password(Request $request)
    {
        $successMessage = '';
        $errorMessage   = '';

        if ($request->isMethod('POST')) {
            // $email is raw — no sanitization (SQLi when vuln enabled)
            $email = $request->input('email');

            if ($email) {
                $emailData = User::getEmailData($email);

                if (!empty($emailData)) {
                    // Send password reset email
                    app('hackazon.email')->send(
                        $emailData['to'],
                        $emailData['from'],
                        $emailData['subject'],
                        $emailData['text']
                    );
                    $successMessage = "Check your email and restore password.";
                } else {
                    $errorMessage = "Email is incorrect.";
                }
            }
        }

        return $this->view('user.password', [
            'pageTitle'      => 'Restore password',
            'successMessage' => $successMessage,
            'errorMessage'   => $errorMessage,
        ]);
    }

    /**
     * @Vuln\Description("View: user/register.")
     */
    public function register(Request $request)
    {
        if (!is_null(Auth::user())) {
            return redirect('/account');
        }

        $errors     = [];
        $viewData   = ['pageTitle' => 'Registration'];

        if ($request->isMethod('POST')) {
            $dataUser = $this->getDataUser($request);
            $valid    = true;

            if (User::checkExistingUser($dataUser)) {
                $errors[] = "User already registered";
                $valid    = false;
            }

            if ($valid) {
                if (!$dataUser['username']) {
                    $valid    = false;
                    $errors[] = 'Please enter your username.';
                }

                if (!$dataUser['email']) {
                    $valid    = false;
                    $errors[] = 'Please enter your email.';
                }

                if (!$dataUser['password'] || $dataUser['password'] !== $dataUser['password_confirmation']) {
                    $valid    = false;
                    $errors[] = 'Passwords are missing or not equal.';
                }

                if ($valid) {
                    // RegisterUser stores data without sanitization — stored XSS preserved
                    User::registerUser($dataUser);
                    Auth::attempt([
                        'username' => $dataUser['username'],
                        'password' => $dataUser['password'],
                    ]);

                    // Send registration email
                    $emailView = view('user.register_email', ['data' => $dataUser])->render();
                    $emailData = User::getEmailData($dataUser['email']);
                    app('hackazon.email')->send(
                        $emailData['to'],
                        $emailData['from'],
                        'You have successfully registered on hackazon.com',
                        $emailView
                    );

                    return redirect('/account');
                }
            }

            if (!$valid) {
                foreach ($dataUser as $key => $value) {
                    $viewData[$key] = $value;
                }
            }
        }

        $viewData['errorMessage'] = implode('<br>', $errors);
        return $this->view('user.register', $viewData);
    }

    /**
     * @Vuln\Description("View: user/recover.")
     */
    public function recover(Request $request)
    {
        if (!$request->isMethod('GET')) {
            abort(404);
        }

        // recover token taken from query — no validation (IDOR preserved)
        $recoverPassw = $request->query('recover');

        if (!$recoverPassw) {
            abort(404);
        }

        $user = User::getUserByRecoveryPass($recoverPassw);

        if ($user) {
            return $this->view('user.recover', [
                'username'     => $user->username,
                'recover_passw' => $recoverPassw,
            ]);
        }

        abort(404);
    }

    /**
     * @Vuln\Description("View: user/recover.")
     */
    public function newpassw(Request $request)
    {
        if (!$request->isMethod('POST')) {
            abort(404);
        }

        // All inputs taken raw — no sanitization
        $username    = $request->input('username');
        $recoverPassw = $request->input('recover');
        $newPassw    = $request->input('password');
        $confirmPassw = $request->input('cpassword');

        if ($username && $recoverPassw && $newPassw && $confirmPassw) {
            if ($confirmPassw === $newPassw && User::checkRecoverPass($username, $recoverPassw)) {
                if (User::changeUserPassword($username, $newPassw)) {
                    Auth::attempt(['username' => $username, 'password' => $newPassw]);
                    return $this->view('user.recover', [
                        'successMessage' => 'The password has been changed successfully',
                    ]);
                }
                return $this->view('user.recover', []);
            }
        }

        abort(404);
    }

    /**
     * @Vuln\Description("View: user/terms.")
     */
    public function terms(Request $request)
    {
        return $this->view('user.terms');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function getDataUser(Request $request): array
    {
        // Raw input — no sanitization (XSS and SQLi vectors preserved)
        return [
            'first_name'            => $request->input('first_name'),
            'last_name'             => $request->input('last_name'),
            'email'                 => $request->input('email'),
            'username'              => $request->input('username'),
            'password'              => $request->input('password'),
            'password_confirmation' => $request->input('password_confirmation'),
        ];
    }
}
