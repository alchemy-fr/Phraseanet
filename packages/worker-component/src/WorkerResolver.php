<?php

/*
 * This file is part of alchemy/worker-component.
 *
 * (c) Alchemy <info@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Worker;

interface WorkerResolver
{

    /**
     * @param string $messageType
     * @param array $message
     * @return bool
     */
    public function hasWorker($messageType, array $message);

    /**
     * @param string $messageType
     * @param array $message
     * @return Worker
     */
    public function getWorker($messageType, array $message);
}
