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

use Silex\Application,
    Silex\ControllerProviderInterface,
    Silex\ControllerCollection;
use Alchemy\Phrasea\Helper\Record as RecordHelper,
    Alchemy\Phrasea\Out\Module\PDF as PDFExport;
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
                $printer = new RecordHelper\Printer($app['phraseanet.core'], $app['request']);

                $template = 'prod/actions/printer_default.html.twig';

                /* @var $twig \Twig_Environment */
                $twig = $app['phraseanet.core']->getTwig();

                return $twig->render($template, array('printer' => $printer, 'message' => ''));
            }
        );

        $controllers->post('/print.pdf', function(Application $app) {
                $printer = new RecordHelper\Printer($app['phraseanet.core'], $app['request']);

                $request = $app['request'];

                $session = \Session_Handler::getInstance($app['phraseanet.appbox']);

                $layout = $request->get('lay');

                foreach ($printer->get_elements() as $record) {
                    $session->get_logger($record->get_databox())
                        ->log($record, \Session_Logger::EVENT_PRINT, $layout, '');
                }
                $PDF = new PDFExport($printer->get_elements(), $layout);

                return new Response($PDF->render(), 200, array('Content-Type' => 'application/pdf'));
            }
        );

        return $controllers;
    }
}
