<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Prod;

use Alchemy\Phrasea\Controller\RecordsRequest;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Property implements ControllerProviderInterface
{
    /**
     * {@inheritDoc}
     */
    public function connect(Application $app)
    {
        $app['controller.prod.property'] = $this;

        $controllers = $app['controllers_factory'];

        $app['firewall']->addMandatoryAuthentication($controllers);

        $controllers->before(function (Request $request) use ($app) {
            $app['firewall']->requireNotGuest();
        });

        $controllers->get('/', 'controller.prod.property:displayStatusProperty')
            ->bind('display_status_property');

        $controllers->get('/type/', 'controller.prod.property:displayTypeProperty')
            ->bind('display_type_property');

        $controllers->post('/status/', 'controller.prod.property:changeStatus')
            ->bind('change_status');

        $controllers->post('/type/', 'controller.prod.property:changeType')
            ->bind('change_type');

        return $controllers;
    }

    /**
     *  Display Status property
     *
     * @param  Application $app
     * @param  Request     $request
     * @return Response
     */
    public function displayStatusProperty(Application $app, Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            $app->abort(400);
        }

        $records = RecordsRequest::fromRequest($app, $request, false, ['chgstatus']);
        $databoxStatus = \databox_status::getDisplayStatus($app);
        $statusBit = $nRec = [];

        foreach ($records as $record) {
            //perform logic
            $sbasId = $record->get_databox()->get_sbas_id();

            if (!isset($nRec[$sbasId])) {
                $nRec[$sbasId] = ['stories' => 0, 'records' => 0];
            }

            $nRec[$sbasId]['records']++;

            if ($record->is_grouping()) {
                $nRec[$sbasId]['stories']++;
            }

            if (!isset($statusBit[$sbasId])) {
                $statusBit[$sbasId] = isset($databoxStatus[$sbasId]) ? $databoxStatus[$sbasId] : [];

                foreach (array_keys($statusBit[$sbasId]) as $bit) {
                    $statusBit[$sbasId][$bit]['nset'] = 0;
                }
            }

            $status = strrev($record->get_status());

            foreach (array_keys($statusBit[$sbasId]) as $bit) {
                $statusBit[$sbasId][$bit]["nset"] += substr($status, $bit, 1) !== "0" ? 1 : 0;
            }
        }

        foreach ($records->databoxes() as $databox) {
            $sbasId = $databox->get_sbas_id();
            foreach ($statusBit[$sbasId] as $bit => $values) {
                $statusBit[$sbasId][$bit]["status"] = $values["nset"] == 0 ? 0 : ($values["nset"] == $nRec[$sbasId]['records'] ? 1 : 2);
            }
        }

        return new Response($app['twig']->render('prod/actions/Property/index.html.twig', [
            'records'   => $records,
            'statusBit' => $statusBit,
            'nRec'      => $nRec
        ]));
    }

    /**
     * Display type property
     *
     * @param  Application $app
     * @param  Request     $request
     * @return Response
     */
    public function displayTypeProperty(Application $app, Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            $app->abort(400);
        }

        $records = RecordsRequest::fromRequest($app, $request, false, ['canmodifrecord']);

        $recordsType = [];

        foreach ($records as $record) {
            //perform logic
            $sbasId = $record->get_databox()->get_sbas_id();

            if (!isset($recordsType[$sbasId])) {
                $recordsType[$sbasId] = [];
            }

            if (!isset($recordsType[$sbasId][$record->get_type()])) {
                $recordsType[$sbasId][$record->get_type()] = [];
            }

            $recordsType[$sbasId][$record->get_type()][] = $record;
        }

        return new Response($app['twig']->render('prod/actions/Property/type.html.twig', [
            'records'     => $records,
            'recordsType' => $recordsType,
        ]));
    }

    /**
     * Change record status
     *
     * @param  Application  $app
     * @param  Request      $request
     * @return JsonResponse
     */
    public function changeStatus(Application $app, Request $request)
    {
        $applyStatusToChildren = $request->request->get('apply_to_children', []);
        $records = RecordsRequest::fromRequest($app, $request, false, ['chgstatus']);
        $updated = [];
        $postStatus = (array) $request->request->get('status');

        foreach ($records as $record) {
            $sbasId = $record->get_databox()->get_sbas_id();

            //update record
            if (null !== $updatedStatus = $this->updateRecordStatus($record, $postStatus)) {
                $updated[$record->get_serialize_key()] = $updatedStatus;
            }

            //update children if current record is a story
            if (isset($applyStatusToChildren[$sbasId]) && $record->is_grouping()) {
                foreach ($record->get_children() as $child) {
                    if (null !== $updatedStatus = $this->updateRecordStatus($child, $postStatus)) {
                        $updated[$record->get_serialize_key()] = $updatedStatus;
                    }
                }
            }
        }

        return $app->json(['success' => true, 'updated' => $updated], 201);
    }

    /**
     * Change record type
     *
     * @param  Application $app
     * @param  Request     $request
     * @return type
     */
    public function changeType(Application $app, Request $request)
    {
        $typeLst = $request->request->get('types', []);
        $records = RecordsRequest::fromRequest($app, $request, false, ['canmodifrecord']);
        $mimeLst = $request->request->get('mimes', []);
        $forceType = $request->request->get('force_types', '');
        $updated = [];

        foreach ($records as $record) {
            try {
                $recordType = !empty($forceType) ? $forceType : (isset($typeLst[$record->get_serialize_key()]) ? $typeLst[$record->get_serialize_key()] : null);
                $mimeType = isset($mimeLst[$record->get_serialize_key()]) ? $mimeLst[$record->get_serialize_key()] : null;

                if ($recordType) {
                    $record->set_type($recordType);
                    $updated[$record->get_serialize_key()]['record_type'] = $recordType;
                }

                if ($mimeType) {
                    $record->set_mime($mimeType);
                    $updated[$record->get_serialize_key()]['mime_type'] = $mimeType;
                }
            } catch (\Exception $e) {

            }
        }

        return $app->json(['success' => true, 'updated' => $updated], 201);
    }

    /**
     * Set new status to selected record
     *
     * @param  \record_adapter $record
     * @param  array           $postStatus
     * @return array|null
     */
    private function updateRecordStatus(\record_adapter $record, Array $postStatus)
    {
        $sbasId = $record->get_databox()->get_sbas_id();

        if (isset($postStatus[$sbasId]) && is_array($postStatus[$sbasId])) {
            $postStatus = $postStatus[$sbasId];
            $currentStatus = strrev($record->get_status());

            $newStatus = '';
            foreach (range(0, 31) as $i) {
                $newStatus .= isset($postStatus[$i]) ? ($postStatus[$i] ? '1' : '0') : $currentStatus[$i];
            }

            $record->set_binary_status(strrev($newStatus));

            return [
                'current_status' => $currentStatus,
                'new_status'     => $newStatus
            ];
        }

        return null;
    }
}
