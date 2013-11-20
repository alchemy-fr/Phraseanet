<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Security;

use Silex\Application;

class Firewall
{
    private $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function requireSetUp()
    {
        if (!$this->app['phraseanet.configuration-tester']->isInstalled()) {
            $this->app->abort(302, 'Phraseanet is not installed', [
                'X-Phraseanet-Redirect' => $this->app->path('setup')
            ]);
        }

        return null;
    }

    public function requireAdmin()
    {
        $this->requireNotGuest();

        if (!$this->app['acl']->get($this->app['authentication']->getUser())->is_admin()) {
            $this->app->abort(403, 'Admin role is required');
        }

        return $this;
    }

    public function requireAccessToModule($module)
    {
        $this->requireAuthentication();

        if (!$this->app['acl']->get($this->app['authentication']->getUser())->has_access_to_module($module)) {
            $this->app->abort(403, 'You do not have required rights');
        }

        return $this;
    }

    public function requireAccessToSbas($sbas_id)
    {
        $this->requireAuthentication();

        if (!$this->app['acl']->get($this->app['authentication']->getUser())->has_access_to_sbas($sbas_id)) {
            $this->app->abort(403, 'You do not have required rights');
        }

        return $this;
    }

    public function requireAccessToBase($base_id)
    {
        $this->requireAuthentication();

        if (!$this->app['acl']->get($this->app['authentication']->getUser())->has_access_to_base($base_id)) {
            $this->app->abort(403, 'You do not have required rights');
        }

        return $this;
    }

    public function requireRight($right)
    {
        $this->requireAuthentication();

        if (!$this->app['acl']->get($this->app['authentication']->getUser())->has_right($right)) {
            $this->app->abort(403, 'You do not have required rights');
        }

        return $this;
    }

    public function requireRightOnBase($base_id, $right)
    {
        $this->requireAuthentication();

        if (!$this->app['acl']->get($this->app['authentication']->getUser())->has_right_on_base($base_id, $right)) {
            $this->app->abort(403, 'You do not have required rights');
        }

        return $this;
    }

    public function requireRightOnSbas($sbas_id, $right)
    {
        $this->requireAuthentication();

        if (!$this->app['acl']->get($this->app['authentication']->getUser())->has_right_on_sbas($sbas_id, $right)) {
            $this->app->abort(403, 'You do not have required rights');
        }

        return $this;
    }

    public function requireNotGuest()
    {
        $this->requireAuthentication();

        if ($this->app['authentication']->getUser()->is_guest()) {
            $this->app->abort(403, 'Guests do not have admin role');
        }

        return $this;
    }

    public function requireAuthentication()
    {
        if (!$this->app['authentication']->isAuthenticated()) {
            $this->app->abort(302, 'You are not authenticated', [
                'X-Phraseanet-Redirect' => $this->app->path('homepage')
            ]);
        }

        return $this;
    }

    public function requireNotAuthenticated()
    {
        if ($this->app['authentication']->isAuthenticated()) {
            $this->app->abort(302, 'You are authenticated', [
                'X-Phraseanet-Redirect' => $this->app->path('prod')
            ]);
        }

        return $this;
    }

    public function requireOrdersAdmin()
    {
        if (false === !!count($this->app['acl']->get($this->app['authentication']->getUser())->get_granted_base(['order_master']))) {
            $this->app->abort(403, 'You are not an order admin');
        }

        return $this;
    }
}
