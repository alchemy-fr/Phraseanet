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

interface WorkerFactory 
{
    /**
     * @return Worker
     */
    public function createWorker();
}
