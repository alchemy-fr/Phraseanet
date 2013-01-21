<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller;

use Alchemy\Phrasea\Application as PhraseaApplication;
use Silex\Application;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Permalink extends AbstractDelivery
{

    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        $that = $this;

        $controllers->get('/v1/{label}/{sbas_id}/{record_id}/{key}/{subdef}/view/', function($label, $sbas_id, $record_id, $key, $subdef, PhraseaApplication $app) {

            $databox = $app['phraseanet.appbox']->get_databox((int) $sbas_id);

            $record = \media_Permalink_Adapter::challenge_token($app, $databox, $key, $record_id, $subdef);

            if (!$record instanceof \record_adapter) {
                throw new \Exception_NotFound('bad luck');
            }

            $params = array(
                'subdef_name' => $subdef
                , 'module_name' => 'overview'
                , 'module'      => 'overview'
                , 'view'        => 'overview'
                , 'record'      => $record
            );

            return $app['twig']->render('overview.html.twig', $params);
        })->assert('sbas_id', '\d+')->assert('record_id', '\d+');

        $controllers->get('/v1/{label}/{sbas_id}/{record_id}/{key}/{subdef}/', function(Application $app, $label, $sbas_id, $record_id, $key, $subdef) use ($that) {
            $databox = $app['phraseanet.appbox']->get_databox((int) $sbas_id);
            $record = \media_Permalink_Adapter::challenge_token($app, $databox, $key, $record_id, $subdef);

            if (!($record instanceof \record_adapter)) {
                throw new \Exception_NotFound('bad luck');
            }

            $watermark = $stamp = false;

            if ($app->isAuthenticated()) {
                $user = \User_Adapter::getInstance($app['phraseanet.user']->get_id(), $app);

                $watermark = !$user->ACL()->has_right_on_base($record->get_base_id(), 'nowatermark');

                if ($watermark) {

                    $repository = $app['EM']->getRepository('\Entities\BasketElement');

                    if (count($repository->findReceivedValidationElementsByRecord($record, $user)) > 0) {
                        $watermark = false;
                    } elseif (count($repository->findReceivedElementsByRecord($record, $user)) > 0) {
                        $watermark = false;
                    }
                }

                return $that->deliverContent($app['request'], $record, $subdef, $watermark, $stamp, $app);
            } else {
                $collection = \collection::get_from_base_id($app, $record->get_base_id());
                switch ($collection->get_pub_wm()) {
                    default:
                    case 'none':
                        $watermark = false;
                        break;
                    case 'stamp':
                        $stamp = true;
                        break;
                    case 'wm':
                        $watermark = false;
                        break;
                }
            }

            return $that->deliverContent($app['request'], $record, $subdef, $watermark, $stamp, $app);
        })->assert('sbas_id', '\d+')->assert('record_id', '\d+');

        return $controllers;
    }
}
