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

    private $extension = null;


    public function connectionsAction(Request $request, $sbasId)
    {
        if(!($extension = $request->get('format'))) {
            $extension = 'csv';
        }
        if(!array_key_exists($extension, self::$mapFromExtension)) {
            throw new \InvalidArgumentException(sprintf("bad format \"%s\" for report", $extension));
        }
        $this->extension = $extension;


        /** @var ReportFactory $reportFactory */
        $reportFactory = $this->app['report.factory'];

        /** @var ReportConnections $report */
        $report = $reportFactory->createReport(
            ReportFactory::CONNECTIONS,
            $sbasId,
            [
                'dmin' => $request->get('dmin'),
                'dmax' => $request->get('dmax'),
                'group' => $request->get('group'),
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

    public function downloadsAction(Request $request, $sbasId)
    {
        if(!($type = $request->get('type'))) {
            $type = 'csv';
        }
        if(!array_key_exists($type, self::$mapFromExtension)) {
            throw new \InvalidArgumentException(sprintf("bad format \"%s\" for report", $type));
        }


        /** @var ReportFactory $reportFactory */
        $reportFactory = $this->app['report.factory'];

        /** @var ReportDownloads $report */
        $report = $reportFactory->createReport(
            ReportFactory::DOWNLOADS,
            $sbasId,
            [
                'dmin' => $request->get('dmin'),
                'dmax' => $request->get('dmax'),
                'group' => $request->get('group'),
                'bases' => $request->get('base[]')
            ]
        );

        $report->setFormat(self::$mapFromExtension[$type]['format']);

        $response = new StreamedResponse();

        $this->setHeadersFromFormat($response, $type);

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
