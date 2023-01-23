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
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="Tokens",
 *      indexes={
 *          @ORM\index(name="type", columns={"type"}),
 *          @ORM\index(name="created", columns={"created"}),
 *          @ORM\index(name="updated", columns={"updated"}),
 *          @ORM\index(name="expiration", columns={"expiration"})
 *      }
 * )
 * @ORM\Entity(repositoryClass="Alchemy\Phrasea\Model\Repositories\TokenRepository")
 */
class Token
{
    /**
     * @ORM\Id
     * @ORM\Column(type="string", length=128)
     */
    private $value;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=true)
     **/
    private $user;

    /**
     * @ORM\Column(type="string", length=32)
     */
    private $type;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $data;

    /**
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime")
     */
    private $created;

    /**
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="datetime")
     */
    private $updated;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $expiration;

    /**
     * Set value
     *
     * @param  string $value
     * @return Token
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

    /**
     * Set type
     *
     * @param  string $type
     * @return Token
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set data
     *
     * @param  string $data
     * @return Token
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Get data
     *
     * @return string
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Set created
     *
     * @param  \DateTime $created
     * @return Token
     */
    public function setCreated($created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * Get created
     *
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Set updated
     *
     * @param  \DateTime $updated
     * @return Token
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get updated
     *
     * @return \DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * Set expiration
     *
     * @param  \DateTime $expiration
     * @return Token
     */
    public function setExpiration(\DateTime $expiration = null)
    {
        $this->expiration = $expiration;

        return $this;
    }

    /**
     * Get expiration
     *
     * @return \DateTime
     */
    public function getExpiration()
    {
        return $this->expiration;
    }

    /**
     * Set user
     *
     * @param  User  $user
     * @return Token
     */
    public function setUser(User $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }
}
