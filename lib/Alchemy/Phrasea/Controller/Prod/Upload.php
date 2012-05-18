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

use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Alchemy\Phrasea\Helper;

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
     * @param   Application     $app    A silex application
     * @return  \Silex\ControllerCollection
     */
    public function connect(Application $app)
    {
        $controllers = new ControllerCollection();

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
        $app->get('/upload/', $this->call('getUploadForm'))
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
        $app->post('/upload/', $this->call('upload'))
            ->bind('upload');

        return $controllers;
    }

    /**
     * Render the html upload form
     *
     * @param Application   $app        A Silex application
     * @param Request       $request    The current request
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

        $html = $app['Core']['Twig']->render('prod/upload/upload.html.twig', array(
            'collections' => $collections
            ));

        return new Response($html);
    }

    /**
     * Upload processus
     *
     * @param Application   $app        The Silex application
     * @param Request       $request    The current request
     *
     * @return Response
     */
    public function upload(Application $app, Request $request)
    {

    }

    /**
     * Prefix the method to call with the controller class name
     *
     * @param   string  $method     The method to call
     * @return  string
     */
    private function call($method)
    {
        return sprintf('%s::%s', __CLASS__, $method);
    }
}
