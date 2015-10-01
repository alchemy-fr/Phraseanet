<?php

namespace Alchemy\Phrasea\Core\Event;

use Symfony\Component\EventDispatcher\Event;

class AclDownloadQuotasOnBaseChanged extends AclRelated
{
    public function getBaseId()
    {
        return $this->args['base_id'];
    }

    public function getRemainingDownloads()
    {
        return $this->args['remain_dwnld'];
    }

    public function getMonthDownloadMax()
    {
        return $this->args['month_dwnld_max'];
    }
}
