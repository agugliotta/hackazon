<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Class AuthController
 * PHPixie-style auth controller replacement.
 * Note: before() in legacy threw NotFoundException — this maps to a 404 for the 'auth' prefix routes.
 */
class AuthController extends PageController
{
    protected function before(): mixed
    {
        // Original threw NotFoundException in before() to block direct access
        abort(404);
    }

    public function login(Request $request)
    {
        if ($request->isMethod('POST')) {
            $login    = $request->input('username');
            $password = $request->input('password');

            // Attempt login — no sanitization on inputs (preserved from original)
            if (Auth::attempt(['username' => $login, 'password' => $password])) {
                return redirect('/account');
            }
        }

        return $this->view('auth.login', ['breadcrumbs' => 'Account Entrance']);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        return redirect('/');
    }

    public function password(Request $request)
    {
        return $this->view('auth.password');
    }

    public function register(Request $request)
    {
        if (!is_null(Auth::user())) {
            return redirect('/account');
        }

        if ($request->isMethod('POST')) {
            $login    = $request->input('username');
            $password = $request->input('password');

            $existingUser = User::where('username', $login)->get();

            if ($existingUser->count() > 0) {
                $errorMessage = "User already registered";
            } else {
                User::registerUser($login, $password);
                Auth::attempt(['username' => $login, 'password' => $password]);
                return redirect('/account');
            }
        }

        return $this->view('auth.register', ['errorMessage' => $errorMessage ?? '']);
    }

    /**
     * Facebook OAuth handler (legacy stub).
     * Actual OAuth flow is handled by FacebookController.
     */
    public function facebook(Request $request)
    {
        $accessToken = $request->query('access_token');
        $returnUrl   = $request->query('return_url');
        $displayMode = $request->query('display_mode', 'page');

        $response = \Illuminate\Support\Facades\Http::get(
            "https://graph.facebook.com/me?access_token=" . $accessToken
        );
        $data = $response->object();

        $user = User::saveOAuthUser($data->first_name, $data->id, 'facebook');
        Auth::login($user);

        if ($displayMode === 'popup') {
            return response('<script>window.close();</script>');
        }

        return redirect($returnUrl ?: '/account');
    }
}
