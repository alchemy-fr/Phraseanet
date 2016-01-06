<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Phrasea\ControllerProvider;

use Alchemy\Phrasea\Security\Firewall;
use Silex\Application;
use Silex\ControllerCollection;

trait ControllerProviderTrait
{
    /**
     * @param Application $app
     * @return ControllerCollection
     */
    protected function createAuthenticatedCollection(Application $app)
    {
        $controllers = $app['controllers_factory'];

        $this->getFirewall($app)->addMandatoryAuthentication($controllers);

        return $controllers;
    }

    /**
     * @param Application $app
     * @return ControllerCollection
     */
    protected function createCollection(Application $app)
    {
        return $app['controllers_factory'];
    }

    /**
     * @param Application $app
     * @return Firewall
     */
    protected function getFirewall(Application $app)
    {
        return $app['firewall'];
    }
}
