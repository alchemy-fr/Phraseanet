<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Utils;

use Symfony\Component\HttpFoundation\Request;
use Silex\Application;
use Silex\ControllerProviderInterface;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class PathFileTest implements ControllerProviderInterface
{

    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        $controllers->get('/path/', function(Application $app, Request $request) {

                return $app->json(array(
                        'exists'     => file_exists($request->query->get('path'))
                        , 'file'       => is_file($request->query->get('path'))
                        , 'dir'        => is_dir($request->query->get('path'))
                        , 'readable'   => is_readable($request->query->get('path'))
                        , 'writeable'  => is_writable($request->query->get('path'))
                        , 'executable' => is_executable($request->query->get('path'))
                    ));
            });

        $controllers->get('/url/', function(Application $app, Request $request) {

                return $app->json(array('code' => \http_query::getHttpCodeFromUrl($request->query->get('url'))));
            });

        return $controllers;
    }
}
