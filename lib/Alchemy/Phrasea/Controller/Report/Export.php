<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Report;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class Export implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $app['controller.report.export'] = $this;

        $controllers = $app['controllers_factory'];

        $controllers->before(function() use ($app) {
            $app['firewall']->requireAuthentication();
            $app['firewall']->requireAccessToModule('report');
        });

        $controllers->post('/csv', 'controller.report.export:exportCSV')
            ->bind('report_export_csv');

        return $controllers;
    }

    /**
     * Export data to a csv file
     *
     * @param  Application $app
     * @param  Request     $request
     * @return Response
     */
    public function exportCSV(Application $app, Request $request)
    {
        $name = $request->request->get('name', 'export');

        if (null === $data = $request->request->get('csv')) {
            $app->abort(400);
        }

        $filename = mb_strtolower('report_' . $name . '_' . date('dmY') . '.csv');
        $data = preg_replace('/[ \t\r\f]+/', '', $data);

        $response = new Response($data, 200, array(
            'Expires'               => 'Mon, 26 Jul 1997 05:00:00 GMT',
            'Last-Modified'         => gmdate('D, d M Y H:i:s'). ' GMT',
            'Cache-Control'         => 'no-store, no-cache, must-revalidate',
            'Cache-Control'         => 'post-check=0, pre-check=0',
            'Pragma'                => 'no-cache',
            'Content-Type'          => 'text/csv',
            'Content-Length'        => strlen($data),
            'Cache-Control'         => 'max-age=3600, must-revalidate',
            'Content-Disposition'   => 'max-age=3600, must-revalidate',
        ));

        $response->headers->set('Content-Disposition', $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename));

        return $response;
    }
}
