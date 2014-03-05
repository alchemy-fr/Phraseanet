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

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Authentication\ACLProvider;
use Alchemy\Phrasea\Model\Entities\ApiAccount;
use Alchemy\Phrasea\Model\Entities\ApiLog;
use Alchemy\Phrasea\Model\Entities\User;
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
        $resource = $general = $aspect = $action = null;
        $chunks = explode('/', trim($request->getPathInfo(), '/'));

        if (false === $response->isOk() || sizeof($chunks) === 0) {
            return;
        }
        $resource = $chunks[0];

        if (count($chunks) == 2 && (int) $chunks[1] == 0) {
            $general = $chunks[1];
        } else {
            switch ($resource) {
                case ApiLog::DATABOXES_RESOURCE :
                    if ((int) $chunks[1] > 0 && count($chunks) == 3) {
                        $aspect = $chunks[2];
                    }
                    break;
                case ApiLog::RECORDS_RESOURCE :
                    if ((int) $chunks[1] > 0 && count($chunks) == 4) {
                        if (!isset($chunks[3])) {
                            $aspect = "record";
                        } elseif (preg_match("/^set/", $chunks[3])) {
                            $action = $chunks[3];
                        } else {
                            $aspect = $chunks[3];
                        }
                    }
                    break;
                case ApiLog::BASKETS_RESOURCE :
                    if ((int) $chunks[1] > 0 && count($chunks) == 3) {
                        if (preg_match("/^set/", $chunks[2]) || preg_match("/^delete/", $chunks[2])) {
                            $action = $chunks[2];
                        } else {
                            $aspect = $chunks[2];
                        }
                    }
                    break;
                case ApiLog::FEEDS_RESOURCE :
                    if ((int) $chunks[1] > 0 && count($chunks) == 3) {
                        $aspect = $chunks[2];
                    }
                    break;
            }
        }

        $log->setResource($resource);
        $log->setGeneral($general);
        $log->setAspect($aspect);
        $log->setAction($action);
    }
}
