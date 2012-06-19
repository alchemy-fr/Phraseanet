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

use Alchemy\Phrasea\Border;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Serializer\Serializer;

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
     * Render the html upload form
     *
     * @param Application $app     A Silex application
     * @param Request     $request The current request
     *
     * @return Response
     */
    public function getUploadForm(Application $app, Request $request)
    {
        $collections = array();
        $rights = array('canaddrecord');

        foreach ($app['Core']->getAuthenticatedUser()->ACL()->get_granted_base($rights) as $collection) {
            $databox = $collection->get_databox();
            if ( ! isset($collections[$databox->get_sbas_id()])) {
                $collections[$databox->get_sbas_id()] = array(
                    'databox'             => $databox,
                    'databox_collections' => array()
                );
            }

            $collections[$databox->get_sbas_id()]['databox_collections'][] = $collection;
        }

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

        $maxFileSize = min(UploadedFile::getMaxFilesize(), (int) $postMaxSize);

        $html = $app['Core']['Twig']->render(
            'prod/upload/upload.html.twig', array(
            'collections'         => $collections,
            'maxFileSize'         => $maxFileSize,
            'maxFileSizeReadable' => \p4string::format_octets($maxFileSize)
            )
        );

        return new Response($html);
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

        if ( ! $request->files->get('files')) {
            throw new \Exception_BadRequest('Missing file parameter');
        }

        if (count($request->files->get('files')) > 1) {
            throw new \Exception_BadRequest('Upload is limited to 1 file per request');
        }

        $base_id = $request->get('base_id');

        if ( ! $base_id) {
            throw new \Exception_BadRequest('Missing base_id parameter');
        }

        if ( ! $app['Core']->getAuthenticatedUser()->ACL()->has_right_on_base($base_id, 'canaddrecord')) {
            throw new \Exception_Forbidden('User is not allowed to add record on this collection');
        }

        $file = current($request->files->get('files'));

        if ( ! $file->isValid()) {
            throw new \Exception_BadRequest('Uploaded file is invalid');
        }

        try {
            $uploadedFilename = $file->getRealPath();
            $renamedFilename = $file->getRealPath() . $file->getClientOriginalName();

            $originalname = $file->getClientOriginalName();
            $clientMimeType = $file->getClientMimeType();
            $size = $file->getSize();
            $error = $file->getError();

            $app['Core']['file-system']->rename($uploadedFilename, $renamedFilename);

            $file = new UploadedFile($renamedFilename, $originalname, $clientMimeType, $size, $error);

            $media = $app['Core']['mediavorus']->guess($file);
            $collection = \collection::get_from_base_id($base_id);

            $lazaretSession = new \Entities\LazaretSession();
            $lazaretSession->setUsrId($app['Core']->getAuthenticatedUser()->get_id());

            $app['Core']['EM']->persist($lazaretSession);

            $packageFile = new Border\File($media, $collection, $file->getClientOriginalName());

            $postStatus = $request->get('status');

            if (isset($postStatus[$collection->get_sbas_id()]) && is_array($postStatus[$collection->get_sbas_id()])) {
                $postStatus = $postStatus[$collection->get_sbas_id()];

                $status = '';
                foreach (range(0, 63) as $i) {
                    $status .= isset($postStatus[$i]) ? ($postStatus[$i] ? '1' : '0') : '0';
                }
                $packageFile->addAttribute(new Border\Attribute\Status(strrev($status)));
            }

            $forceBehavior = $request->get('forceAction');

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

            $code = $app['Core']['border-manager']->process(
                $lazaretSession, $packageFile, $callback, $forceBehavior
            );

            $app['Core']['file-system']->rename($renamedFilename, $uploadedFilename);

            if ( ! ! $forceBehavior) {
                $reasons = array();
            }

            if ($elementCreated instanceof \record_adapter) {
                $id = $elementCreated->get_serialize_key();
                $element = 'record';
                $message = _('The record was successfully created');
            } else {
                $params = array('lazaret_file' => $elementCreated);

                $appbox = \appbox::get_instance($app['Core']);

                $eventsManager = \eventsmanager_broker::getInstance($appbox, $app['Core']);
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

        $response = self::getJsonResponse($app['Core']['Serializer'], $datas);

        // IE 7 and 8 does not correctly handle json response in file API
        // let send them an html content-type header
        $response->headers->set('Content-type', 'text/html');

        return $response;
    }

    private static function getJsonResponse(Serializer $serializer, Array $datas)
    {
        return new Response(
                $serializer->serialize($datas, 'json'),
                200,
                array('Content-type' => 'application/json')
        );
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
}
