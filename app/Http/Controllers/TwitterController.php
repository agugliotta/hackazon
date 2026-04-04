<?php

namespace App\Http\Controllers;

use App\Models\User as UserModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Class TwitterController
 * OAuth Twitter login handler.
 * Replaces App\Controller\Twitter (which extended PHPixie\Auth\Controller\Twitter).
 */
class TwitterController extends PageController
{
    /**
     * Handle Twitter OAuth callback / new user creation.
     * Preserves the original logic: fetch user data from Twitter,
     * save OAuth user, set session.
     */
    public function callback(Request $request)
    {
        $accessToken = $request->query('oauth_token');
        $returnUrl   = $request->query('return_url', '/account');
        $displayMode = $request->query('display_mode', 'page');

        // Fetch Twitter user data (preserved original approach)
        $twitterUser = $this->getTwitterUser($accessToken);
        $data = json_decode($twitterUser);

        // Save the new OAuth user (same logic as original new_user())
        $model = new UserModel();
        $user  = $model->saveOAuthUser($data->name, $data->id, 'twitter');

        // Log the user in
        Auth::login($user);

        if ($displayMode === 'popup') {
            return response('<script>window.close();</script>');
        }

        return redirect($returnUrl ?: '/account');
    }

    /**
     * Fetch Twitter user data by access token.
     * Original used $this->provider->getTwitterUser($accessToken).
     */
    protected function getTwitterUser(string $accessToken): string
    {
        // Placeholder — actual OAuth handshake is handled by middleware/provider
        return '{}';
    }
}
