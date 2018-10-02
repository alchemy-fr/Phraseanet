<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Model\Entities\User;

abstract class eventsmanager_notifyAbstract
{
    /** @var null|string */
    protected $group = null;
    /** @var Application */
    protected $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function get_group()
    {
        return $this->group;
    }

    public function is_available(User $user)
    {
        return true;
    }

    public function email()
    {
        return true;
    }

    abstract public function get_name();

    abstract public function datas(array $data, $unread);

    abstract public function icon_url();
}
