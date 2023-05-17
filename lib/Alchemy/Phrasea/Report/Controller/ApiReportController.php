<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Report\Controller;

use Alchemy\Phrasea\Application\Helper\JsonBodyAware;
use Alchemy\Phrasea\Controller\Api\Result;
use Alchemy\Phrasea\Report\ReportConnections;
use Alchemy\Phrasea\Report\ReportActions;
use Alchemy\Phrasea\Report\ReportFactory;
use Alchemy\Phrasea\Report\ReportRecords;
use Alchemy\Phrasea\Report\ReportService;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpFoundation\Request;


class ApiReportController
{
    use JsonBodyAware;

    private $reportFactory;
    private $reportService;
    private $anonymousReport;
    private $acl;


    /**
     * @param ReportFactory $reportFactory
     * @param ReportService $reportService
     * @param Bool $anonymousReport
     * @param \ACL $acl
     */
    public function __construct(ReportFactory $reportFactory, ReportService $reportService, $anonymousReport, \ACL $acl)
    {
        $this->reportFactory   = $reportFactory;
        $this->reportService   = $reportService;
        $this->anonymousReport = $anonymousReport;
        $this->acl             = $acl;
    }

    /**
     * route api/report
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function rootAction(Request $request)
    {
        $ret = [
            'granted' => $this->reportService->getGranted()
        ];

        $result = Result::create($request, $ret);

        return $result->createResponse();
    }

    /**
     * route api/report/connections
     *
     * @param Request $request
     * @param $sbasId
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function connectionsAction(Request $request, $sbasId)
    {
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

        $result = Result::create($request, $report->getContent());

        return $result->createResponse();
    }

    /**
     * route api/report/downloads
     *
     * @param Request $request
     * @param $sbasId
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function downloadsAction(Request $request, $sbasId)
    {
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

        $result = Result::create($request, $report->getContent());

        return $result->createResponse();
    }

    /**
     * route api/report/records
     *
     * @param Request $request
     * @param $sbasId
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function recordsAction(Request $request, $sbasId)
    {
        /** @var ReportRecords $report */
        $report = $this->reportFactory->createReport(
            ReportFactory::RECORDS,
            $sbasId,
            [
                'dmin'  => $request->get('dmin'),
                'dmax'  => $request->get('dmax'),
                'group' => $request->get('group'),
                'base'  => $request->get('base'),
                'meta'  => $request->get('meta'),
            ]
        );

        $result = Result::create($request, $report->getContent());

        return $result->createResponse();
    }

}
