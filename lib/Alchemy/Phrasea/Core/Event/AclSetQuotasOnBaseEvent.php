<?php

namespace Alchemy\Phrasea\Core\Event;

use Symfony\Component\EventDispatcher\Event;

class AclSetQuotasOnBaseEvent extends AclRelatedEvent
{
    public function getBaseId()
    {
        return $this->parms['base_id'];
    }

    public function getRemainingDownloads()
    {
        return $this->parms['remain_dwnld'];
    }

    public function getMonthDownloadMax()
    {
        return $this->parms['month_dwnld_max'];
    }
}
