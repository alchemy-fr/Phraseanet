<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Middleware;

use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class BasketMiddlewareProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['middleware.basket.converter'] = $app->protect(function (Request $request, Application $app) {
            if ($request->attributes->has('basket')) {
                $request->attributes->set('basket', $app['converter.basket']->convert($request->attributes->get('basket')));
            }
        });

        $app['middleware.basket.user-access'] = $app->protect(function (Request $request, Application $app) {
            if ($request->attributes->has('basket')) {
                if (!$app['acl.basket']->hasAccess($request->attributes->get('basket'), $app['authentication']->getUser())) {
                    throw new AccessDeniedHttpException('Current user does not have access to the basket');
                }
            }
        });

        $app['middleware.basket.user-is-owner'] = $app->protect(function (Request $request, Application $app) {
            if (!$app['acl.basket']->isOwner($request->attributes->get('basket'), $app['authentication']->getUser())) {
                throw new AccessDeniedHttpException('Only basket owner can modify the basket');
            }
        });
    }

    public function boot(Application $app)
    {
    }
}
