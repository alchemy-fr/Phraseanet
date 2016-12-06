<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\ControllerProvider\Prod;

use Alchemy\Phrasea\Application as PhraseaApplication;
use Alchemy\Phrasea\Controller\Prod\ShareController;
use Alchemy\Phrasea\ControllerProvider\ControllerProviderTrait;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ServiceProviderInterface;
use Symfony\Component\HttpFoundation\Request;

class Share implements ControllerProviderInterface, ServiceProviderInterface
{
    use ControllerProviderTrait;

    public function register(Application $app)
    {
        $app['controller.prod.share'] = $app->share(function (PhraseaApplication $app) {
            return (new ShareController($app))
                ;
        });
    }

    public function boot(Application $app)
    {
        // no-op
    }

    public function connect(Application $app)
    {
        $controllers = $this->createAuthenticatedCollection($app);
        $firewall = $this->getFirewall($app);

        $controllers->before(function () use ($firewall) {
            $firewall->requireNotGuest();
        });

        $controllers->get('/record/{base_id}/{record_id}/', 'controller.prod.share:shareRecord')
            ->before(function (Request $request) use ($app, $firewall) {
                $firewall->requireRightOnSbas(
                    \phrasea::sbasFromBas($app, $request->attributes->get('base_id')),
                    \ACL::BAS_CHUPUB
                );
            })
            ->bind('share_record');

        return $controllers;
    }
}
