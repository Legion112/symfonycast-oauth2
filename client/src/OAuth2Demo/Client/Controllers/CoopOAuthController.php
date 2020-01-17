<?php

namespace OAuth2Demo\Client\Controllers;

use Silex\Application;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Guzzle\Http\Client;

class CoopOAuthController extends BaseController
{
    public static function addRoutes($routing)
    {
        $routing->get('/coop/oauth/start', array(new self(), 'redirectToAuthorization'))->bind('coop_authorize_start');
        $routing->get('/coop/oauth/handle', array(new self(), 'receiveAuthorizationCode'))->bind('coop_authorize_redirect');
    }

    /**
     * This page actually redirects to the COOP authorize page and begins
     * the typical, "auth code" OAuth grant type flow.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function redirectToAuthorization(Request $request)
    {
        $redirectUri = $this->generateUrl(
            'coop_authorize_redirect',
            [],
            true
        );

        $url = 'http://coop.apps.symfonycasts.com/authorize?'.http_build_query([
            'response_type' => 'code',
            'client_id' => 'Top Cluckd',
            'redirect_uri' => $redirectUri,
            'scope' => 'eggs-count profile',
        ]);

        return $this->redirect($url);
    }

    /**
     * This is the URL that COOP will redirect back to after the user approves/denies access
     *
     * Here, we will get the authorization code from the request, exchange
     * it for an access token, and maybe do some other setup things.
     *
     * @param Application $app
     * @param Request $request
     * @return string|RedirectResponse
     * @throws \Exception
     * @noinspection NullPointerExceptionInspection
     */
    public function receiveAuthorizationCode(Application $app, Request $request)
    {
        // equivalent to $_GET['code']
        $code = $request->get('code');

        if (!$code) {
            $error = $request->get('error');
            $errorDescription = $request->get('error_description');

            return $this->render('failed_authorization.twig', [
                'response' => [
                    'error' => $error,
                    'error_description' => $errorDescription,
                ]
            ]);
        }

        // create our http client (Guzzle)
        $http = new Client('http://coop.apps.knpuniversity.com', array(
            'request.options' => array(
                'exceptions' => false,
            )
        ));

        $redirectUri = $this->generateUrl(
            'coop_authorize_redirect',
            [],
            true
        );

        $request = $http->post('/token', null, [
            'client_id' => 'Top Cluckd',
            'client_secret' => 'b7c8d0bdf9e39f4494ac9badd9a8a4a7',
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $redirectUri,
        ]);

        $response = $request->send();
        $responseBody =  $response->getBody(true);
        $responseArr = json_decode($responseBody, true);

        if (!isset($responseArr['access_token'])) {
            return $this->render('failed_token_request.twig', [
                'response' => $responseArr ? $responseArr : $response
            ]);
        }

        $accessToken = $responseArr['access_token'];
        $expireIn = $responseArr['expires_in'];
        $expiresAt = new \DateTime("+$expireIn seconds");

        $request  = $http->get('/api/me');

        $request->addHeader('Authorization', 'Bearer '.$accessToken);
        $response = $request->send();

        $json = json_decode($response->getBody(), true);

        $user = $this->getLoggedInUser();
        $user->coopAccessToken = $accessToken;
        $user->coopUserId = $json['id'];
        $user->coopAccessExpiresAt = $expiresAt;
        $this->saveUser($user);

        // redirects to the homepage
        return $this->redirect($this->generateUrl('home'));
    }
}
