<?php

namespace App\Http\Controllers;

use App\Models\User as UserModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

/**
 * Class FacebookController
 * OAuth Facebook login handler.
 * Replaces App\Controller\Facebook (which extended PHPixie\Auth\Controller\Facebook).
 */
class FacebookController extends PageController
{
    /**
     * Handle Facebook OAuth callback / new user creation.
     * Preserves the original new_user() logic.
     */
    public function callback(Request $request)
    {
        $accessToken = $request->query('access_token');
        $returnUrl   = $request->query('return_url', '/account');
        $displayMode = $request->query('display_mode', 'page');

        // Fetch Facebook user data (original used CURL via $this->provider->request())
        $response = Http::get("https://graph.facebook.com/me?access_token=" . $accessToken);
        $data = $response->object();

        // Save the new OAuth user (same logic as original new_user())
        $model = new UserModel();
        $user  = $model->saveOAuthUser($data->first_name, $data->id, 'facebook');

        // Log the user in
        Auth::login($user);

        if ($displayMode === 'popup') {
            return response('<script>window.close();</script>');
        }

        return redirect($returnUrl ?: '/account');
    }
}
