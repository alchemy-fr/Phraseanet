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

use Alchemy\Phrasea\Application\Helper\DataboxLoggerAware;
use Alchemy\Phrasea\Controller\Controller;
use Alchemy\Phrasea\Helper\Record as RecordHelper;
use Alchemy\Phrasea\Out\Module\PDFRecords;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PrinterController extends Controller
{
    use DataboxLoggerAware;

    public function postPrinterAction(Request $request)
    {
        $printer = new RecordHelper\Printer($this->app, $request);

        $basketFeedbackId = null;
        if($printer->is_basket() && ($basket = $printer->get_original_basket()) && ($validation = $basket->getValidation())) {
            if($validation->getInitiator()->getId() === $this->app->getAuthenticatedUser()->getId()) {
                $basketFeedbackId = $basket->getId();
            }
        }

        return $this->render('prod/actions/printer_default.html.twig', [
            'printer' => $printer,
            'message' => '',
            'basketFeedbackId' => $basketFeedbackId,
        ]);
    }

    public function printAction(Request $request)
    {
        $printer = new RecordHelper\Printer($this->app, $request);
        $printer->setThumbnailName($request->request->get('thumbnail-chosen'));
        $printer->setPreviewName($request->request->get('preview-chosen'));

        $b = $printer->get_original_basket();

        $layout = $request->request->get('lay');
        $title = $request->request->get('print-pdf-title') ? : '';
        $description = $request->request->get('print-pdf-description') ? : '';
        $userPassword = $request->request->get('print-pdf-password') ? : '';
        $canDownload = $request->request->get('can-download-subdef') == 1 ? true : false ;

        $downloadSubdef = '';
        if ($canDownload) {
            $downloadSubdef = $request->request->get('print-select-download-subdef');
        }

        foreach ($printer->get_elements() as $record) {
            $this->getDataboxLogger($record->getDatabox())->log($record, \Session_Logger::EVENT_PRINT, $layout, '');
        }

        $PDF = new PDFRecords($this->app, $printer, $layout, $title, $description, $userPassword, $canDownload, $downloadSubdef);

        $response = new Response($PDF->render(), 200, array('Content-Type' => 'application/pdf'));
        $response->headers->set('Pragma', 'public', true);
        $response->setMaxAge(0);

        return $response;
    }

}
