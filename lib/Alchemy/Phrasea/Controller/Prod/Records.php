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

use Alchemy\Phrasea\Controller\RecordsRequest;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class Records implements ControllerProviderInterface
{

    /**
     * {@inheritDoc}
     */
    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        $controllers->before(function(Request $request) use ($app) {
            $app['firewall']->requireNotGuest();
        });

        /**
         * Delete a record or a list of records
         *
         * name         : record_delete
         *
         * description  : Delete a record or a list of records
         *
         * method       : POST
         *
         * parameters   : none
         *
         * return       : JSON Response
         */
        $controllers->post('/delete/', $this->call('doDeleteRecords'))
            ->bind('record_delete');

        /**
         * Verify if I can delete records
         *
         * name         : record_what_can_i_delete
         *
         * description  : Verify if I can delete records
         *
         * method       : POST
         *
         * parameters   : none
         *
         * return       : HTML Response
         */
        $controllers->post('/delete/what/', $this->call('whatCanIDelete'))
            ->bind('record_what_can_i_delete');

        /**
         * Renew a record URL
         *
         * name         : record_renew_url
         *
         * description  : Renew a record URL
         *
         * method       : POST
         *
         * parameters   : none
         *
         * return       : JSON Response
         */
        $controllers->post('/renew-url/', $this->call('renewUrl'))
            ->bind('record_renew_url');

        return $controllers;
    }

    /**
     *  Delete a record or a list of records
     *
     * @param   Application     $app
     * @param   Request         $request
     * @return  HtmlResponse
     */
    public function doDeleteRecords(Application $app, Request $request)
    {
        $records = RecordsRequest::fromRequest($app, $request, !!$app->request->get('del_children'), array(
            'candeleterecord'
        ));

        $basketElementsRepository = $app['EM']->getRepository('\Entities\BasketElement');
        $deleted = array();

        foreach ($records as $record) {
            try {
                $basketElements = $basketElementsRepository->findElementsByRecord($record);

                foreach ($basketElements as $element) {
                    $app['EM']->remove($element);
                    $deleted[] = $element->getRecord($app)->get_serialize_key();
                }

                $record->delete();
                $deleted[] = $record->get_serialize_key();
            } catch (\Exception $e) {

            }
        }

        $app['EM']->flush();

        return $app->json($deleted);
    }

    /**
     *  Delete a record or a list of records
     *
     * @param   Application     $app
     * @param   Request         $request
     * @return  JsonResponse
     */
    public function whatICanDelete(Application $app, Request $request)
    {
        $records = RecordsRequest::fromRequest($app, $request, !!$app->request->get('del_children'), array(
            'candeleterecord'
        ));

        return $app['twig']->render('prod/actions/delete_records_confirm.html.twig', array(
            'lst'       => $records->serializedList(),
            'groupings' => $records->stories()->count(),
        ));
    }

    /**
     *  Renew url list of records
     *
     * @param   Application     $app
     * @param   Request         $request
     * @param   integer         $databox_id
     * @param   integer         $record_id
     * @return  JsonResponse
     */
    public function renewUrl(Application $app, Request $request)
    {
        $records = RecordsRequest::fromRequest($app, $request, !!$app->request->get('renew_children_url'));

        $renewed = array();
        foreach ($records as $record) {
            $renewed[] = array(
                $record->get_serialized_key() => $record->get_preview()->renew_url(),
            );
        };

        return $app->json($renewed);
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
