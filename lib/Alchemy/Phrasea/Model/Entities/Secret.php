<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Phrasea\Model\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="Secrets")
 * @ORM\Entity(repositoryClass="Alchemy\Phrasea\Model\Repositories\SecretRepository")
 */
class Secret
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     * @var int
     */
    private $id;

    /**
     * @ORM\Column(type="datetime")
     * @var \DateTime
     */
    private $created;

    /**
     * @ORM\Column(type="binary_string", length=40)
     * @var string
     */
    private $token;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="creator_id", referencedColumnName="id", nullable=false)
     * @var User
     */
    private $creator;

    /**
     * @param User   $creator
     * @param string $token
     */
    public function __construct(User $creator, $token)
    {
        $this->created = new \DateTime();
        $this->creator = $creator;
        $this->token = (string) $token;
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
     * @return User
     */
    public function getCreator()
    {
        return $this->creator;
    }

    /**
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }
}
