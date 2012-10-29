<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Prod;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Alchemy\Phrasea\Helper\Record as RecordHelper;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class MoveCollection implements ControllerProviderInterface
{

    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

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

        $controllers->post('/apply/', function(Application $app, Request $request) {
                $move = new RecordHelper\MoveCollection($app['Core'], $request);
                $success = false;

                try {
                    $move->execute();
                    $success = true;
                    $msg = _('Records have been successfuly moved');
                } catch (\Exception_Unauthorized $e) {
                    $msg = sprintf(_("You do not have the permission to move records to %s"), \phrasea::bas_names($move->getBaseIdDestination()));
                } catch (\Exception $e) {
                    $msg = _('An error occured');
                }

                $datas = array(
                    'success' => $success,
                    'message' => $msg
                );

                return new JsonResponse($datas);
            });

        return $controllers;
    }
}
