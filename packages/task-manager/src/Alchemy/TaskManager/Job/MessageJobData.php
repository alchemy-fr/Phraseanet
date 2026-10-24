<?php

/*
 * This file is part of Alchemy Task Manager
 *
 * (c) 2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\TaskManager\Job;

class MessageJobData implements JobDataInterface
{
    private $message;

    public function __construct($message)
    {
        $this->message = (string) $message;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return $this->message;
    }
}
