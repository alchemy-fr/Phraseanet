<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller;

use Alchemy\Embed\Media\Media;
use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Authentication\ACLProvider;
use Alchemy\Phrasea\Authentication\Authenticator;
use Alchemy\Phrasea\Model\Repositories\BasketElementRepository;
use Alchemy\Phrasea\Model\Serializer\CaptionSerializer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PermalinkController extends AbstractDelivery
{
    /** @var ACLProvider */
    private $acl;
    /** @var Authenticator */
    private $authentication;
    /** @var Media */
    private $mediaService;

    public function __construct(Application $app, ACLProvider $acl, Authenticator $authenticator, Media $mediaService)
    {
        parent::__construct($app);

        $this->acl = $acl;
        $this->authentication = $authenticator;
        $this->mediaService = $mediaService;
    }

    public function getOptionsResponse(Request $request, $sbas_id, $record_id)
    {
        $databox = $this->mediaService->getDatabox($sbas_id);
        $token = $request->query->get('token');
        $record = $this->mediaService->retrieveRecord($databox, $token, $record_id, $request->get('subdef', 'thumbnail'));

        if (null === $record) {
            throw new NotFoundHttpException("Record not found");
        }

        return new Response('', 200, ['Allow' => 'GET, HEAD, OPTIONS']);
    }

    public function deliverCaption(Request $request, $sbas_id, $record_id)
    {
        $databox = $this->mediaService->getDatabox($sbas_id);
        $token = $request->query->get('token');
        $record = $this->mediaService->retrieveRecord($databox, $token, $record_id, \databox_subdef::CLASS_THUMBNAIL);

        if (null === $record) {
            throw new NotFoundHttpException("Caption not found");
        }
        $caption = $record->get_caption();

        return new Response($this->app['serializer.caption']->serialize($caption, CaptionSerializer::SERIALIZE_JSON), 200, ["Content-Type" => 'application/json']);
    }

    public function deliverPermaview(Request $request, $sbas_id, $record_id, $subdef)
    {
        return $this->doDeliverPermaview($request, $sbas_id, $record_id, $request->query->get('token'), $subdef);
    }

    private function doDeliverPermaview(Request $request, $sbas_id, $record_id, $token, $subdefName)
    {

        $databox = $this->mediaService->getDatabox($sbas_id);
        $record = $this->mediaService->retrieveRecord($databox, $token, $record_id, $subdefName);
        $metaData = $this->mediaService->getMetaData($request, $record, $subdefName);
        $subdef = $record->get_subdef($subdefName);

        return $this->app['twig']->render('overview.html.twig', [
            'ogMetaData'  => $metaData['ogMetaData'],
            'subdef'      => $subdef,
            'module_name' => 'overview',
            'module'      => 'overview',
            'view'        => 'overview',
            'token'       => $token,
            'record'      => $record,
            'recordUrl'   => $this->app->url('permalinks_permalink', [
                'sbas_id' => $sbas_id,
                'record_id' => $record_id,
                'subdef' => $subdefName,
                'label' => $record->get_title(),
                'token' => $token,
            ])
        ]);
    }

    public function deliverPermaviewOldWay(Request $request, $sbas_id, $record_id, $token, $subdef)
    {
        return $this->doDeliverPermaview($request, $sbas_id, $record_id, $token, $subdef);
    }

    public function deliverPermalink(Request $request, $sbas_id, $record_id, $subdef)
    {
        return $this->doDeliverPermalink($request, $sbas_id, $record_id, $request->query->get('token'), $subdef);
    }

    private function doDeliverPermalink(Request $request, $sbas_id, $record_id, $token, $subdef)
    {
        $databox = $this->mediaService->getDatabox($sbas_id);
        $record = $this->mediaService->retrieveRecord($databox, $token, $record_id, $subdef);
        $watermark = $stamp = false;

        if ($this->authentication->isAuthenticated()) {
            $watermark = !$this->acl->get($this->authentication->getUser())->has_right_on_base($record->get_base_id(), 'nowatermark');

            if ($watermark) {
                /** @var BasketElementRepository $repository */
                $repository = $this->app['repo.basket-elements'];

                if (count($repository->findReceivedValidationElementsByRecord($record, $this->authentication->getUser())) > 0) {
                    $watermark = false;
                } elseif (count($repository->findReceivedElementsByRecord($record, $this->authentication->getUser())) > 0) {
                    $watermark = false;
                }
            }

            return $this->deliverContentWithCaptionLink($request, $record, $subdef, $watermark, $stamp, $token);
        }

        $collection = \collection::getByBaseId($this->app, $record->get_base_id());
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

        return $this->deliverContentWithCaptionLink($request, $record, $subdef, $watermark, $stamp, $token);
    }

    /**
     * @param Request         $request
     * @param \record_adapter $record
     * @param string          $subdef
     * @param bool            $watermark
     * @param bool            $stamp
     * @param string          $token
     * @return Response
     */
    private function deliverContentWithCaptionLink(Request $request, \record_adapter $record, $subdef, $watermark, $stamp, $token)
    {
        $response = $this->deliverContent($request, $record, $subdef, $watermark, $stamp);

        $response->headers->set('Link', $this->app->url("permalinks_caption", [
            'sbas_id'   => $record->get_sbas_id(),
            'record_id' => $record->get_record_id(),
            'token'     => $token,
        ]));

        return $response;
    }

    public function deliverPermalinkOldWay(Request $request, $sbas_id, $record_id, $token, $subdef)
    {
        return $this->doDeliverPermalink($request, $sbas_id, $record_id, $token, $subdef);
    }
}
