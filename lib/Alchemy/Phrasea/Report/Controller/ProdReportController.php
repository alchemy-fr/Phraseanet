<?php
/**
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Phrasea\Report\Controller;

use Alchemy\Phrasea\Controller\Controller;
use Alchemy\Phrasea\Report\Report;
use Alchemy\Phrasea\Report\ReportConnections;
use Alchemy\Phrasea\Report\ReportActions;
use Alchemy\Phrasea\Report\ReportFactory;
use Alchemy\Phrasea\Report\ReportRecords;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;


class ProdReportController extends Controller
{
    private static $mapFromExtension = [
        'csv' => [
            'contentType' => 'text/csv',
            'format'      => Report::FORMAT_CSV,
        ],
        'ods' => [
            'contentType' => 'application/vnd.oasis.opendocument.spreadsheet',
            'format'      => Report::FORMAT_ODS,
        ],
        'xlsx' => [
            'contentType' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'format'      => Report::FORMAT_XLSX,
        ],
    ];

    private $reportFactory;
    private $anonymousReport;
    private $acl;
    private $appbox;

    private $extension = null;


    /**
     * @param ReportFactory $reportFactory
     * @param Bool $anonymousReport
     * @param \ACL $acl
     */
    public function __construct(ReportFactory $reportFactory, $anonymousReport, \ACL $acl, \appbox $appbox)
    {
        $this->reportFactory   = $reportFactory;
        $this->anonymousReport = $anonymousReport;
        $this->acl             = $acl;
        $this->appbox          = $appbox;
    }

    /**
     * route prod/report/connections
     *
     * @param Request $request
     * @return Response
     */
    public function indexAction(Request $request)
    {
        return new Response($this->render('prod/report/index.html.twig', [
            'truc' => "hello"
        ]));
    }

    /**
     * route prod/report/connections
     *
     * @param Request $request
     * @param $sbasId
     * @return RedirectResponse|StreamedResponse
     */
    public function connectionsAction(Request $request, $sbasId)
    {
        if ($request->isMethod("POST")) {
            if (!($extension = $request->get('format'))) {
                $extension = 'csv';
            }
            if (!array_key_exists($extension, self::$mapFromExtension)) {
                throw new \InvalidArgumentException(sprintf("bad format \"%s\" for report", $extension));
            }
            $this->extension = $extension;

            /** @var ReportConnections $report */
            $report = $this->reportFactory->createReport(
                ReportFactory::CONNECTIONS,
                $sbasId,
                [
                    'dmin' => $request->get('dmin'),
                    'dmax' => $request->get('dmax'),
                    'group' => $request->get('group'),
                    'anonymize' => $this->anonymousReport,
                ]
            );

            $report->setFormat(self::$mapFromExtension[$this->extension]['format']);

            $response = new StreamedResponse();

            $this->setHeadersFromFormat($response, $report);

            $response->setCallback(function () use ($report) {
                $report->render();
            });

            return $response;
        } else {
            return new RedirectResponse($this->appbox->getPhraseApplication()->path('report_dashboard'). "#report-connections");
        }
    }

    /**
     * route prod/report/downloads
     *
     * @param Request $request
     * @param $sbasId
     * @return RedirectResponse|StreamedResponse
     */
    public function downloadsAction(Request $request, $sbasId)
    {
        if ($request->isMethod("POST")) {
            if(!($extension = $request->get('format'))) {
                $extension = 'csv';
            }

            if(!array_key_exists($extension, self::$mapFromExtension)) {
                throw new \InvalidArgumentException(sprintf("bad format \"%s\" for report", $extension));
            }
            $this->extension = $extension;

            /** @var ReportActions $report */
            $report = $this->reportFactory->createReport(
                ReportFactory::DOWNLOADS,
                $sbasId,
                [
                    'dmin'      => $request->get('dmin'),
                    'dmax'      => $request->get('dmax'),
                    'group'     => $request->get('group'),
                    'bases'     => $request->get('base'),
                    'anonymize' => $this->anonymousReport,
                ]
            );

            $report->setFormat(self::$mapFromExtension[$this->extension]['format']);
            $report->setPermalink($request->get('permalink'));

            $response = new StreamedResponse();

            $this->setHeadersFromFormat($response, $report);

            $response->setCallback(function() use($report) {
                $report->render();
            });

            return $response;
        } else {
            return new RedirectResponse($this->appbox->getPhraseApplication()->path('report_dashboard'). "#report-downloads");
        }
    }

    /**
     * route prod/report/records
     *
     * @param Request $request
     * @param $sbasId
     * @return RedirectResponse|StreamedResponse
     */
    public function recordsAction(Request $request, $sbasId)
    {
        if ($request->isMethod("POST")) {
            if (!($extension = $request->get('format'))) {
                $extension = 'csv';
            }
            if (!array_key_exists($extension, self::$mapFromExtension)) {
                throw new \InvalidArgumentException(sprintf("bad format \"%s\" for report", $extension));
            }
            $this->extension = $extension;

            /** @var ReportRecords $report */
            $report = $this->reportFactory->createReport(
                ReportFactory::RECORDS,
                $sbasId,
                [
                    'dmin' => $request->get('dmin'),
                    'dmax' => $request->get('dmax'),
                    'group' => $request->get('group'),
                    'base' => $request->get('base'),
                    'meta' => $request->get('meta'),
                ]
            );

            $report->setFormat(self::$mapFromExtension[$this->extension]['format']);
            $report->setPermalink($request->get('permalink'));

            set_time_limit(600);
            $response = new StreamedResponse();

            $this->setHeadersFromFormat($response, $report);

            $response->setCallback(function () use ($report) {
                $report->render();
            });

            return $response;
        } else {
            return new RedirectResponse($this->appbox->getPhraseApplication()->path('report_dashboard'). "#report-records");
        }
    }


    private function setHeadersFromFormat($response, Report $report)
    {
        $response->headers->set('Content-Type', self::$mapFromExtension[$this->extension]['contentType']);
        $response->headers->set('Content-Disposition', 'attachment;filename="' . $report->getName() . '"');
        $response->headers->set('Cache-Control', 'max-age=0');
    }

}
