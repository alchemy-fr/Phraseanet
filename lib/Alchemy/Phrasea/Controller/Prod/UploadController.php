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
            'id' => '',
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

        $file = current($request->files->get('files'));

        if (!$file->isValid()) {
            throw new BadRequestHttpException('Uploaded file is invalid');
        }

        try {
            // Add file extension, so mediavorus can guess file type for octet-stream file
            $uploadedFilename = $file->getRealPath();
            $renamedFilename = $file->getRealPath() . '.' . pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION);

            $this->getFilesystem()->rename($uploadedFilename, $renamedFilename);

            $media = $this->app->getMediaFromUri($renamedFilename);
            $collection = \collection::getByBaseId($this->app, $base_id);

            $lazaretSession = new LazaretSession();
            $lazaretSession->setUser($this->getAuthenticatedUser());

            $this->getEntityManager()->persist($lazaretSession);

            $packageFile = new File($this->app, $media, $collection, $file->getClientOriginalName());

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

            $this->getFilesystem()->rename($renamedFilename, $uploadedFilename);

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

                        $this->getSubDefinitionSubstituer()->substitute($elementCreated, 'thumbnail', $media);
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

        foreach ($acl->get_granted_base([\ACL::CANADDRECORD]) as $collection) {
            $databox = $collection->get_databox();

            if ( ! isset($collections[$databox->get_sbas_id()])) {
                $collections[$databox->get_sbas_id()] = [
                    'databox'             => $databox,
                    'databox_collections' => []
                ];
            }

            $collections[$databox->get_sbas_id()]['databox_collections'][] = $collection;
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

        switch (strtolower(substr($postMaxSize, -1))) {
            /** @noinspection PhpMissingBreakStatementInspection */
            case 'g':
                $postMaxSize *= 1024;
            /** @noinspection PhpMissingBreakStatementInspection */
            case 'm':
                $postMaxSize *= 1024;
            case 'k':
                $postMaxSize *= 1024;
        }

        return min(UploadedFile::getMaxFilesize(), (int) $postMaxSize);
    }
}
