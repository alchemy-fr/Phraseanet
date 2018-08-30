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

use Alchemy\Phrasea\Application\Helper\DelivererAware;
use Alchemy\Phrasea\Application\Helper\FilesystemAware;
use Alchemy\Phrasea\Application\Helper\JsonBodyAware;
use Alchemy\Phrasea\Controller\Api\Result;
use Alchemy\Phrasea\Report\Report;
use Alchemy\Phrasea\Report\ReportConnections;
use Alchemy\Phrasea\Report\ReportDownloads;
use Alchemy\Phrasea\Report\ReportFactory;
use Alchemy\Phrasea\Report\ReportRootService;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpFoundation\Request;


class ApiReportController extends BaseReportController
{
    use DelivererAware;
    use FilesystemAware;
    use JsonBodyAware;

    public function rootAction(Request $request)
    {
        /** @var ReportRootService $rootReport */
        $rootReport = $this->app['report.root'];

        $ret = [
            'granted' => $rootReport->getGranted()
        ];

        $result = Result::create($request, $ret);

        return $result->createResponse();
    }

    public function connectionsAction(Request $request, $sbasId)
    {
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

        $report->setFormat(Report::FORMAT_CSV);

        $report->render();

        return null;
/*
        $ret = $report->getContent();
        $result = Result::create($request, $ret);
        return $result->createResponse();
*/
    }

    public function downloadsAction(Request $request, $sbasId)
    {
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

        $ret = $report->getContent();
        $result = Result::create($request, $ret);
        return $result->createResponse();
    }

}
