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

class Controller_Admin_Fields implements ControllerProviderInterface
{

  public function connect(Application $app)
  {
    $appbox = appbox::get_instance();
    $session = $appbox->get_session();
//    $session->close_storage();

    $controllers = new ControllerCollection();


    $controllers->get('/checkmulti/', function() use ($app, $appbox)
            {
              $request = $app['request'];
              $multi = ($request->get('multi') === 'true');

              $metadata = databox_field::load_class_from_xpath($request->get('source'));

              $datas = array(
                  'result' => ($multi === $metadata->is_multi())
                  , 'is_multi' => $metadata->is_multi()
              );

              return new Response(p4string::jsonencode($datas));
            });

    $controllers->get('/checkreadonly/', function() use ($app, $appbox)
            {
              $request = $app['request'];
              $readonly = ($request->get('readonly') === 'true');

              $metadata = databox_field::load_class_from_xpath($request->get('source'));

              $datas = array(
                  'result' => ($readonly === $metadata->is_readonly())
                  , 'is_readonly' => $metadata->is_readonly()
              );

              return new Response(p4string::jsonencode($datas));
            });

    return $controllers;
  }

}
