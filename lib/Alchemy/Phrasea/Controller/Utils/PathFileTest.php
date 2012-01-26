<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Utils;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ControllerCollection;

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class PathFileTest implements ControllerProviderInterface
{

  public function connect(Application $app)
  {
    $controllers = new ControllerCollection();

    $controllers->get('/path/', function() use ($app)
      {
        $path = $app['request']->get('path');

        $Serializer = $app['Core']['Serializer'];

        return new Response(
            $Serializer->serialize(
              array(
              'exists'     => file_exists($path)
              , 'file'       => is_file($path)
              , 'dir'        => is_dir($path)
              , 'readable'   => is_readable($path)
              , 'writeable'  => is_writable($path)
              , 'executable' => is_executable($path)
              )
              , 'json'
            )
            , 200
            , array('content-type' => 'application/json')
        );
      });

    $controllers->get('/url/', function() use ($app)
      {
        $url = $app['request']->get('url');

        $Serializer = $app['Core']['Serializer'];

        return new Response(
            $Serializer->serialize(
              array(
              'code' => \http_query::getHttpCodeFromUrl($url)
              )
              , 'json'
            )
            , 200
            , array('content-type' => 'application/json')
        );
      });


    return $controllers;
  }

}
