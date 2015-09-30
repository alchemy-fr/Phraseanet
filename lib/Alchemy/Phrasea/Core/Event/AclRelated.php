<?php

namespace Alchemy\Phrasea\Core\Event;

use Symfony\Component\EventDispatcher\Event;
use ACL;

class AclRelated extends Event
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
