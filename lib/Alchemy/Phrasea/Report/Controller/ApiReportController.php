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
use Alchemy\Phrasea\Report\ReportConnectionsService;
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
        /** @var ReportConnectionsService $connectionsReport */
        $connectionsReport = $this->app['report.connections'];

        $ret = [
            'connections' => $connectionsReport->getConnections($request, $sbasId)
        ];

        $result = Result::create($request, $ret);

        return $result->createResponse();
    }

}
