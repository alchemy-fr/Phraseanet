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

use Alchemy\Phrasea\Border\File;
use Alchemy\Phrasea\Border\Attribute\Status;
use Entities\LazaretSession;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Upload controller collection
 *
 * Defines routes related to the Upload process in phraseanet
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
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
        $controllers = $app['controllers_factory'];

        /**
         * Upload form route
         *
         * name         : upload_form
         *
         * description  : Render the html upload form
         *
         * method       : GET
         *
         * return       : HTML Response
         */
        $controllers->get('/', $this->call('getUploadForm'))
            ->bind('upload_form');

        /**
         * Flash upload form route
         *
         * name         : upload_flash_form
         *
         * description  : Render the html flash upload form
         *
         * method       : GET
         *
         * return       : HTML Response
         */
        $controllers->get('/flash-version/', $this->call('getFlashUploadForm'))
            ->bind('upload_flash_form');

        /**
         * UPLOAD route
         *
         * name         : upload
         *
         * description  : Initiate the upload process
         *
         * method       : POST
         *
         * parameters   : 'bas_id'        int     (mandatory) :   The id of the destination collection
         *                'status'        array   (optional)  :   The status to set to new uploaded files
         *                'attributes'    array   (optional)  :   Attributes id's to attach to the uploaded files
         *                'forceBehavior' int     (optional)  :   Force upload behavior
         *                      - 0 Force record
         *                      - 1 Force lazaret
         *
         * return       : JSON Response
         */
        $controllers->post('/', $this->call('upload'))
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
                'prod/upload/upload-flash.html.twig', array(
                'sessionId'           => session_id(),
                'collections'         => $this->getGrantedCollections($app['phraseanet.core']->getAuthenticatedUser()),
                'maxFileSize'         => $maxFileSize,
                'maxFileSizeReadable' => \p4string::format_octets($maxFileSize)
                )
        );
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
                'prod/upload/upload.html.twig', array(
                'collections'         => $this->getGrantedCollections($app['phraseanet.core']->getAuthenticatedUser()),
                'maxFileSize'         => $maxFileSize,
                'maxFileSizeReadable' => \p4string::format_octets($maxFileSize)
                )
        );
    }

    /**
     * Upload processus
     *
     * @param Application $app     The Silex application
     * @param Request     $request The current request
     *
     * @return Response
     */
    public function upload(Application $app, Request $request)
    {
        $datas = array(
            'success' => false,
            'code'    => null,
            'message' => '',
            'element' => '',
            'reasons' => array(),
            'id' => '',
        );

        if (null === $request->files->get('files')) {
            throw new \Exception_BadRequest('Missing file parameter');
        }

        if (count($request->files->get('files')) > 1) {
            throw new \Exception_BadRequest('Upload is limited to 1 file per request');
        }

        $base_id = $request->request->get('base_id');

        if ( ! $base_id) {
            throw new \Exception_BadRequest('Missing base_id parameter');
        }

        if ( ! $app['phraseanet.core']->getAuthenticatedUser()->ACL()->has_right_on_base($base_id, 'canaddrecord')) {
            throw new \Exception_Forbidden('User is not allowed to add record on this collection');
        }

        $file = current($request->files->get('files'));

        if ( ! $file->isValid()) {
            throw new \Exception_BadRequest('Uploaded file is invalid');
        }

        try {
            // Add file extension, so mediavorus can guess file type for octet-stream file
            $uploadedFilename = $file->getRealPath();
            $renamedFilename = $file->getRealPath() . '.' . pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION);

            $originalname = $file->getClientOriginalName();
            $clientMimeType = $file->getClientMimeType();
            $size = $file->getSize();
            $error = $file->getError();

            $app['phraseanet.core']['file-system']->rename($uploadedFilename, $renamedFilename);

            $file = new UploadedFile($renamedFilename, $originalname, $clientMimeType, $size, $error);

            $media = $app['phraseanet.core']['mediavorus']->guess($file);
            $collection = \collection::get_from_base_id($base_id);

            $lazaretSession = new LazaretSession();
            $lazaretSession->setUsrId($app['phraseanet.core']->getAuthenticatedUser()->get_id());

            $app['phraseanet.core']['EM']->persist($lazaretSession);

            $packageFile = new File($media, $collection, $file->getClientOriginalName());

            $postStatus = $request->request->get('status');

            if (isset($postStatus[$collection->get_base_id()]) && is_array($postStatus[$collection->get_base_id()])) {
                $postStatus = $postStatus[$collection->get_base_id()];

                $status = '';
                foreach (range(0, 63) as $i) {
                    $status .= isset($postStatus[$i]) ? ($postStatus[$i] ? '1' : '0') : '0';
                }
                $packageFile->addAttribute(new Status(strrev($status)));
            }

            $forceBehavior = $request->request->get('forceAction');

            $reasons = array();
            $elementCreated = null;

            $callback = function($element, $visa, $code) use (&$reasons, &$elementCreated) {
                    foreach ($visa->getResponses() as $response) {
                        if ( ! $response->isOk()) {
                            $reasons[] = $response->getMessage();
                        }
                    }

                    $elementCreated = $element;
                };

            $code = $app['phraseanet.core']['border-manager']->process(
                $lazaretSession, $packageFile, $callback, $forceBehavior
            );

            $app['phraseanet.core']['file-system']->rename($renamedFilename, $uploadedFilename);

            if ( ! ! $forceBehavior) {
                $reasons = array();
            }

            if ($elementCreated instanceof \record_adapter) {
                $id = $elementCreated->get_serialize_key();
                $element = 'record';
                $message = _('The record was successfully created');
            } else {
                $params = array('lazaret_file' => $elementCreated);

                $appbox = $app['phraseanet.appbox'];

                $eventsManager = \eventsmanager_broker::getInstance($appbox, $app['phraseanet.core']);
                $eventsManager->trigger('__UPLOAD_QUARANTINE__', $params);

                $id = $elementCreated->getId();
                $element = 'lazaret';
                $message = _('The file was moved to the quarantine');
            }

            $datas = array(
                'success' => true,
                'code'    => $code,
                'message' => $message,
                'element' => $element,
                'reasons' => $reasons,
                'id'      => $id,
            );
        } catch (\Exception $e) {
            $datas['message'] = _('Unable to add file to Phraseanet');
        }

        $response = $app->json($datas);
        // IE 7 and 8 does not correctly handle json response in file API
        // lets send them an html content-type header
        $response->headers->set('Content-type', 'text/html');

        return $response;
    }

    /**
     * Prefix the method to call with the controller class name
     *
     * @param  string $method The method to call
     * @return string
     */
    private function call($method)
    {
        return sprintf('%s::%s', __CLASS__, $method);
    }

    /**
     * Get current user's granted collections where he can upload
     *
     * @param \User_Adapter $user
     * @return array
     */
    private function getGrantedCollections(\User_Adapter $user)
    {
        $collections = array();

        foreach ($user->ACL()->get_granted_base(array('canaddrecord')) as $collection) {
            $databox = $collection->get_databox();
            if ( ! isset($collections[$databox->get_sbas_id()])) {
                $collections[$databox->get_sbas_id()] = array(
                    'databox'             => $databox,
                    'databox_collections' => array()
                );
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
