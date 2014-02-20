<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Prod;

use Alchemy\Phrasea\Border\File;
use Alchemy\Phrasea\Border\Attribute\Status;
use DataURI\Parser;
use DataURI\Exception\Exception as DataUriException;
use Alchemy\Phrasea\Model\Entities\LazaretSession;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class Upload implements ControllerProviderInterface
{
    /**
     * Connect the ControllerCollection to the Silex Application
     *
     * @param  Application                 $app A silex application
     * @return \Silex\ControllerCollection
     */
    public function connect(Application $app)
    {
        $app['controller.prod.upload'] = $this;

        $controllers = $app['controllers_factory'];

        $app['firewall']->addMandatoryAuthentication($controllers);

        $controllers->before(function (Request $request) use ($app) {
            $app['firewall']->requireRight('addrecord');
        });

        $controllers->get('/', 'controller.prod.upload:getUploadForm')
            ->bind('upload_form');

        $controllers->get('/flash-version/', 'controller.prod.upload:getFlashUploadForm')
            ->bind('upload_flash_form');

        $controllers->post('/', 'controller.prod.upload:upload')
            ->bind('upload');

        return $controllers;
    }

    /**
     * Render the flash upload form
     *
     * @param Application $app     A Silex application
     * @param Request     $request The current request
     *
     * @return Response
     */
    public function getFlashUploadForm(Application $app, Request $request)
    {
        $maxFileSize = $this->getUploadMaxFileSize();

        return $app['twig']->render(
            'prod/upload/upload-flash.html.twig', [
            'sessionId'           => session_id(),
            'collections'         => $this->getGrantedCollections($app['acl']->get($app['authentication']->getUser())),
            'maxFileSize'         => $maxFileSize,
            'maxFileSizeReadable' => \p4string::format_octets($maxFileSize)
        ]);
    }

    /**
     * Render the html upload form
     *
     * @param Application $app     A Silex application
     * @param Request     $request The current request
     *
     * @return Response
     */
    public function getUploadForm(Application $app, Request $request)
    {
        $maxFileSize = $this->getUploadMaxFileSize();

        return $app['twig']->render(
            'prod/upload/upload.html.twig', [
            'collections'         => $this->getGrantedCollections($app['acl']->get($app['authentication']->getUser())),
            'maxFileSize'         => $maxFileSize,
            'maxFileSizeReadable' => \p4string::format_octets($maxFileSize)
        ]);
    }

    /**
     * Upload processus
     *
     * @param Application $app     The Silex application
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
    public function upload(Application $app, Request $request)
    {
        $datas = [
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

        if (!$app['acl']->get($app['authentication']->getUser())->has_right_on_base($base_id, 'canaddrecord')) {
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

            $app['filesystem']->rename($uploadedFilename, $renamedFilename);

            $media = $app['mediavorus']->guess($renamedFilename);
            $collection = \collection::get_from_base_id($app, $base_id);

            $lazaretSession = new LazaretSession();
            $lazaretSession->setUser($app['authentication']->getUser());

            $app['EM']->persist($lazaretSession);

            $packageFile = new File($app, $media, $collection, $file->getClientOriginalName());

            $postStatus = $request->request->get('status');

            if (isset($postStatus[$collection->get_base_id()]) && is_array($postStatus[$collection->get_base_id()])) {
                $postStatus = $postStatus[$collection->get_base_id()];

                $status = '';
                foreach (range(0, 31) as $i) {
                    $status .= isset($postStatus[$i]) ? ($postStatus[$i] ? '1' : '0') : '0';
                }
                $packageFile->addAttribute(new Status($app, strrev($status)));
            }

            $forceBehavior = $request->request->get('forceAction');

            $reasons = [];
            $elementCreated = null;

            $callback = function ($element, $visa, $code) use ($app, &$reasons, &$elementCreated) {
                    foreach ($visa->getResponses() as $response) {
                        if (!$response->isOk()) {
                            $reasons[] = $response->getMessage($app['translator']);
                        }
                    }

                    $elementCreated = $element;
                };

            $code = $app['border-manager']->process(
                $lazaretSession, $packageFile, $callback, $forceBehavior
            );

            $app['filesystem']->rename($renamedFilename, $uploadedFilename);

            if (!!$forceBehavior) {
                $reasons = [];
            }

            if ($elementCreated instanceof \record_adapter) {
                $id = $elementCreated->get_serialize_key();
                $element = 'record';
                $message = $app->trans('The record was successfully created');
                $app['phraseanet.SE']->addRecord($elementCreated);

                // try to create thumbnail from data URI
                if ('' !== $b64Image = $request->request->get('b64_image', '')) {
                    try {
                        $dataUri = Parser::parse($b64Image);

                        $fileName = $app['temporary-filesystem']->createTemporaryFile('base_64_thumb', null, "png");
                        file_put_contents($fileName, $dataUri->getData());
                        $media = $app['mediavorus']->guess($fileName);
                        $app['subdef.substituer']->substitute($elementCreated, 'thumbnail', $media);
                        $app['phraseanet.logger']($elementCreated->get_databox())->log(
                            $elementCreated,
                            \Session_Logger::EVENT_SUBSTITUTE,
                            'thumbnail',
                            ''
                        );

                        unset($media);
                        $app['temporary-filesystem']->clean('base_64_thumb');
                    } catch (DataUriException $e) {

                    }
                }
            } else {
                $params = ['lazaret_file' => $elementCreated];

                $app['events-manager']->trigger('__UPLOAD_QUARANTINE__', $params);

                $id = $elementCreated->getId();
                $element = 'lazaret';
                $message = $app->trans('The file was moved to the quarantine');
            }

            $datas = [
                'success' => true,
                'code'    => $code,
                'message' => $message,
                'element' => $element,
                'reasons' => $reasons,
                'id'      => $id,
            ];
        } catch (\Exception $e) {
            $datas['message'] = $app->trans('Unable to add file to Phraseanet');
        }

        $response = $app->json($datas);
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

        foreach ($acl->get_granted_base(['canaddrecord']) as $collection) {
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
            case 'g':
                $postMaxSize *= 1024;
            case 'm':
                $postMaxSize *= 1024;
            case 'k':
                $postMaxSize *= 1024;
        }

        return min(UploadedFile::getMaxFilesize(), (int) $postMaxSize);
    }
}
