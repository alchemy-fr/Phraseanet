<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Prod;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Alchemy\Phrasea\Helper\Record as RecordHelper;
use Alchemy\Phrasea\Out\Module\PDF as PDFExport;
use Symfony\Component\HttpFoundation\Response;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Printer implements ControllerProviderInterface
{

    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        $controllers->post('/', function(Application $app) {
                $printer = new RecordHelper\Printer($app, $app['request']);

                return $app['twig']->render('prod/actions/printer_default.html.twig', array('printer' => $printer, 'message' => ''));
            }
        );

        $controllers->post('/print.pdf', function(Application $app) {
            $printer = new RecordHelper\Printer($app, $app['request']);

            $request = $app['request'];

            $layout = $request->request->get('lay');

            foreach ($printer->get_elements() as $record) {
                $app['phraseanet.logger']($record->get_databox())
                    ->log($record, \Session_Logger::EVENT_PRINT, $layout, '');
            }
            $PDF = new PDFExport($app, $printer->get_elements(), $layout);

            $response =  new Response($PDF->render(), 200, array('Content-Type' => 'application/pdf'));
            $response->headers->set('Pragma', 'public', true);
            $response->setMaxAge(0);

            return $response;
        });

        return $controllers;
    }
}
