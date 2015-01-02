<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Event\DataboxEvent;

class DeleteStatusEvent extends DataboxEvent
{
    private $status;

    public function __construct(\databox $databox, array $status)
    {
        $this->status = $status;

        parent::__construct($databox);
    }

    public function getStatus()
    {
        return $this->status;
    }
}
