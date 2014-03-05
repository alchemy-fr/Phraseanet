<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Middleware;

use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\HttpFoundation\Request;

class TokenMiddlewareProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['middleware.token.converter'] = $app->protect(function (Request $request, Application $app) {
            if ($request->attributes->has('token')) {
                $request->attributes->set('token', $app['converter.token']->convert($request->attributes->get('token')));
            }
        });
    }

    public function boot(Application $app)
    {
    }
}
