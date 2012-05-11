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

use Alchemy\Phrasea\Helper;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Lazaret controller collection
 *
 * Defines routes related to the lazaret (quarantine) functionality
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Lazaret implements ControllerProviderInterface
{

    /**
     * Connect the ControllerCollection to the Silex Application
     *
     * @param Application $app A silex application
     * @return \Silex\ControllerCollection
     */
    public function connect(Application $app)
    {
        $controllers = new ControllerCollection();

        /**
         * Lazaret Elements route AKA lazaret_elements
         *
         * descritpion : List all lazaret elements
         *
         * method : GET
         *
         * @return HTML Response
         */
        $app->get('/lazaret/', $this->call('listElement'))
            ->bind('lazaret_elements');

        /**
         * Lazaret Element route AKA lazaret_element
         *
         * descritpion : Get one lazaret element identified by {file_id} parameter
         *
         * method : GET
         *
         * return JSON Response
         */
        $app->get('/lazaret/{file_id}/', $this->call('getElement'))
            ->assert('file_id', '\d+')
            ->bind('lazaret_element');

        /**
         * Lazaret Force Add route AKA lazaret_force_add
         *
         * descritpion : Move a lazaret element identified by {file_id} parameter into phraseanet
         *
         * method : POST
         *
         * parameters :
         *  'bas_id' int (mandatory) : The id of the destination collection
         *  'keep_attributes' boolean (optional) : Keep all attributes attached to the lazaret element
         *  'attributes' array (optional) : Attributes id's to attach to the lazaret element
         *
         * return JSON Response
         */
        $app->post('/lazaret/{file_id}/force-add/', $this->call('addElement'))
            ->assert('file_id', '\d+')
            ->bind('lazaret_force_add');

        /**
         * Lazaret Deny route AKA lazaret_deny_element
         *
         * descritpion : Remove a lazaret element identified by {file_id} parameter
         *
         * method : POST
         *
         * @return JSON
         */
        $app->post('/lazaret/{file_id}/deny/', $this->call('denyElement'))
            ->assert('file_id', '\d+')
            ->bind('lazaret_deny_element');

        /**
         * Lazaret Accept Route AKA lazaret_accept
         *
         * description : Substitute the phraseanet record identified by
         * the post parameter 'record_id'by the lazaret element identified
         * by {file_id} parameter
         *
         * method : POST
         *
         * parameters :
         *  'record_id' int (mandatory) : The substitued record
         *
         * return JSON response
         */
        $app->post('/lazaret/{file_id}/accept/', $this->call('acceptElement'))
            ->assert('file_id', '\d+')
            ->bind('lazaret_accept');

        /**
         * Lazaret Thumbnail route AKA lazaret_thumbnail
         *
         * descritpion : Get the thumbnail attached to the lazaret element identified by {file_id} parameter
         *
         * method : GET
         *
         * return JSON Response
         */
        $app->get('/lazaret/{file_id}/thumbnail/', $this->call('thumbnailElement'))
            ->assert('file_id', '\d+')
            ->bind('lazaret_thumbnail');

        return $controllers;
    }

    /**
     * List all elements in lazaret
     *
     * @param Application $app The Silex application where the controller is mounted on
     * @param Request $request The current request
     *
     * @return Response
     */
    public function listElement(Application $app, Request $request)
    {

    }

    /**
     * Get one lazaret Element
     *
     * @param Application $app The Silex application where the controller is mounted on
     * @param Request $request The current request
     * @param type $file_id An lazaret element id
     *
     * @return Response
     */
    public function getElement(Application $app, Request $request, $file_id)
    {

    }

    /**
     * Add an element to phraseanet
     *
     * @param Application $app The Silex application where the controller is mounted on
     * @param Request $request The current request
     * @param type $file_id An lazaret element id
     *
     * @return Response
     */
    public function addElement(Application $app, Request $request, $file_id)
    {

    }

    /**
     * Delete a lazaret element
     *
     * @param Application $app The Silex application where the controller is mounted on
     * @param Request $request The current request
     * @param type $file_id An lazaret element id
     *
     * @return Response
     */
    public function denyElement(Application $app, Request $request, $file_id)
    {

    }

    /**
     * Substitute a record element by a lazaret element
     *
     * @param Application $app The Silex application where the controller is mounted on
     * @param Request $request The current request
     * @param type $file_id An lazaret element id
     *
     * @return Response
     */
    public function acceptElement(Application $app, Request $request, $file_id)
    {

    }

    /**
     * Get the associated lazaret element thumbnail
     *
     * @param Application $app The Silex application where the controller is mounted on
     * @param Request $request The current request
     * @param type $file_id An lazaret element id
     *
     * @return Response
     */
    public function thumbnailElement(Application $app, Request $request, $file_id)
    {

    }

    /**
     * Prefix the method to call with the controller class name
     *
     * @param type $method The method to call
     * @return string
     */
    private function call($method)
    {
        return sprintf('%s::%s', __CLASS__, $method);
    }
}
