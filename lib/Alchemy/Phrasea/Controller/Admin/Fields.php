<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Admin;

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
class Fields implements ControllerProviderInterface
{

  public function connect(Application $app)
  {
    $appbox  = \appbox::get_instance();
    $session = $appbox->get_session();

    $controllers = new ControllerCollection();


    $controllers->get('/checkmulti/', function() use ($app, $appbox)
      {
        $request = $app['request'];
        $multi   = ($request->get('multi') === 'true');

        $metadata = \databox_field::load_class_from_xpath($request->get('source'));

        $datas = array(
          'result'   => ($multi === $metadata->is_multi())
          , 'is_multi' => $metadata->is_multi()
        );

        $Serializer = $app['Core']['Serializer'];

        return new Response(
            $Serializer->serialize($datas, 'json')
            , 200
            , array('Content-Type' => 'application/json')
        );
      });

    $controllers->get('/checkreadonly/', function() use ($app, $appbox)
      {
        $request  = $app['request'];
        $readonly = ($request->get('readonly') === 'true');

        $metadata = \databox_field::load_class_from_xpath($request->get('source'));

        $datas = array(
          'result'      => ($readonly === $metadata->is_readonly())
          , 'is_readonly' => $metadata->is_readonly()
        );

        $Serializer = $app['Core']['Serializer'];

        return new Response(
            $Serializer->serialize($datas, 'json')
            , 200
            , array('Content-Type' => 'application/json')
        );
      });

    return $controllers;
  }

}