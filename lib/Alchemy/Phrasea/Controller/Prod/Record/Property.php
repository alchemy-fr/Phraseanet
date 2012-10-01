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
            ->bind('display-property');

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
        $records = RecordsRequest::fromRequest($app, $request);
        $databoxStatus = \databox_status::getDisplayStatus($app);

        $records->filter(function($record) use ($app, $databoxStatus) {
                $success = false;

                if (!$app['phraseanet.user']->ACL()->has_preview_grant($record) ||
                    !$app['phraseanet.user']->ACL()->has_preview_grant($record)) {
                    try {
                        $conn = $record->get_databox()->get_connection();

                        $sql = '
                        SELECT record_id FROM record WHERE ((status ^ ' . $app['phraseanet.user']->ACL()->get_mask_xor($record->get_base_id()) . ')
                        & ' . $app['phraseanet.user']->ACL()->get_mask_and($record->get_base_id()) . ')=0' .
                            ' AND record_id = :record_id';

                        $stmt = $conn->prepare($sql);
                        $stmt->execute(array(':record_id' => $record->get_id()));

                        if (0 < $stmt->rowCount()) {
                            $success = true;
                        }

                        $stmt->closeCursor();
                        unset($stmt);
                    } catch (Exception $e) {

                    }
                } else {
                    $success = true;
                }

                return $success;
            });

        $statusBit = array();
        foreach ($records as $record) {
            $sbasId = $record->get_databox()->get_sbas_id();

            if (!isset($statusBit[$sbasId])) {

                $statusBit[$sbasId] = isset($databoxStatus[$sbasId]) ? $databoxStatus[$sbasId] : array();

                foreach (array_keys($statusBit[$sbasId]) as $bit) {
                    $statusBit[$sbasId][$bit]['nset'] = 0;
                }
            }

            $status = strrev($record->get_status());
            foreach (array_key($statusBit[$sbasId]) as $bit) {
                $statusBit[$sbasId][$bit]["nset"] += substr($status, $bit, 1) !== "0" ? 1 : 0;
            }
        }

        $nbStories = $records->stories()->count();
        $nbRecords = $records->count();
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
