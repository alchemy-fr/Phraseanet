<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2018 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Phrasea\Controller\Prod;

use Alchemy\Phrasea\Application\Helper\DataboxLoggerAware;
use Alchemy\Phrasea\Controller\Controller;
use Alchemy\Phrasea\Helper\Record as RecordHelper;
use Alchemy\Phrasea\Out\Module\PDF as PDFExport;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PrinterController extends Controller
{
    use DataboxLoggerAware;

    public function postPrinterAction(Request $request)
    {
        $printer = new RecordHelper\Printer($this->app, $request);

        return $this->render('prod/actions/printer_default.html.twig', ['printer' => $printer, 'message' => '']);
    }

    public function printAction(Request $request)
    {
        $printer = new RecordHelper\Printer($this->app, $request);

        $layout = $request->request->get('lay');

        foreach ($printer->get_elements() as $record) {
            $this->getDataboxLogger($record->getDatabox())->log($record, \Session_Logger::EVENT_PRINT, $layout, '');
        }
        $PDF = new PDFExport($this->app, $printer->get_elements(), $layout);

        $response = new Response($PDF->render(), 200, array('Content-Type' => 'application/pdf'));
        $response->headers->set('Pragma', 'public', true);
        $response->setMaxAge(0);

        return $response;
    }
}
