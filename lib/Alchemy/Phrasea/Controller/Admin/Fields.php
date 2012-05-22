<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Admin;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ControllerCollection;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Fields implements ControllerProviderInterface
{

    public function connect(Application $app)
    {
        $appbox = \appbox::get_instance($app['Core']);

        $controllers = new ControllerCollection();

        $controllers->get('/checkmulti/', function() use ($app, $appbox) {
                $request = $app['request'];

                $multi = ($request->get('multi') === 'true');

                $tag = \databox_field::loadClassFromTagName($request->get('source'));

                $datas = array(
                    'result'   => ($multi === $tag->isMulti()),
                    'is_multi' => $tag->isMulti(),
                );

                $Serializer = $app['Core']['Serializer'];

                return new Response(
                        $Serializer->serialize($datas, 'json')
                        , 200
                        , array('Content-Type' => 'application/json')
                );
            });

        $controllers->get('/checkreadonly/', function() use ($app, $appbox) {
                $request = $app['request'];
                $readonly = ($request->get('readonly') === 'true');

                $tag = \databox_field::loadClassFromTagName($request->get('source'));

                $datas = array(
                    'result'      => ($readonly !== $tag->isWritable()),
                    'is_readonly' => ! $tag->isWritable(),
                );

                $Serializer = $app['Core']['Serializer'];

                return new Response(
                        $Serializer->serialize($datas, 'json'),
                        200,
                        array('Content-Type' => 'application/json')
                );
            });

        return $controllers;
    }
}
