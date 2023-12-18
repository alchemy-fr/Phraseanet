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
use Alchemy\Phrasea\Core\LazyLocator;
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
                ->setEntityManagerLocator(new LazyLocator($app, 'orm.em'))
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

        // tranform 'basket' argument (id) to basket object
        $controllers->before($app['middleware.basket.converter']);

        $controllers->get('/record/{base_id}/{record_id}/', 'controller.prod.share:shareRecord')
            ->before(function (Request $request) use ($app, $firewall) {
                $socialTools = $app['conf']->get(['registry', 'actions', 'social-tools']);
                if ($socialTools === "all") {
                    return;
                }
                elseif ($socialTools === "none") {
                    $app->abort(403, 'social tools disabled');
                }
                elseif ($socialTools === "publishers") {
                    $firewall->requireRightOnSbas(
                        \phrasea::sbasFromBas($app, $request->attributes->get('base_id')),
                        \ACL::BAS_CHUPUB
                    );
                }
                else {
                    throw new \Exception("bad value \"" . $socialTools . "\" for social tools");
                }
            })
            ->bind('share_record');

        /** @uses ShareController::quitshareAction() */
        $controllers->post('/quitshare/{basket}/', 'controller.prod.share:quitshareAction')
            ->assert('basket', '\d+')
            ->bind('prod_share_quitshare');

        return $controllers;
    }
}
