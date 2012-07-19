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

        $controllers->get('/path/', function( Request $request) {
                $path = $request->get('path');

                return $app->json(array(
                        'exists'     => file_exists($path)
                        , 'file'       => is_file($path)
                        , 'dir'        => is_dir($path)
                        , 'readable'   => is_readable($path)
                        , 'writeable'  => is_writable($path)
                        , 'executable' => is_executable($path)
                    ));
            });

        $controllers->get('/url/', function( Request $request) {
                $url = $request->get('url');

                return $app->json(array('code' => \http_query::getHttpCodeFromUrl($url)));
            });

        return $controllers;
    }
}
