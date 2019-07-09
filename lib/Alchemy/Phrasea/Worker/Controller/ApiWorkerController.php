<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Worker\Controller;

use Symfony\Component\HttpFoundation\Request;

use Alchemy\Phrasea\Controller\Controller;
use Alchemy\Phrasea\Application\Helper\DispatcherAware;
use Alchemy\Phrasea\Core\Event\WorkerableEvent;
use Alchemy\Phrasea\Controller\Api\Result;


class ApiWorkerController extends Controller
{
    use DispatcherAware;

    public function executeAction(Request $request)
    {
        $message = $request->getContent();
        /** @var WorkerableEvent $event */
        $o = WorkerableEvent::restoreFromWorkerMessage($message, $this->app);
        $name = $o['name'];
        $event = $o['event'];

        $event->setReplayed();

        $this->dispatch($name, $event);

        $ret = [
            'name' => $name,
            'payload' => $message
        ];

        // return $this->returnResourceResponse($request, ['elements'], $resource);
        $result = Result::create($request, $ret)->createResponse();

        return $result;
    }
}
