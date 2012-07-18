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

use Alchemy\Phrasea\Application as PhraseaApplication;
use Symfony\Component\HttpFoundation\Request;
use Silex\Application;
use Silex\ControllerProviderInterface;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Fields implements ControllerProviderInterface
{

    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        $controllers->get('/checkmulti/', function(PhraseaApplication $app, Request $request) {

                $multi = ($request->get('multi') === 'true');

                $tag = \databox_field::loadClassFromTagName($request->get('source'));

                $datas = array(
                    'result'   => ($multi === $tag->isMulti()),
                    'is_multi' => $tag->isMulti(),
                );

                return $app->json($app['phraseanet.core']['Serializer']->serialize($datas, 'json'));
            });

        $controllers->get('/checkreadonly/', function(PhraseaApplication $app, Request $request) {
                $readonly = ($request->get('readonly') === 'true');

                $tag = \databox_field::loadClassFromTagName($request->get('source'));

                $datas = array(
                    'result'      => ($readonly !== $tag->isWritable()),
                    'is_readonly' => ! $tag->isWritable(),
                );

                return $app->json($app['phraseanet.core']['Serializer']->serialize($datas, 'json'));
            });

        return $controllers;
    }
}
