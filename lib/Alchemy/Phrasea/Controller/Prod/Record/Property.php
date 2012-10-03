<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Prod\Record;

use Alchemy\Phrasea\Controller\RecordsRequest;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Property implements ControllerProviderInterface
{

    /**
     * {@inheritDoc}
     */
    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        $controllers->before(function(Request $request) use ($app) {
                $response = $app['firewall']->requireNotGuest();
                if ($response instanceof Response) {
                    return $response;
                }
            });

        $controllers->get('/', $this->call('displayProperty'))
            ->bind('display_property');

        $controllers->post('/status/', $this->call('changeStatus'))
            ->bind('change_status');

        return $controllers;
    }

    /**
     *  Display property
     *
     * @param   Application     $app
     * @param   Request         $request
     * @return  Response
     */
    public function displayProperty(Application $app, Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            $app->abort(400);
        }

        $records = RecordsRequest::fromRequest($app, $request, false, array('chgstatus'));
        $databoxStatus = \databox_status::getDisplayStatus($app);
        $statusBit = $recordsType = $nRec = $toRemove = array();

        foreach ($records as $key => $record) {
            if (!$app['phraseanet.user']->ACL()->has_hd_grant($record) ||
                !$app['phraseanet.user']->ACL()->has_preview_grant($record)) {
                try {
                    $conn = $record->get_databox()->get_connection();

                    $sql = sprintf('SELECT record_id FROM record WHERE ((status ^ %s) & %s) = 0 AND record_id = :record_id', $app['phraseanet.user']->ACL()->get_mask_xor($record->get_base_id()), $app['phraseanet.user']->ACL()->get_mask_and($record->get_base_id()));

                    $stmt = $conn->prepare($sql);
                    $stmt->execute(array(':record_id' => $record->get_record_id()));

                    if (0 === $stmt->rowCount()) {
                        $toRemove[] = $key;
                    }

                    $stmt->closeCursor();
                    unset($stmt);
                } catch (Exception $e) {
                    $toRemove[] = $key;
                }
            }
        }

        foreach ($toRemove as $key) {
            $records->remove($key);
        }

        foreach ($records as $record) {
            $sbasId = $record->get_databox()->get_sbas_id();

            if (!isset($nRec[$sbasId])) {
                $nRec[$sbasId] = array('stories' => 0, 'records' => 0);
            }

            $nRec[$sbasId]['records']++;

            if ($record->is_grouping()) {
                $nRec[$sbasId]['stories']++;
            }

            if (!isset($recordsType[$sbasId])) {
                $recordsType[$sbasId] = array();
            }

            if (!isset($recordsType[$sbasId][$record->get_type()])) {
                $recordsType[$sbasId][$record->get_type()] = array();
            }

            $recordsType[$sbasId][$record->get_type()] = $record;

            if (!isset($statusBit[$sbasId])) {

                $statusBit[$sbasId] = isset($databoxStatus[$sbasId]) ? $databoxStatus[$sbasId] : array();

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

        return $app['twig']->render('prod/actions/Property/index.html.twig', array(
                'records'     => $records,
                'statusBit'   => $statusBit,
                'recordsType' => $recordsType,
                'nRec'        => $nRec
            ));
    }

    public function changeStatus(Application $app, Request $request)
    {
        $applyStatusToChildren = $request->request->get('apply_to_children', array());
        $records = RecordsRequest::fromRequest($app, $request, false, array('chgstatus'));
        $updated = array();
        $postStatus = $request->request->get('status');

        foreach ($records as $record) {
            $sbasId = $record->get_databox()->get_sbas_id();

            //update record
            $updated[$record->get_serialize_key()] = $this->updateRecordStatus($record, $postStatus);

            //update children if current record is a story
            if (isset($applyStatusToChildren[$sbasId]) && $record->is_grouping()) {
                foreach ($record->get_children() as $child) {
                   $updated[$record->get_serialize_key()] = $this->updateRecordStatus($child, $postStatus);
                }
            }
        }

        return $app->json(array('success' => true, 'updated' => $updated), 201);
    }

    private function updateRecordStatus(\record_adapter $record, Array $postStatus)
    {
        $sbasId = $record->get_databox()->get_sbas_id();

        if (isset($postStatus[$sbasId]) && is_array($postStatus[$sbasId])) {
            $postStatus = $postStatus[$sbasId];
            $currentStatus = strrev($record->get_status());

            $newStatus = '';
            foreach (range(0, 63) as $i) {
                $newStatus .= isset($postStatus[$i]) ? ($postStatus[$i] ? '1' : '0') : $currentStatus[$i];
            }

            $record->set_binary_status(strrev($newStatus));

            return array(
                'current_status' => $currentStatus,
                'new_status'     => $newStatus
            );
        }
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
