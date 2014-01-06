<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Utils;

use Symfony\Component\HttpFoundation\Request;
use Silex\Application;
use Silex\ControllerProviderInterface;

class PathFileTest implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $app['controller.utils.pathfile-test'] = $this;

        $controllers = $app['controllers_factory'];

        /**
         * @todo : check this as it would lead to a security issue
         */
        $controllers->get('/path/', function (Application $app, Request $request) {
            return $app->json([
                    'exists'     => file_exists($request->query->get('path'))
                    , 'file'       => is_file($request->query->get('path'))
                    , 'dir'        => is_dir($request->query->get('path'))
                    , 'readable'   => is_readable($request->query->get('path'))
                    , 'writeable'  => is_writable($request->query->get('path'))
                    , 'executable' => is_executable($request->query->get('path'))
                ]);
        });

        $controllers->get('/url/', function (Application $app, Request $request) {
            return $app->json(['code' => \http_query::getHttpCodeFromUrl($request->query->get('url'))]);
        });

        return $controllers;
    }
}
