<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Event\Acl;

use Symfony\Component\EventDispatcher\Event;
use ACL;

abstract class AclEvent extends Event
{
    /** @var ACL */
    private $acl;

    /**@var array|null $args supplemental parameters specific to an inherited event class */
    protected $args;

    /**
     * @param ACL $acl
     * @param array|null $args
     */
    public function __construct(ACL $acl, array $args = null)
    {
        $this->acl = $acl;
        $this->args = $args;
    }

    /**
     * @return ACL
     */
    public function getAcl()
    {
        return $this->acl;
    }
}
