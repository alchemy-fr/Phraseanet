<?php

/*
 * This file is part of Alchemy Task Manager
 *
 * (c) 2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\TaskManager\Event;

use Alchemy\TaskManager\TaskManager;

class TaskManagerRequestEvent extends TaskManagerEvent
{
    private $request;
    private $response;

    public function __construct(TaskManager $manager, $request, $response)
    {
        parent::__construct($manager);

        $this->request = $request;
        $this->response = $response;
    }

    /**
     * Returns the initial request.
     *
     * @return string
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Returns the response that will be set.
     *
     * @return mixed
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Sets the response that will be sent.
     *
     * @param mixed $response
     */
    public function setResponse($response)
    {
        if ($this->isPropagationStopped()) {
            return;
        }
        $this->response = $response;
    }
}
