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

use Alchemy\Phrasea\Controller\Controller;
use Alchemy\Phrasea\Controller\RecordsRequest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PropertyController extends Controller
{
    /**
     *  Display Status property
     *
     * @param  Request $request
     * @return Response
     */
    public function displayStatusProperty(Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            $this->app->abort(400);
        }

        $records = RecordsRequest::fromRequest($this->app, $request, false, [\ACL::CHGSTATUS]);

        $databoxes = $records->databoxes();
        if (count($databoxes) > 1) {
            return new Response($this->render('prod/actions/Property/index.html.twig', [
                'records'   => $records,
            ]));
        }

        $databox = reset($databoxes);
        $statusStructure = $databox->getStatusStructure();
        $recordsStatuses = [];

        foreach ($records->received() as $record) {
            foreach ($statusStructure as $status) {
                $bit = $status['bit'];

                if (!isset($recordsStatuses[$bit])) {
                    $recordsStatuses[$bit] = $status;
                }

                $statusSet = \databox_status::bitIsSet($record->getStatusBitField(), $bit);

                if (!isset($recordsStatuses[$bit]['flag'])) {
                    $recordsStatuses[$bit]['flag'] = (int) $statusSet;
                }

                // if flag property was already set and the value is different from the previous one
                // it means that records share different value for the same flag
                if ($recordsStatuses[$bit]['flag'] !== (int) $statusSet) {
                    $recordsStatuses[$bit]['flag'] = 2;
                }
            }
        }

        return new Response($this->render('prod/actions/Property/index.html.twig', [
            'records'   => $records,
            'status'    => $recordsStatuses,
        ]));
    }

    /**
     * Display type property
     *
     * @param  Request $request
     * @return Response
     */
    public function displayTypeProperty(Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            $this->app->abort(400);
        }

        $records = RecordsRequest::fromRequest($this->app, $request, false, [\ACL::CANMODIFRECORD]);

        $recordsType = [];

        foreach ($records as $record) {
            //perform logic
            $sbasId = $record->getDataboxId();

            if (!isset($recordsType[$sbasId])) {
                $recordsType[$sbasId] = [];
            }

            if (!isset($recordsType[$sbasId][$record->getType()])) {
                $recordsType[$sbasId][$record->getType()] = [];
            }

            $recordsType[$sbasId][$record->getType()][] = $record;
        }

        return new Response($this->render('prod/actions/Property/type.html.twig', [
            'records'     => $records,
            'recordsType' => $recordsType,
        ]));
    }

    /**
     * Change record status
     *
     * @param  Request $request
     * @return Response
     */
    public function changeStatus(Request $request)
    {
        $applyStatusToChildren = $request->request->get('apply_to_children', []);
        $records = RecordsRequest::fromRequest($this->app, $request, false, [\ACL::CHGSTATUS]);
        $updated = [];
        $postStatus = (array) $request->request->get('status');

        foreach ($records as $record) {
            $sbasId = $record->getDataboxId();

            //update record
            if (null !== $updatedStatus = $this->updateRecordStatus($record, $postStatus)) {
                $updated[$record->getId()] = $updatedStatus;
            }

            //update children if current record is a story
            if (isset($applyStatusToChildren[$sbasId]) && $record->isStory()) {
                foreach ($record->getChildren() as $child) {
                    if (null !== $updatedStatus = $this->updateRecordStatus($child, $postStatus)) {
                        $updated[$record->getId()] = $updatedStatus;
                    }
                }
            }
        }

        return $this->app->json(['success' => true, 'updated' => $updated], 201);
    }

    /**
     * Change record type
     *
     * @param  Request $request
     * @return Response
     */
    public function changeType(Request $request)
    {
        $typeLst = $request->request->get('types', []);
        $records = RecordsRequest::fromRequest($this->app, $request, false, [\ACL::CANMODIFRECORD]);
        $mimeLst = $request->request->get('mimes', []);
        $forceType = $request->request->get('force_types', '');
        $updated = [];

        foreach ($records as $record) {
            try {
                $recordType = !empty($forceType) ? $forceType : (isset($typeLst[$record->getId()]) ? $typeLst[$record->getId()] : null);
                $mimeType = isset($mimeLst[$record->getId()]) ? $mimeLst[$record->getId()] : null;

                if ($recordType) {
                    $record->setType($recordType);
                    $updated[$record->getId()]['record_type'] = $recordType;
                }

                if ($mimeType) {
                    $record->setMimeType($mimeType);
                    $updated[$record->getId()]['mime_type'] = $mimeType;
                }
            } catch (\Exception $e) {

            }
        }

        return $this->app->json(['success' => true, 'updated' => $updated], 201);
    }

    /**
     * Set new status to selected record
     *
     * @param  \record_adapter $record
     * @param  array           $postStatus
     * @return array|null
     */
    private function updateRecordStatus(\record_adapter $record, array $postStatus)
    {
        $sbasId = $record->getDataboxId();

        if (isset($postStatus[$sbasId]) && is_array($postStatus[$sbasId])) {
            $postStatus = $postStatus[$sbasId];
            $currentStatus = strrev($record->getStatus());

            $newStatus = '';
            foreach (range(0, 31) as $i) {
                $newStatus .= isset($postStatus[$i]) ? ($postStatus[$i] ? '1' : '0') : $currentStatus[$i];
            }

            $record->setStatus(strrev($newStatus));

            return [
                'current_status' => $currentStatus,
                'new_status'     => $newStatus,
            ];
        }

        return null;
    }
}
