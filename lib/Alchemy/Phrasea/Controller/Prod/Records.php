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
         * Get  the record detailed view
         *
         * name         : record_details
         *
         * description  : Get the detailed view for a specific record
         *
         * method       : POST|GET
         *
         * parameters   : none
         *
         * return       : JSON Response
         */
        $controllers->match('/', $this->call('getRecord'))
            ->bind('record_details')
            ->method('GET|POST');

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
     * Get record detailed view
     *
     * @param   Application   $app
     * @param   Request       $request
     * @param   integer       $sbas_id
     * @param   integer       $record_id
     * @return  JsonResponse
     */
    public function getRecord(Application $app, Request $request)
    {
        if(!$request->isXmlHttpRequest()){
            $app->abort(400);
        }

        $searchEngine = null;
        $train = '';

        if ('' === $env = strtoupper($request->get('env', ''))) {
            $app->abort(400, '`env` parameter is missing');
        }

        // Use $request->get as HTTP method can be POST or GET
        if ('RESULT' == $env = strtoupper($request->get('env', ''))) {
            if (null === $optionsSerial = $request->get('options_serial')) {
                $app->abort(400, 'Search engine options are missing');
            }

            if (false !== $options = unserialize($optionsSerial)) {
                $searchEngine = new \searchEngine_adapter($app);
                $searchEngine->set_options($options);
            } else {
                $app->abort(400, 'Provided search engine options are not valid');
            }
        }

        $pos = (int) $request->get('pos', 0);
        $query = $request->get('query', '');
        $reloadTrain = !! $request->get('roll', false);

        $record = new \record_preview(
            $app,
            $env,
            $pos < 0 ? 0 : $pos,
            $request->get('cont', ''),
            $reloadTrain,
            $searchEngine,
            $query
        );

        if ($record->is_from_reg()) {
            $train = $app['twig']->render('prod/preview/reg_train.html.twig',
                array('record' => $record)
            );
        }

        if ($record->is_from_basket() && $reloadTrain) {
            $train = $app['twig']->render('prod/preview/basket_train.html.twig',
                array('record' => $record)
            );
        }

        if ($record->is_from_feed()) {
            $train = $app['twig']->render('prod/preview/feed_train.html.twig',
                array('record' => $record)
            );
        }

        return $app->json(array(
            "desc"          => $app['twig']->render('prod/preview/caption.html.twig', array(
                'record'        => $record,
                'highlight'     => $query,
                'searchEngine'  => $searchEngine
            )),
            "html_preview"  => $app['twig']->render('common/preview.html.twig', array(
                'record'        => $record
            )),
            "others"        => $app['twig']->render('prod/preview/appears_in.html.twig', array(
                'parents'       => $record->get_grouping_parents(),
                'baskets'       => $record->get_container_baskets($app['EM'], $app['phraseanet.user'])
            )),
            "current"       => $train,
            "history"       => $app['twig']->render('prod/preview/short_history.html.twig', array(
                'record'        => $record
            )),
            "popularity"    => $app['twig']->render('prod/preview/popularity.html.twig', array(
                'record'        => $record
            )),
            "tools"         => $app['twig']->render('prod/preview/tools.html.twig', array(
                'record'        => $record
            )),
            "pos"           => $record->get_number(),
            "title"         => $record->get_title($query, $searchEngine)
        ));
    }

    /**
     *  Delete a record or a list of records
     *
     * @param   Application     $app
     * @param   Request         $request
     * @return  JsonResponse
     */
    public function doDeleteRecords(Application $app, Request $request)
    {
        $records = RecordsRequest::fromRequest($app, $request, !!$request->request->get('del_children'), array(
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
    public function whatCanIDelete(Application $app, Request $request)
    {
        $records = RecordsRequest::fromRequest($app, $request, !!$request->request->get('del_children'), array(
            'candeleterecord'
        ));

        return $app['twig']->render('prod/actions/delete_records_confirm.html.twig', array(
            'records'   => $records
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
        $records = RecordsRequest::fromRequest($app, $request, !!$request->request->get('renew_children_url'));

        $renewed = array();
        foreach ($records as $record) {
            $renewed[$record->get_serialize_key()] = $record->get_preview()->renew_url();
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
