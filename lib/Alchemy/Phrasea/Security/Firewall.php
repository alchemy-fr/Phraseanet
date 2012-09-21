<?php

namespace Alchemy\Phrasea\Security;

use Silex\Application;
use \Symfony\Component\HttpFoundation\Response;

class Firewall
{
    private $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function requireSetUp()
    {
        if (!\setup::is_installed()) {
            return $this->app->redirect("/setup/");
        }

        return $this;
    }

    public function requireAdmin()
    {
        $response = $this->requireNotGuest();
        if ($response instanceof Response) {
            return $response;
        }

        if (!$this->app['phraseanet.user']->ACL()->is_admin()) {
            $this->app->abort(403, 'Admin role is required');
        }

        return $this;
    }

    public function requireAccessToModule($module)
    {
        $response = $this->requireAuthentication();
        if ($response instanceof Response) {
            return $response;
        }

        if (!$this->app['phraseanet.user']->ACL()->has_access_to_module($module)) {
            $this->app->abort(403, 'You do not have required rights');
        }

        unset($response);

        return $this;
    }

    public function requireAccessToSbas($sbas_id)
    {
        $response = $this->requireAuthentication();
        if ($response instanceof Response) {
            return $response;
        }

        if (!$this->app['phraseanet.user']->ACL()->has_access_to_sbas($sbas_id)) {
            $this->app->abort(403, 'You do not have required rights');
        }

        unset($response);

        return $this;
    }

    public function requireAccessToBase($base_id)
    {
        $response = $this->requireAuthentication();
        if ($response instanceof Response) {
            return $response;
        }

        if (!$this->app['phraseanet.user']->ACL()->has_access_to_base($base_id)) {
            $this->app->abort(403, 'You do not have required rights');
        }

        unset($response);

        return $this;
    }

    public function requireRight($right)
    {
        $response = $this->requireAuthentication();
        if ($response instanceof Response) {
            return $response;
        }

        if (!$this->app['phraseanet.user']->ACL()->has_right($right)) {
            $this->app->abort(403, 'You do not have required rights');
        }

        unset($response);

        return $this;
    }

    public function requireRightOnBase($base_id, $right)
    {
        $response = $response = $this->requireAuthentication();
        if ($response instanceof Response) {
            return $response;
        }

        if (!$this->app['phraseanet.user']->ACL()->has_right_on_base($base_id, $right)) {
            $this->app->abort(403, 'You do not have required rights');
        }

        return $this;
    }


    public function requireRightOnSbas($sbas_id, $right)
    {
        $response = $response = $this->requireAuthentication();
        if ($response instanceof Response) {
            return $response;
        }

        if (!$this->app['phraseanet.user']->ACL()->has_right_on_sbas($sbas_id, $right)) {
            $this->app->abort(403, 'You do not have required rights');
        }

        return $this;
    }

    public function requireNotGuest()
    {
        $response = $response = $this->requireAuthentication();
        if ($response instanceof Response) {
            return $response;
        }

        if ($this->app['phraseanet.user']->is_guest()) {
            $this->app->abort(403, 'Guests do not have admin role');
        }

        return $this;
    }

    public function requireAuthentication()
    {
        if (!$this->app->isAuthenticated()) {
            return $this->app->redirect('/login/');
        }

        return $this;
    }

    public function requireOrdersAdmin()
    {
        if (false === !!count($this->app['phraseanet.user']->ACL()->get_granted_base(array('order_master')))) {
            $this->app->abort(403);
        }

        return $this;
    }
}
