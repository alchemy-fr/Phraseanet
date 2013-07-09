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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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

        $deliverPermaview = function($sbas_id, $record_id, $token, $subdef, PhraseaApplication $app) {
            $databox = $app['phraseanet.appbox']->get_databox((int) $sbas_id);

            $record = \media_Permalink_Adapter::challenge_token($app, $databox, $token, $record_id, $subdef);

            if (!$record instanceof \record_adapter) {
                throw new NotFoundHttpException('bad luck');
            }

            $params = array(
                'subdef_name' => $subdef
                , 'module_name' => 'overview'
                , 'module'      => 'overview'
                , 'view'        => 'overview'
                , 'record'      => $record
            );

            return $app['twig']->render('overview.html.twig', $params);
        };

        $deliverPermalink = function(PhraseaApplication $app, $sbas_id, $record_id, $token, $subdef) use ($that) {
            $databox = $app['phraseanet.appbox']->get_databox((int) $sbas_id);
            $record = \media_Permalink_Adapter::challenge_token($app, $databox, $token, $record_id, $subdef);

            if (!($record instanceof \record_adapter)) {
                throw new NotFoundHttpException('bad luck');
            }

            $watermark = $stamp = false;

            if ($app['authentication']->isAuthenticated()) {
                $user = \User_Adapter::getInstance($app['authentication']->getUser()->get_id(), $app);

                $watermark = !$user->ACL()->has_right_on_base($record->get_base_id(), 'nowatermark');

                if ($watermark) {

                    $repository = $app['EM']->getRepository('\Entities\BasketElement');

                    if (count($repository->findReceivedValidationElementsByRecord($record, $user)) > 0) {
                        $watermark = false;
                    } elseif (count($repository->findReceivedElementsByRecord($record, $user)) > 0) {
                        $watermark = false;
                    }
                }
                $response = $that->deliverContent($app['request'], $record, $subdef, $watermark, $stamp, $app);

                $linkToCaption = $app->url("permalinks_caption", array('sbas_id' => $sbas_id, 'record_id' => $record_id, 'token' => $token));
                $response->headers->set('Link', $linkToCaption);

                return $response;
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

            $response = $that->deliverContent($app['request'], $record, $subdef, $watermark, $stamp, $app);

            $linkToCaption = $app->url("permalinks_caption", array('sbas_id' => $sbas_id, 'record_id' => $record_id, 'token' => $token));
            $response->headers->set('Link', $linkToCaption);

            return $response;
        };

        $controllers->get('/v1/{sbas_id}/{record_id}/caption/', function(PhraseaApplication $app, Request $request, $sbas_id, $record_id) {
            $token = $request->query->get('token');

            $databox = $app['phraseanet.appbox']->get_databox((int) $sbas_id);

            $record = \media_Permalink_Adapter::challenge_token($app, $databox, $token, $record_id, 'thumbnail');
            if (null === $record) {
                throw new NotFoundHttpException("Caption not found");
            }
            $caption = $record->get_caption();

            return new Response($caption->serialize(\caption_record::SERIALIZE_JSON), 200, array("Content-Type" => 'application/json'));
        })
            ->assert('sbas_id', '\d+')->assert('record_id', '\d+')
            ->bind('permalinks_caption');

        $controllers->get('/v1/{sbas_id}/{record_id}/{subdef}/', function (PhraseaApplication $app, Request $request, $sbas_id, $record_id, $subdef) use ($deliverPermaview) {
            $token = $request->query->get('token');

            return $deliverPermaview($sbas_id, $record_id, $token, $subdef, $app);
        })
            ->bind('permalinks_permaview')
            ->assert('sbas_id', '\d+')
            ->assert('record_id', '\d+');

        $controllers->get('/v1/{label}/{sbas_id}/{record_id}/{token}/{subdef}/view/', function(PhraseaApplication $app, $label, $sbas_id, $record_id, $token, $subdef) use ($deliverPermaview) {
            return $deliverPermaview($sbas_id, $record_id, $token, $subdef, $app);
        })
            ->bind('permalinks_permaview_old')
            ->assert('sbas_id', '\d+')
            ->assert('record_id', '\d+');

        $controllers->get('/v1/{sbas_id}/{record_id}/{subdef}/{label}', function (PhraseaApplication $app, Request $request, $sbas_id, $record_id, $subdef, $label) use ($deliverPermalink) {
            $token = $request->query->get('token');

            return $deliverPermalink($app, $sbas_id, $record_id, $token, $subdef);
        })
            ->bind('permalinks_permalink')
            ->assert('sbas_id', '\d+')
            ->assert('record_id', '\d+');

        $controllers->get('/v1/{label}/{sbas_id}/{record_id}/{token}/{subdef}/', function(PhraseaApplication $app, $label, $sbas_id, $record_id, $token, $subdef) use ($deliverPermalink) {
            return $deliverPermalink($app, $sbas_id, $record_id, $token, $subdef);
        })
            ->bind('permalinks_permalink_old')
            ->assert('sbas_id', '\d+')
            ->assert('record_id', '\d+');

        return $controllers;
    }
}
