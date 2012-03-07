<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ControllerCollection;

class Controller_Utils_PathFileTest implements ControllerProviderInterface
{

  public function connect(Application $app)
  {
    $controllers = new ControllerCollection();

    $controllers->get('/path/', function() use ($app)
            {
              $path = $app['request']->get('path');

              return new Response(p4string::jsonencode(array(
                                  'exists' => file_exists($path)
                                  , 'file' => is_file($path)
                                  , 'dir' => is_dir($path)
                                  , 'readable' => is_readable($path)
                                  , 'writeable' => is_writable($path)
                                  , 'executable' => is_executable($path)
                              )), 200, array('application/json'));
            });

    $controllers->get('/url/', function() use ($app)
            {
              $url = $app['request']->get('url');

              return new Response(p4string::jsonencode(array(
                                  'code' => http_query::getHttpCodeFromUrl($url)
                              )), 200, array('application/json'));
            });


    return $controllers;
  }

}
