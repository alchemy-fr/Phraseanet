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
        if ($app['Core']->isAuthenticated()) {
            try {
                $session = \appbox::get_instance($app['Core'])->get_session();
                $session->open_phrasea_session();
            } catch (\Exception $e) {

                return $app->redirect('/login/logout.php');
            }
        } else {

            return $app->redirect('/login/');
        }

        if ($app['Core']->getAuthenticatedUser()->is_guest()) {

            return $app->redirect('/login/');
        }
    }
}
