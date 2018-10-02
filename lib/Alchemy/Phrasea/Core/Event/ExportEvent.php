<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Event;

use Alchemy\Phrasea\Model\Entities\User;
use Symfony\Component\EventDispatcher\Event as SfEvent;

class ExportEvent extends SfEvent
{
    private $user;
    private $basketId;
    private $list;
    private $subdefs;
    private $exportFileName;

    public function __construct(User $user, $basketId, $list, $subdefs, $exportFileName)
    {
        $this->user = $user;
        $this->basketId = $basketId;
        $this->list = $list;
        $this->subdefs = $subdefs;
        $this->exportFileName = $exportFileName;
    }

    /**
     * @return mixed
     */
    public function getBasketId()
    {
        return $this->basketId;
    }

    /**
     * @return mixed
     */
    public function getList()
    {
        return $this->list;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @return mixed
     */
    public function getExportFileName()
    {
        return $this->exportFileName;
    }

    /**
     * @return mixed
     */
    public function getSubdefs()
    {
        return $this->subdefs;
    }
}
