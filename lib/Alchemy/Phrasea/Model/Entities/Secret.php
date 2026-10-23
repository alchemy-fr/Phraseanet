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

#[ORM\Entity(repositoryClass: \Alchemy\Phrasea\Model\Repositories\SecretRepository::class)]
#[ORM\Table(name: 'Secrets')]
class Secret
{
    /**
     * @var int
     */
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    private $id;

    /**
     * @var \DateTime
     */
    #[ORM\Column(type: 'datetime')]
    private $created;

    /**
     * @var string
     */
    #[ORM\Column(type: 'binary_string', length: 40)]
    private $token;

    /**
     * @var User
     */
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'creator_id', referencedColumnName: 'id', nullable: false)]
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
