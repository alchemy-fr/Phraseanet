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

class ApiApplicationMiddlewareProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['middleware.api-application.converter'] = $app->protect(function (Request $request, Application $app) {
            if ($request->attributes->has('application')) {
                $request->attributes->set('application', $app['converter.api-application']->convert($request->attributes->get('application')));
            }
        });
    }

    public function boot(Application $app)
    {
    }
}
