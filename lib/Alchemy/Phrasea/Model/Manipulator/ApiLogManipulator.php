<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Model\Manipulator;

use Alchemy\Phrasea\Model\Entities\ApiAccount;
use Alchemy\Phrasea\Model\Entities\ApiLog;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiLogManipulator implements ManipulatorInterface
{
    private $om;
    private $repository;

    public function __construct(ObjectManager $om, EntityRepository $repo)
    {
        $this->om = $om;
        $this->repository = $repo;
    }

    public function create(ApiAccount $account, Request $request, Response $response)
    {
        $log = new ApiLog();
        $log->setAccount($account);
        $this->doSetFromHttpContext($log, $request, $response);

        $this->update($log);

        return $log;
    }

    public function delete(ApiLog $log)
    {
        $this->om->remove($log);
        $this->om->flush();
    }

    public function update(ApiLog $log)
    {
        $this->om->persist($log);
        $this->om->flush();
    }

    private function doSetFromHttpContext(ApiLog $log, Request $request, Response $response)
    {
        $log->setRoute($request->getPathInfo());
        $log->setMethod($request->getMethod());
        $log->setStatusCode($response->getStatusCode());
        $log->setFormat($response->headers->get('content-type'));
        $this->setDetails($log, $request, $response);
    }

    /**
     * Parses the requested route to fetch
     * - the resource (databox, basket, record etc ..)
     * - general action (list, add, search)
     * - the action (setstatus, setname etc..)
     * - the aspect (collections, related, content etc..)
     *
     * @param ApiLog   $log
     * @param Request  $request
     * @param Response $response
     */
    private function setDetails(ApiLog $log, Request $request, Response $response)
    {
        $chunks = explode('/', trim($request->getPathInfo(), '/'));

        if (false === $response->isOk() || sizeof($chunks) === 0) {
            return;
        }

        switch ($chunks[0]) {
            case ApiLog::DATABOXES_RESOURCE :
                $this->hydrateDataboxes($log, $chunks);
                break;
            case ApiLog::RECORDS_RESOURCE :
                $this->hydrateRecords($log, $chunks);
                break;
            case ApiLog::BASKETS_RESOURCE :
                $this->hydrateBaskets($log, $chunks);
                break;
            case ApiLog::FEEDS_RESOURCE :
                $this->hydrateFeeds($log, $chunks);
                break;
            case ApiLog::QUARANTINE_RESOURCE :
                $this->hydrateQuarantine($log, $chunks);
                break;
            case ApiLog::STORIES_RESOURCE :
                $this->hydrateStories($log, $chunks);
                break;
            case ApiLog::MONITOR_RESOURCE :
                $this->hydrateMonitor($log, $chunks);
                break;
        }
    }

    private function hydrateDataboxes(ApiLog $log, $chunks)
    {
        $log->setResource($chunks[0]);
        $log->setGeneral($chunks[0]);
        if (count($chunks) === 2) {
            $log->setAction($chunks[1]);
        }
        if ((int) $chunks[1] > 0 && count($chunks) === 3) {
            $log->setAspect($chunks[2]);
        }
    }

    private function hydrateRecords(ApiLog $log, $chunks)
    {
        $log->setResource($chunks[0]);
        $log->setGeneral($chunks[0]);
        if (count($chunks) === 2) {
            $log->setAction($chunks[1]);
        }
        if (count($chunks) === 3 && (int) $chunks[1] > 0 && (int) $chunks[2] > 0) {
            $log->setAction('get');
        }
        if ((int) $chunks[1] > 0 && (int) $chunks[2] > 0 && count($chunks) == 4) {
            if (preg_match("/^set/", $chunks[3])) {
                $log->setAction($chunks[3]);
            } else {
                $log->setAspect($chunks[3]);
            }
        }
    }

    private function hydrateBaskets(ApiLog $log, $chunks)
    {
        $log->setResource($chunks[0]);
        $log->setGeneral($chunks[0]);
        if (count($chunks) === 2) {
            $log->setAction($chunks[1]);
        }
        if ((int) $chunks[1] > 0 && count($chunks) == 3) {
            if (preg_match("/^set/", $chunks[2]) || preg_match("/^delete/", $chunks[2])) {
                $log->setAction($chunks[2]);
            } else {
                $log->setAspect($chunks[2]);
            }
        }
    }

    private function hydrateFeeds(ApiLog $log, $chunks)
    {
        $log->setResource($chunks[0]);
        $log->setGeneral($chunks[0]);
        if (count($chunks) === 2) {
            if (preg_match("/^content$/", $chunks[1])) {
                $log->setAspect($chunks[1]);
            } else {
                $log->setAction($chunks[1]);
            }
        }
        if (count($chunks) === 3) {
            if ((int) $chunks[1] > 0) {
                $log->setAspect($chunks[2]);
            }
            if (preg_match("/^entry$/", $chunks[1]) && (int) $chunks[2] > 0) {
                $log->setAspect($chunks[1]);
            }
        }
    }

    private function hydrateQuarantine(ApiLog $log, $chunks)
    {
        $log->setResource($chunks[0]);
        $log->setGeneral($chunks[0]);
        if (count($chunks) === 2) {
            $log->setAction($chunks[1]);
        }
    }

    private function hydrateStories(ApiLog $log, $chunks)
    {
        $log->setGeneral($chunks[0]);
        $log->setResource($chunks[0]);
        if ((int) $chunks[1] > 0 && (int) $chunks[2] > 0 && count($chunks) == 4) {
            $log->setAspect($chunks[3]);
        }
        if (count($chunks) === 3 && (int) $chunks[1] > 0 && (int) $chunks[2] > 0) {
            $log->setAction('get');
        }
    }

    private function hydrateMonitor(ApiLog $log, $chunks)
    {
        $log->setGeneral($chunks[0]);
        if (count($chunks) === 2) {
            $log->setAspect($chunks[1]);
        }
        if (count($chunks) === 3 && (int) $chunks[2] > 0) {
            $log->setAspect($chunks[1]);
            $log->setAction('get');
        }
        if (count($chunks) === 4) {
            $log->setAspect($chunks[1]);
            $log->setAction($chunks[3]);
        }
    }
}
