<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
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
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     *
     * @return User
     **/
    private $user;

    /**
     * @ORM\Column(type="string", length=64, nullable=true)
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
     * @param User $user
     *
     * @return FeedToken
     */
    public function setUser(User $user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
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
