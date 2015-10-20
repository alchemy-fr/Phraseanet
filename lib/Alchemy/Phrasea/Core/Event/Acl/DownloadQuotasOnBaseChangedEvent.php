<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Event\Acl;

class DownloadQuotasOnBaseChangedEvent extends AclEvent
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
