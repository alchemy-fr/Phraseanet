<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Prod;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Alchemy\Phrasea\Helper\Record as RecordHelper;

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class MoveCollection implements ControllerProviderInterface
{

    public function connect(Application $app)
    {
        $controllers = new ControllerCollection();

        $controllers->post('/', function(Application $app, Request $request) {
                $request = $app['request'];
                $move = new RecordHelper\MoveCollection($app['Core'], $app['request']);
                $move->propose();

                $template = 'prod/actions/collection_default.twig';
                /* @var $twig \Twig_Environment */
                $twig = $app['Core']->getTwig();

                return $twig->render($template, array('action'  => $move, 'message' => ''));
            }
        );


        $controllers->post('/apply/', function(Application $app) {
                $request = $app['request'];
                $move = new RecordHelper\MoveCollection($app['Core'], $app['request']);
                $move->execute($request);
                $template = 'prod/actions/collection_submit.twig';

                /* @var $twig \Twig_Environment */
                $twig = $app['Core']->getTwig();

                return $twig->render($template, array('action'  => $move, 'message' => ''));
            });

        return $controllers;
    }
}
