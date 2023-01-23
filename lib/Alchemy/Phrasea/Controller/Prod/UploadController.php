<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Phrasea\Controller\Prod;

use Alchemy\Phrasea\Application\Helper\BorderManagerAware;
use Alchemy\Phrasea\Application\Helper\DataboxLoggerAware;
use Alchemy\Phrasea\Application\Helper\DispatcherAware;
use Alchemy\Phrasea\Application\Helper\EntityManagerAware;
use Alchemy\Phrasea\Application\Helper\FilesystemAware;
use Alchemy\Phrasea\Application\Helper\SubDefinitionSubstituerAware;
use Alchemy\Phrasea\Border\Attribute\Status;
use Alchemy\Phrasea\Border\File;
use Alchemy\Phrasea\Border\Visa;
use Alchemy\Phrasea\Controller\Controller;
use Alchemy\Phrasea\Core\Event\LazaretEvent;
use Alchemy\Phrasea\Core\Event\RecordEdit;
use Alchemy\Phrasea\Core\PhraseaEvents;
use Alchemy\Phrasea\Model\Entities\LazaretFile;
use Alchemy\Phrasea\Model\Entities\LazaretSession;
use DataURI\Exception\Exception as DataUriException;
use DataURI\Parser;
use GuzzleHttp\Client as Guzzle;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class UploadController extends Controller
{
    use BorderManagerAware;
    use DataboxLoggerAware;
    use DispatcherAware;
    use EntityManagerAware;
    use FilesystemAware;
    use SubDefinitionSubstituerAware;

    public function getFlashUploadForm()
    {
        $maxFileSize = $this->getUploadMaxFileSize();

        return $this->render('prod/upload/upload-flash.html.twig', [
            'sessionId'           => session_id(),
            'collections'         => $this->getGrantedCollections($this->getAclForUser()),
            'maxFileSize'         => $maxFileSize,
            'maxFileSizeReadable' => \p4string::format_octets($maxFileSize)
        ]);
    }

    public function getHtml5UploadForm()
    {
        $maxFileSize = $this->getUploadMaxFileSize();

        return $this->render('prod/upload/upload.html.twig', [
            'sessionId'           => session_id(),
            'collections'         => $this->getGrantedCollections($this->getAclForUser()),
            'maxFileSize'         => $maxFileSize,
            'maxFileSizeReadable' => \p4string::format_octets($maxFileSize)
        ]);
    }

    public function getUploadForm()
    {
        $maxFileSize = $this->getUploadMaxFileSize();

        return $this->render('prod/upload/upload.html.twig', [
            'collections'         => $this->getGrantedCollections($this->getAclForUser()),
            'maxFileSize'         => $maxFileSize,
            'maxFileSizeReadable' => \p4string::format_octets($maxFileSize)
        ]);
    }

    public function getHead(Request $request)
    {
        $response = [
            'content-type' => null,
            'content-length' => null,
            'basename' => null
        ];
        try {
            $url = $request->get('url');
            $basename = pathinfo($url, PATHINFO_BASENAME);

            $guzzle = new Guzzle(['base_uri' => $url]);
            $res = $guzzle->head('');
            $response['content-type'] = $res->getHeaderLine('content-type');
            $response['content-length'] = doubleval($res->getHeaderLine('content-length'));
            $response['basename'] = $basename;
        }
        catch (\Exception $e) {
            // no-op : head will return no info but will not crash
        }

        return $this->app->json($response);
    }

    /**
     * Upload processus
     *
     * @param Request     $request The current request
     *
     * parameters   : 'bas_id'        int     (mandatory) :   The id of the destination collection
     *                'status'        array   (optional)  :   The status to set to new uploaded files
     *                'attributes'    array   (optional)  :   Attributes id's to attach to the uploaded files
     *                'forceBehavior' int     (optional)  :   Force upload behavior
     *                      - 0 Force record
     *                      - 1 Force lazaret
     *
     * @return Response
     */
    public function upload(Request $request)
    {
        $data = [
            'success' => false,
            'code'    => null,
            'message' => '',
            'element' => '',
            'reasons' => [],
            'id'      => '',
        ];

        if (null === $request->files->get('files')) {
            throw new BadRequestHttpException('Missing file parameter');
        }

        if (count($request->files->get('files')) > 1) {
            throw new BadRequestHttpException('Upload is limited to 1 file per request');
        }

        $base_id = $request->request->get('base_id');

        if (!$base_id) {
            throw new BadRequestHttpException('Missing base_id parameter');
        }

        if (!$this->getAclForUser()->has_right_on_base($base_id, \ACL::CANADDRECORD)) {
            throw new AccessDeniedHttpException('User is not allowed to add record on this collection');
        }

        /** @var UploadedFile $file */
        $file = current($request->files->get('files'));

        if (!$file->isValid()) {
            throw new BadRequestHttpException('Uploaded file is invalid');
        }

        if ($file->getClientOriginalName() === "blob" && $file->getClientMimeType() === "application/json") {

            // a "upload by url" was done, we receive a tiny json that contains url.
            $json = json_decode(file_get_contents($file->getRealPath()), true);
            $url = $json['url'];
            $pi = pathinfo($url);   // filename, extension

            $tempfile = $this->getTemporaryFilesystem()->createTemporaryFile('download_', null, $pi['extension']);

            try {
                $guzzle = new Guzzle(['base_uri' => $url]);
                $res = $guzzle->get("", ['save_to' => $tempfile]);
            }
            catch (\Exception $e) {
                throw new BadRequestHttpException(sprintf('Error "%s" downloading "%s"', $e->getMessage(), $url));
            }

            if($res->getStatusCode() !== 200) {
                throw new BadRequestHttpException(sprintf('Error %s downloading "%s"', $res->getStatusCode(), $url));
            }

            $uploadedFilename = $renamedFilename = $tempfile;

            $originalName = $pi['filename'] . '.' . $pi['extension'];

        } else {
            // Add file extension, so mediavorus can guess file type for octet-stream file
            $uploadedFilename = $file->getRealPath();
            $renamedFilename = null;

            if(!empty($this->app['conf']->get(['main', 'storage', 'worker_tmp_files']))) {
                $tmpStorage = \p4string::addEndSlash($this->app['conf']->get(['main', 'storage', 'worker_tmp_files'])).'upload/';

                if(!is_dir($tmpStorage)){
                    $this->getFilesystem()->mkdir($tmpStorage);
                }

                $renamedFilename = $tmpStorage. pathinfo($file->getRealPath(), PATHINFO_FILENAME) .'.' . pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION);

            } else {
                $renamedFilename = $file->getRealPath() . '.' . pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION);
            }

            $this->getFilesystem()->rename($uploadedFilename, $renamedFilename);

            $originalName = $file->getClientOriginalName();
        }

        try {
            $media = $this->app->getMediaFromUri($renamedFilename);
            $collection = \collection::getByBaseId($this->app, $base_id);

            $lazaretSession = new LazaretSession();
            $lazaretSession->setUser($this->getAuthenticatedUser());

            $this->getEntityManager()->persist($lazaretSession);

            $packageFile = new File($this->app, $media, $collection, $originalName);

            $postStatus = $request->request->get('status');

            if (isset($postStatus[$collection->get_base_id()]) && is_array($postStatus[$collection->get_base_id()])) {
                $postStatus = $postStatus[$collection->get_base_id()];

                $status = '';
                foreach (range(0, 31) as $i) {
                    $status .= isset($postStatus[$i]) ? ($postStatus[$i] ? '1' : '0') : '0';
                }
                $packageFile->addAttribute(new Status($this->app, strrev($status)));
            }

            $forceBehavior = $request->request->get('forceAction');

            $reasons = [];
            $elementCreated = null;

            $callback = function ($element, Visa $visa) use (&$reasons, &$elementCreated) {
                foreach ($visa->getResponses() as $response) {
                    if (!$response->isOk()) {
                        $reasons[] = $response->getMessage($this->app['translator']);
                    }
                }

                $elementCreated = $element;
            };

            $code = $this->getBorderManager()->process( $lazaretSession, $packageFile, $callback, $forceBehavior);

            if($renamedFilename !== $uploadedFilename) {
                $this->getFilesystem()->rename($renamedFilename, $uploadedFilename);
            }

            if (!!$forceBehavior) {
                $reasons = [];
            }

            if ($elementCreated instanceof \record_adapter) {
                $id = $elementCreated->getId();
                $element = 'record';
                $message = $this->app->trans('The record was successfully created');

                $this->dispatch(PhraseaEvents::RECORD_UPLOAD, new RecordEdit($elementCreated));

                // try to create thumbnail from data URI
                if ('' !== $b64Image = $request->request->get('b64_image', '')) {
                    try {
                        $dataUri = Parser::parse($b64Image);

                        $fileName = $this->getTemporaryFilesystem()->createTemporaryFile('base_64_thumb', null, "png");
                        file_put_contents($fileName, $dataUri->getData());

                        $media = $this->app->getMediaFromUri($fileName);

                        $this->getSubDefinitionSubstituer()->substituteSubdef($elementCreated, 'thumbnail', $media);
                        $this->getDataboxLogger($elementCreated->getDatabox())
                            ->log($elementCreated, \Session_Logger::EVENT_SUBSTITUTE, 'thumbnail', '');

                        unset($media);
                        $this->getTemporaryFilesystem()->clean('base_64_thumb');
                    } catch (DataUriException $e) {

                    }
                }
            } else {
                /** @var LazaretFile $elementCreated */
                $this->dispatch(PhraseaEvents::LAZARET_CREATE, new LazaretEvent($elementCreated));

                $id = $elementCreated->getId();
                $element = 'lazaret';
                $message = $this->app->trans('The file was moved to the quarantine');
            }

            $data = [
                'success' => true,
                'code'    => $code,
                'message' => $message,
                'element' => $element,
                'reasons' => $reasons,
                'id'      => $id,
            ];
        } catch (\Exception $e) {
            $data['message'] = $this->app->trans('Unable to add file to Phraseanet');
        }

        $response = $this->app->json($data);
        // IE 7 and 8 does not correctly handle json response in file API
        // lets send them an html content-type header
        $response->headers->set('Content-type', 'text/html');

        return $response;
    }

    /**
     * Get current user's granted collections where he can upload
     *
     * @param \ACL $acl The user's ACL.
     *
     * @return array
     */
    private function getGrantedCollections(\ACL $acl)
    {
        $collections = [];

        foreach ($acl->get_granted_sbas() as $databox) {
            $sbasId = $databox->get_sbas_id();
            foreach ($acl->get_granted_base([\ACL::CANADDRECORD], [$sbasId]) as $collection) {
                $databox = $collection->get_databox();
                if (!isset($collections[$sbasId])) {
                    $collections[$databox->get_sbas_id()] = [
                        'databox'             => $databox,
                        'databox_collections' => []
                    ];
                }
                $collections[$databox->get_sbas_id()]['databox_collections'][] = $collection;
                /** @var DisplaySettingService $settings */
                $settings = $this->app['settings'];
                $userOrderSetting = $settings->getUserSetting($this->app->getAuthenticatedUser(), 'order_collection_by');
                // a temporary array to sort the collections
                $aName = [];
                list($ukey, $uorder) = ["order", SORT_ASC];     // default ORDER_BY_ADMIN
                switch ($userOrderSetting) {
                    case $settings::ORDER_ALPHA_ASC :
                        list($ukey, $uorder) = ["name", SORT_ASC];
                        break;
                    case $settings::ORDER_ALPHA_DESC :
                        list($ukey, $uorder) = ["name", SORT_DESC];
                        break;
                }
                foreach ($collections[$databox->get_sbas_id()]['databox_collections'] as $key => $row) {
                    if ($ukey == "order") {
                        $aName[$key] = $row->get_ord();
                    }
                    else {
                        $aName[$key] = $row->get_name();
                    }
                }
                // sort the collections
                array_multisort($aName, $uorder, SORT_REGULAR, $collections[$databox->get_sbas_id()]['databox_collections']);
            }
        }
        return $collections;

    }

    /**
     * Get POST max file size
     *
     * @return integer
     */
    private function getUploadMaxFileSize()
    {
        $postMaxSize = trim(ini_get('post_max_size'));

        if ('' === $postMaxSize) {
            $postMaxSize = PHP_INT_MAX;
        }

        $r = 0;
        switch (strtolower(substr($postMaxSize, -1))) {
            /** @noinspection PhpMissingBreakStatementInspection */
            case 'g':
                $r += 10;
            /** @noinspection PhpMissingBreakStatementInspection */
            case 'm':
                $r += 10;
            case 'k':
                $r += 10;
                $postMaxSize = ((int)($postMaxSize))<<$r;
        }

        return min(UploadedFile::getMaxFilesize(), (int) $postMaxSize);
    }
}
