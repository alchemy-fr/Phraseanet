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
use Alchemy\Phrasea\Controller\RecordsRequest;
use Alchemy\Phrasea\Helper\Record as RecordHelper;
use Alchemy\Phrasea\Out\Module\PDFRecords;
use record_adapter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class PrinterController extends Controller
{
    use DataboxLoggerAware;

    public function postPrinterAction(Request $request)
    {
        $printer = new RecordHelper\Printer($this->app, $request);

        $basketFeedbackId = null;
        if($printer->is_basket() && ($basket = $printer->get_original_basket()) && $basket->isVoteBasket()) {
            if($basket->getVoteInitiator()->getId() === $this->app->getAuthenticatedUser()->getId()) {
                $basketFeedbackId = $basket->getId();
            }
        }

        $pdfTitle = '';
        $storyId = null;

        if ($printer->is_basket()) {
            $pdfTitle = $printer->get_original_basket()->getName();
        }

        $r = RecordsRequest::fromRequest($this->app, $request, false);

        if ($r->isSingleStory()) {
            $pdfTitle = $r->singleStory()->get_title(['encode'=> record_adapter::ENCODE_NONE]);
            $storyId = $r->singleStory()->getId();
        }

        return $this->render('prod/actions/printer_default.html.twig', [
            'printer' => $printer,
            'message' => '',
            'storyId' => $storyId,
            'pdfTitle'=> $pdfTitle,
            'basketFeedbackId' => $basketFeedbackId,
        ]);
    }

    public function printAction(Request $request)
    {
        $printer = new RecordHelper\Printer($this->app, $request);
        $printer->setThumbnailName($request->request->get('thumbnail-chosen'));
        $printer->setPreviewName($request->request->get('preview-chosen'));

        $layout = $request->request->get('lay');
        $title = $request->request->get('print-pdf-title') ? : '';
        $description = $request->request->get('print-pdf-description') ? : '';
        $userPassword = $request->request->get('print-pdf-password') ? : '';
        $canDownload = $request->request->get('can-download-subdef') == 1 ? true : false ;
        $showRecordInfo = $request->request->get('show-record-information') == 1 ? true : false ;
        $descriptionFontSize = $request->request->get('description-font-size') ? : 12;
        $fieldTitleColor= $request->request->get('field-title-color') ? : '';

        $downloadSubdef = '';
        $urlTtl = null;
        if ($canDownload) {
            $downloadSubdef = $request->request->get('print-select-download-subdef');
            $urlTtl = $request->request->get('print-download-ttl') ? (int)$request->request->get('print-download-ttl') * (int)$request->request->get('print-download-ttl-unit') : null;
            $printer->setUrlTtl($urlTtl);
            $useTitle = ($request->request->get('print-filename') === 'title') ? true : false;
            $printer->setTitleAsDownloadName($useTitle);
        }

        foreach ($printer->get_elements() as $record) {
            $this->getDataboxLogger($record->getDatabox())->log($record, \Session_Logger::EVENT_PRINT, $layout, '');
        }

        $PDF = new PDFRecords(
            $this->app,
            $printer,
            $layout,
            $title,
            $description,
            $userPassword,
            $canDownload,
            $downloadSubdef,
            $showRecordInfo,
            $descriptionFontSize,
            $fieldTitleColor
        );

        $pdfName = '';

        if (!empty($title)) {
            $pdfName = $printer->normalizeString($title);
            $pdfName .= '.pdf';
        }

        $response = new Response($PDF->render(), 200, array('Content-Type' => 'application/pdf'));
        $response->headers->set('Pragma', 'public', true);
        $response->setMaxAge(0);

        $disposition = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_INLINE,
            $pdfName
        );
        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }

}
