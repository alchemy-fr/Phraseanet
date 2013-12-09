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

class Permalink extends AbstractDelivery
{
    public function connect(Application $app)
    {
        $app['controller.permalink'] = $this;

        $controllers = $app['controllers_factory'];

        $that = $this;

        $retrieveRecord = function ($app, $databox, $token, $record_id, $subdef) {
            if (in_array($subdef, [\databox_subdef::CLASS_PREVIEW, \databox_subdef::CLASS_THUMBNAIL]) && $app['EM']->getRepository('Alchemy\Phrasea\Model\Entities\FeedItem')->isRecordInPublicFeed($app, $databox->get_sbas_id(), $record_id)) {
                $record = $databox->get_record($record_id);
            } else {
                $record = \media_Permalink_Adapter::challenge_token($app, $databox, $token, $record_id, $subdef);

                if (!($record instanceof \record_adapter)) {
                    throw new NotFoundHttpException('Wrong token.');
                }
            }

            return $record;
        };

        $deliverPermaview = function ($sbas_id, $record_id, $token, $subdef, PhraseaApplication $app) use ($retrieveRecord) {
            $databox = $app['phraseanet.appbox']->get_databox((int) $sbas_id);

            $record = $retrieveRecord($app, $databox, $token, $record_id, $subdef);

            $params = [
                'subdef_name' => $subdef
                , 'module_name' => 'overview'
                , 'module'      => 'overview'
                , 'view'        => 'overview'
                , 'record'      => $record
            ];

            return $app['twig']->render('overview.html.twig', $params);
        };

        $deliverPermalink = function (PhraseaApplication $app, $sbas_id, $record_id, $token, $subdef) use ($that, $retrieveRecord) {
            $databox = $app['phraseanet.appbox']->get_databox((int) $sbas_id);

            $record = $retrieveRecord($app, $databox, $token, $record_id, $subdef);

            $watermark = $stamp = false;

            if ($app['authentication']->isAuthenticated()) {
                $user = \User_Adapter::getInstance($app['authentication']->getUser()->get_id(), $app);

                $watermark = !$app['acl']->get($user)->has_right_on_base($record->get_base_id(), 'nowatermark');

                if ($watermark) {

                    $repository = $app['EM']->getRepository('Alchemy\Phrasea\Model\Entities\BasketElement');

                    if (count($repository->findReceivedValidationElementsByRecord($record, $user)) > 0) {
                        $watermark = false;
                    } elseif (count($repository->findReceivedElementsByRecord($record, $user)) > 0) {
                        $watermark = false;
                    }
                }
                $response = $that->deliverContent($app['request'], $record, $subdef, $watermark, $stamp, $app);

                $linkToCaption = $app->url("permalinks_caption", ['sbas_id' => $sbas_id, 'record_id' => $record_id, 'token' => $token]);
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

            $linkToCaption = $app->url("permalinks_caption", ['sbas_id' => $sbas_id, 'record_id' => $record_id, 'token' => $token]);
            $response->headers->set('Link', $linkToCaption);

            return $response;
        };

        $controllers->get('/v1/{sbas_id}/{record_id}/caption/', function (PhraseaApplication $app, Request $request, $sbas_id, $record_id) use ($retrieveRecord) {
            $token = $request->query->get('token');

            $databox = $app['phraseanet.appbox']->get_databox((int) $sbas_id);
            $record = $retrieveRecord($app, $databox, $token, $record_id, \databox_subdef::CLASS_THUMBNAIL);
            $caption = $record->get_caption();

            return new Response($caption->serialize(\caption_record::SERIALIZE_JSON), 200, ["Content-Type" => 'application/json']);
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

        $controllers->get('/v1/{label}/{sbas_id}/{record_id}/{token}/{subdef}/view/', function (PhraseaApplication $app, $label, $sbas_id, $record_id, $token, $subdef) use ($deliverPermaview) {
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

        $controllers->get('/v1/{label}/{sbas_id}/{record_id}/{token}/{subdef}/', function (PhraseaApplication $app, $label, $sbas_id, $record_id, $token, $subdef) use ($deliverPermalink) {
            return $deliverPermalink($app, $sbas_id, $record_id, $token, $subdef);
        })
            ->bind('permalinks_permalink_old')
            ->assert('sbas_id', '\d+')
            ->assert('record_id', '\d+');

        return $controllers;
    }
}
