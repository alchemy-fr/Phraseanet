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

use Alchemy\Phrasea\Report\Report;
use Alchemy\Phrasea\Report\ReportConnections;
use Alchemy\Phrasea\Report\ReportDownloads;
use Alchemy\Phrasea\Report\ReportFactory;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;


class ProdReportController extends BaseReportController
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

    private $extension = null;


    /**
     * @param ReportFactory $reportFactory
     * @param Bool $anonymousReport
     * @param \ACL $acl
     */
    public function __construct(ReportFactory $reportFactory, $anonymousReport, \ACL $acl)
    {
        $this->reportFactory   = $reportFactory;
        $this->anonymousReport = $anonymousReport;
        $this->acl             = $acl;
    }

    /**
     * route prod/report/connections
     *
     * @param Request $request
     * @param $sbasId
     * @return StreamedResponse
     */
    public function connectionsAction(Request $request, $sbasId)
    {
        if(!($extension = $request->get('format'))) {
            $extension = 'csv';
        }
        if(!array_key_exists($extension, self::$mapFromExtension)) {
            throw new \InvalidArgumentException(sprintf("bad format \"%s\" for report", $extension));
        }
        $this->extension = $extension;

        /** @var ReportConnections $report */
        $report = $this->reportFactory->createReport(
            ReportFactory::CONNECTIONS,
            $sbasId,
            [
                'dmin'      => $request->get('dmin'),
                'dmax'      => $request->get('dmax'),
                'group'     => $request->get('group'),
                'anonymize' => $this->anonymousReport,
            ]
        );

        $report->setFormat(self::$mapFromExtension[$this->extension]['format']);

        $response = new StreamedResponse();

        $this->setHeadersFromFormat($response, $report);

        $response->setCallback(function() use($report) {
            $report->render();
        });

        return $response;
    }

    /**
     * route prod/report/downloads
     *
     * @param Request $request
     * @param $sbasId
     * @return StreamedResponse
     */
    public function downloadsAction(Request $request, $sbasId)
    {
        if(!($extension = $request->get('format'))) {
            $extension = 'csv';
        }
        if(!array_key_exists($extension, self::$mapFromExtension)) {
            throw new \InvalidArgumentException(sprintf("bad format \"%s\" for report", $extension));
        }
        $this->extension = $extension;

        /** @var ReportDownloads $report */
        $report = $this->reportFactory->createReport(
            ReportFactory::DOWNLOADS,
            $sbasId,
            [
                'dmin'      => $request->get('dmin'),
                'dmax'      => $request->get('dmax'),
                'group'     => $request->get('group'),
                'bases'     => $request->get('base[]'),
                'anonymize' => $this->anonymousReport,
           ]
        );

        $report->setFormat(self::$mapFromExtension[$this->extension]['format']);

        $response = new StreamedResponse();

        $this->setHeadersFromFormat($response, $report);

        $response->setCallback(function() use($report) {
            $report->render();
        });

        return $response;
    }


    private function setHeadersFromFormat($response, Report $report)
    {
        $response->headers->set('Content-Type', self::$mapFromExtension[$this->extension]['contentType']);
        $response->headers->set('Content-Disposition', 'attachment;filename="' . $report->getName() . '"');
        $response->headers->set('Cache-Control', 'max-age=0');
    }

}
