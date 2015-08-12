<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Entities;

class Secret
{
    /** @var int */
    private $id;
    /** @var \DateTime */
    private $created;
    /** @var string */
    private $token;
    /** @var int */
    private $creatorId;

    public function __construct($creatorId, $token)
    {
        $this->created = new \DateTime();
        $this->creatorId = $creatorId;
        $this->token = $token;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @return int
     */
    public function getCreatorId()
    {
        return $this->creatorId;
    }
}
