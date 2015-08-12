<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
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
        $app['controller.permalink'] = $this;

        $controllers = $app['controllers_factory'];

        $controllers->get('/v1/{sbas_id}/{record_id}/caption/', 'controller.permalink:deliverCaption')
                    ->assert('sbas_id', '\d+')->assert('record_id', '\d+')
                    ->bind('permalinks_caption');

        $controllers->match('/v1/{sbas_id}/{record_id}/caption/', 'controller.permalink:getOptionsResponse')
                    ->assert('sbas_id', '\d+')->assert('record_id', '\d+')
                    ->method('OPTIONS');

        $controllers->get('/v1/{sbas_id}/{record_id}/{subdef}/', 'controller.permalink:deliverPermaview')
                    ->bind('permalinks_permaview')
                    ->assert('sbas_id', '\d+')
                    ->assert('record_id', '\d+');

        $controllers->match('/v1/{sbas_id}/{record_id}/{subdef}/', 'controller.permalink:getOptionsResponse')
                    ->method('OPTIONS')
                    ->assert('sbas_id', '\d+')
                    ->assert('record_id', '\d+');

        $controllers->get('/v1/{label}/{sbas_id}/{record_id}/{token}/{subdef}/view/', 'controller.permalink:deliverPermaviewOldWay')
                    ->bind('permalinks_permaview_old')
                    ->assert('sbas_id', '\d+')
                    ->assert('record_id', '\d+');

        $controllers->get('/v1/{sbas_id}/{record_id}/{subdef}/{label}', 'controller.permalink:deliverPermalink')
                    ->bind('permalinks_permalink')
                    ->assert('sbas_id', '\d+')
                    ->assert('record_id', '\d+');

        $controllers->match('/v1/{sbas_id}/{record_id}/{subdef}/{label}', 'controller.permalink:getOptionsResponse')
                    ->method('OPTIONS')
                    ->assert('sbas_id', '\d+')
                    ->assert('record_id', '\d+');

        $controllers->get('/v1/{label}/{sbas_id}/{record_id}/{token}/{subdef}/', 'controller.permalink:deliverPermalinkOldWay')
                    ->bind('permalinks_permalink_old')
                    ->assert('sbas_id', '\d+')
                    ->assert('record_id', '\d+');

        return $controllers;
    }

    public function getOptionsResponse(PhraseaApplication $app, Request $request, $sbas_id, $record_id)
    {
        $databox = $app['phraseanet.appbox']->get_databox((int) $sbas_id);

        $record = $this->retrieveRecord($app, $databox, $request->query->get('token'), $record_id, $request->get('subdef', 'thumbnail'));

        if (null === $record) {
            throw new NotFoundHttpException("Record not found");
        }

        return new Response('', 200, array('Allow' => 'GET, HEAD, OPTIONS'));
    }

    public function deliverCaption(PhraseaApplication $app, Request $request, $sbas_id, $record_id)
    {
        $token = $request->query->get('token');

        $databox = $app['phraseanet.appbox']->get_databox((int) $sbas_id);

        $record = \media_Permalink_Adapter::challenge_token($app, $databox, $token, $record_id, 'thumbnail');
        if (null === $record) {
            throw new NotFoundHttpException("Caption not found");
        }
        $caption = $record->get_caption();

        return new Response($caption->serialize(\caption_record::SERIALIZE_JSON), 200, array("Content-Type" => 'application/json'));
    }

    public function deliverPermaview(PhraseaApplication $app, Request $request, $sbas_id, $record_id, $subdef)
    {
        return $this->doDeliverPermaview($sbas_id, $record_id, $request->query->get('token'), $subdef, $app);
    }

    public function deliverPermaviewOldWay(PhraseaApplication $app, $label, $sbas_id, $record_id, $token, $subdef)
    {
        return $this->doDeliverPermaview($sbas_id, $record_id, $token, $subdef, $app);
    }

    public function deliverPermalink(PhraseaApplication $app, Request $request, $sbas_id, $record_id, $subdef, $label)
    {
        return $this->doDeliverPermalink($app, $sbas_id, $record_id, $request->query->get('token'), $subdef);
    }

    public function deliverPermalinkOldWay(PhraseaApplication $app, $label, $sbas_id, $record_id, $token, $subdef)
    {
        return $this->doDeliverPermalink($app, $sbas_id, $record_id, $token, $subdef);
    }

    /**
     * @param PhraseaApplication $app
     * @param \databox           $databox
     * @param string             $token
     * @param int                $record_id
     * @param string             $subdef
     * @return \record_adapter
     */
    private function retrieveRecord($app, $databox, $token, $record_id, $subdef)
    {
        try {
            $record = new \record_adapter($app, $databox->get_sbas_id(), $record_id);
            $subDefinition = new \media_subdef($app, $record, $subdef);
            $permalink = new \media_Permalink_Adapter($app, $databox, $subDefinition);
        } catch (\Exception $exception) {
            throw new NotFoundHttpException('Wrong token.', $exception);
        }

        if (!$permalink->get_is_activated()) {
            throw new NotFoundHttpException('This token has been disabled.');
        }

        if (in_array($subdef, array(\databox_subdef::CLASS_PREVIEW, \databox_subdef::CLASS_THUMBNAIL))
            && \Feed_Entry_Item::is_record_in_public_feed($app, $databox->get_sbas_id(), $record_id)) {
            return $record;
        } elseif ($permalink->get_token() == (string) $token) {
            return $record;
        }

        throw new NotFoundHttpException('Wrong token.');
    }

    private function doDeliverPermaview($sbas_id, $record_id, $token, $subdef, PhraseaApplication $app)
    {
        $databox = $app['phraseanet.appbox']->get_databox((int) $sbas_id);

        $record = $this->retrieveRecord($app, $databox, $token, $record_id, $subdef);

        return $app['twig']->render('overview.html.twig', array(
            'subdef_name' => $subdef,
            'module_name' => 'overview',
            'module'      => 'overview',
            'view'        => 'overview',
            'record'      => $record,
        ));
    }

    private function doDeliverPermalink(PhraseaApplication $app, $sbas_id, $record_id, $token, $subdef)
    {
        $databox = $app['phraseanet.appbox']->get_databox((int) $sbas_id);

        $record = $this->retrieveRecord($app, $databox, $token, $record_id, $subdef);

        $watermark = $stamp = false;

        if ($app['authentication']->isAuthenticated()) {
            $user = \User_Adapter::getInstance($app['authentication']->getUser()->get_id(), $app);

            $watermark = ! $user->ACL()->has_right_on_base($record->get_base_id(), 'nowatermark');

            if ($watermark) {

                $repository = $app['EM']->getRepository('\Entities\BasketElement');

                if (count($repository->findReceivedValidationElementsByRecord($record, $user)) > 0) {
                    $watermark = false;
                } elseif (count($repository->findReceivedElementsByRecord($record, $user)) > 0) {
                    $watermark = false;
                }
            }
            return $this->deliverContentWithCaptionLink($app, $app['request'], $record, $subdef, $watermark, $stamp, $token);
        }

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
                $watermark = true;
                break;
        }

        return $this->deliverContentWithCaptionLink($app, $app['request'], $record, $subdef, $watermark, $stamp, $token);
    }

    /**
     * @param PhraseaApplication $app
     * @param Request            $request
     * @param \record_adapter    $record
     * @param string             $subdef
     * @param bool               $watermark
     * @param bool               $stamp
     * @param string             $token
     * @return Response
     */
    private function deliverContentWithCaptionLink(PhraseaApplication $app, Request $request, \record_adapter $record, $subdef, $watermark, $stamp, $token)
    {
        $response = $this->deliverContent($request, $record, $subdef, $watermark, $stamp, $app);

        $response->headers->set('Link', $app->url('permalinks_caption', [
            'sbas_id' => $record->get_sbas_id(),
            'record_id' => $record->get_record_id(),
            'token' => $token,
        ]));

        return $response;
    }
}
