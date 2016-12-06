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
use Alchemy\Embed\Media\MediaInformation;
use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Application\Helper\ApplicationBoxAware;
use Alchemy\Phrasea\Authentication\ACLProvider;
use Alchemy\Phrasea\Authentication\Authenticator;
use Alchemy\Phrasea\Core\Event\ExportEvent;
use Alchemy\Phrasea\Core\PhraseaEvents;
use Alchemy\Phrasea\Model\Repositories\BasketElementRepository;
use Alchemy\Phrasea\Model\Serializer\CaptionSerializer;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PermalinkController extends AbstractDelivery
{
    use ApplicationBoxAware;

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
        $databox = $this->findDataboxById($sbas_id);
        $token = $request->query->get('token');
        $record = $this->retrieveRecord($databox, $token, $record_id, $request->get('subdef', 'thumbnail'));

        if (null === $record) {
            throw new NotFoundHttpException("Record not found");
        }

        return new Response('', 200, ['Allow' => 'GET, HEAD, OPTIONS']);
    }

    public function deliverCaption(Request $request, $sbas_id, $record_id)
    {
        $databox = $this->findDataboxById($sbas_id);
        $token = $request->query->get('token');
        $record = $this->retrieveRecord($databox, $token, $record_id, \databox_subdef::CLASS_THUMBNAIL);

        if (null === $record) {
            throw new NotFoundHttpException("Caption not found");
        }
        $caption = $record->get_caption();

        return new Response($this->app['serializer.caption']->serialize($caption, CaptionSerializer::SERIALIZE_JSON), 200, ["Content-Type" => 'application/json']);
    }

    public function deliverPermaview(Request $request, $sbas_id, $record_id, $subdef)
    {
        return $this->doDeliverPermaview($sbas_id, $record_id, $request->query->get('token'), $subdef);
    }

    private function doDeliverPermaview($sbas_id, $record_id, $token, $subdefName)
    {
        $databox = $this->findDataboxById($sbas_id);
        $record = $this->retrieveRecord($databox, $token, $record_id, $subdefName);
        $subdef = $record->get_subdef($subdefName);

        $information = $this->mediaService->createMediaInformationFromResourceAndRoute(
            $subdef,
            'permalinks_permalink',
            [
                'sbas_id'   => $sbas_id,
                'record_id' => $record_id,
                'subdef'    => $subdefName,
                'label'     => str_replace('/', '_', $record->get_title()),
                'token'     => $token,
            ]
        );
        $metaData = $this->mediaService->getMetaData($information);

        return $this->app['twig']->render('overview.html.twig', [
            'ogMetaData'  => $metaData['ogMetaData'],
            'subdef'      => $subdef,
            'module_name' => 'overview',
            'module'      => 'overview',
            'view'        => 'overview',
            'token'       => $token,
            'record'      => $record,
            'recordUrl'   => $information->getUrl(),
        ]);
    }

    public function deliverPermaviewOldWay(Request $request, $sbas_id, $record_id, $token, $subdef)
    {
        return $this->doDeliverPermaview($sbas_id, $record_id, $token, $subdef);
    }

    public function deliverPermalink(Request $request, $sbas_id, $record_id, $subdef)
    {
        return $this->doDeliverPermalink($request, $sbas_id, $record_id, $request->query->get('token'), $subdef);
    }

    private function doDeliverPermalink(Request $request, $sbas_id, $record_id, $token, $subdef)
    {
        $databox = $this->findDataboxById($sbas_id);
        $record = $this->retrieveRecord($databox, $token, $record_id, $subdef);
        $watermark = $stamp = false;

        $isDownload = $request->query->getBoolean('download', false);

        if ($isDownload && $user = $this->app->getAuthenticatedUser()) {
            $this->getEventDispatcher()->dispatch(
                PhraseaEvents::EXPORT_CREATE,
                new ExportEvent($user, 0, $sbas_id . '_' . $record_id, [ $subdef ], '')
            );
        }

        if ($this->authentication->isAuthenticated()) {
            $watermark = !$this->acl->get($this->authentication->getUser())->has_right_on_base($record->getBaseId(), \ACL::NOWATERMARK);

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

        $collection = \collection::getByBaseId($this->app, $record->getBaseId());

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
            'sbas_id'   => $record->getDataboxId(),
            'record_id' => $record->getRecordId(),
            'token'     => $token,
        ]));

        return $response;
    }

    public function deliverPermalinkOldWay(Request $request, $sbas_id, $record_id, $token, $subdef)
    {
        return $this->doDeliverPermalink($request, $sbas_id, $record_id, $token, $subdef);
    }

    /**
     * @param \databox $databox
     * @param string   $token
     * @param int      $record_id
     * @param string   $subdef
     * @return \record_adapter
     */
    private function retrieveRecord(\databox $databox, $token, $record_id, $subdef)
    {
        try {
            $record = $databox->get_record($record_id);
            $subDefinition = $record->get_subdef($subdef);
            $permalink = $subDefinition->get_permalink();
        } catch (\Exception $exception) {
            throw new NotFoundHttpException('Wrong token.', $exception);
        }

        if (null === $permalink || !$permalink->get_is_activated()) {
            throw new NotFoundHttpException('This token has been disabled.');
        }

        $feedItemsRepository = $this->app['repo.feed-items'];
        if (in_array($subdef, [\databox_subdef::CLASS_PREVIEW, \databox_subdef::CLASS_THUMBNAIL])
            && $feedItemsRepository->isRecordInPublicFeed($databox->get_sbas_id(), $record_id)
        ) {
            return $record;
        } elseif ($permalink->get_token() == (string)$token) {
            return $record;
        }

        throw new NotFoundHttpException('Wrong token.');
    }

    /**
     * @return EventDispatcherInterface
     */
    private function getEventDispatcher()
    {
        return $this->app['dispatcher'];
    }
}
