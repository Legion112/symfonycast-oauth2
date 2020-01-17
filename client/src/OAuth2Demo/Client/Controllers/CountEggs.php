<?php

namespace OAuth2Demo\Client\Controllers;

use Guzzle\Http\Client;

class CountEggs extends BaseController
{
    public static function addRoutes($routing)
    {
        $routing->get('/coop/count-eggs', array(new self(), 'countEggs'))->bind('count_eggs');
    }

    /**
     * A page that updates the egg count by making an API request to COOP.
     *
     * When it's finished, it just redirects back to the homepage.
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \Exception
     */
    public function countEggs()
    {

        // create our http client (Guzzle)
        $http = new Client('http://coop.apps.knpuniversity.com', array(
            'request.options' => array(
                'exceptions' => false,
            )
        ));

        $user = $this->getLoggedInUser();

        if(!$user->coopUserId || !$user->coopAccessToken) {
            throw new \Exception('How did you get here! :)');
        }

        if ($user->hasCoopAccessTokenExpired()) {
            return $this->redirect($this->generateUrl('coop_authorize_start'));
        }


        $request  = $http->post("/api/{$user->coopUserId}/eggs-count");
        $request->addHeader('Authorization', 'Bearer '.$user->coopAccessToken);
        $response = $request->send();
        $json = json_decode($response->getBody(), true);

        if ($response->isError()) {
            // it may be simply that the access token is expired!
            // if that's true we may just want to re-authorize them
            throw new \Exception($response->getBody());
        }


        $this->setTodaysEggCountForUser($user, $json['data']);


        return $this->redirect($this->generateUrl('home'));
    }
}
