<?php

namespace Alchemy\Phrasea\Security;

use Silex\Application;

class Firewall
{

    public function requireSetUp(Application $app)
    {
        if ( ! \setup::is_installed()) {
            return $app->redirect("/setup/");
        }
    }

    public function requireAuthentication(Application $app)
    {
        if ($app['phraseanet.core']->isAuthenticated()) {
            try {
                $session = $app['phraseanet.appbox']->get_session();
                $session->open_phrasea_session();
            } catch (\Exception $e) {

                return $app->redirect('/login/logout/');
            }
        } else {

            return $app->redirect('/login/');
        }

        if ($app['phraseanet.core']->getAuthenticatedUser()->is_guest()) {

            return $app->redirect('/login/');
        }
    }

    public function requireNotAuthenticated(Application $app)
    {
        if ($app['phraseanet.core']->isAuthenticated()) {
            return $app->redirect('/prod/');
        }
    }
}
