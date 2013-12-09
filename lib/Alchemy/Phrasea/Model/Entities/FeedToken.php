<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Model\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="FeedTokens")
 * @ORM\Entity(repositoryClass="Alchemy\Phrasea\Model\Repositories\FeedTokenRepository")
 */
class FeedToken
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @ORM\Column(type="integer", name="usr_id")
     */
    private $usrId;

    /**
     * @ORM\Column(type="string", length=12, nullable=true)
     */
    private $value;

    /**
     * @ORM\ManyToOne(targetEntity="Feed", inversedBy="tokens", cascade={"persist"})
     * @ORM\JoinColumn(name="feed_id", referencedColumnName="id")
     */
    private $feed;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set usr_id
     *
     * @param  integer   $usrId
     * @return FeedToken
     */
    public function setUsrId($usrId)
    {
        $this->usrId = $usrId;

        return $this;
    }

    /**
     * Get usr_id
     *
     * @return integer
     */
    public function getUsrId()
    {
        return $this->usrId;
    }

    /**
     * Set feed
     *
     * @param  Feed      $feed
     * @return FeedToken
     */
    public function setFeed(Feed $feed = null)
    {
        $this->feed = $feed;

        return $this;
    }

    /**
     * Get feed
     *
     * @return Feed
     */
    public function getFeed()
    {
        return $this->feed;
    }

    /**
     * Set value
     *
     * @param  string    $value
     * @return FeedToken
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get value
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }
}
