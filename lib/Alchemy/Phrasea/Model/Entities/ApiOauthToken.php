<?php

namespace Alchemy\Phrasea\Model\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="ApiOauthTokens", indexes={@ORM\Index(name="account_id", columns={"account_id"}), @ORM\Index(name="session_id", columns={"session_id"})})
 * @ORM\Entity(repositoryClass="Alchemy\Phrasea\Model\Repositories\ApiOauthTokenRepository")
 */
class ApiOauthToken
{
    /**
     * @var string
     *
     * @ORM\Column(name="oauth_token", type="string", length=32, nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $oauthToken;

    /**
     * @ORM\ManyToOne(targetEntity="Session")
     * @ORM\JoinColumn(name="session_id", referencedColumnName="id")
     */
    private $session;

    /**
     * @OneToOne(targetEntity="ApiAccount", mappedBy="oauthToken", nullable=false)
     *
     * @return ApiAccount
     **/
    private $account;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $expires;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=128, nullable=true)
     */
    private $scope;

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
     * @param ApiAccount $account
     *
     * @return ApiOauthTokens
     */
    public function setAccount(ApiAccount $account)
    {
        $this->account = $account;

        return $this;
    }

    /**
     * @return ApiAccount
     */
    public function getAccount()
    {
        return $this->account;
    }

    /**
     * @param \DateTime $created
     *
     * @return ApiOauthTokens
     */
    public function setCreated(\DateTime $created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @param \DateTime $expires
     *
     * @return ApiOauthTokens
     */
    public function setExpires(\DateTime $expires)
    {
        $this->expires = $expires;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getExpires()
    {
        return $this->expires;
    }

    /**
     * @param string $oauthToken
     *
     * @return ApiOauthTokens
     */
    public function setOauthToken($oauthToken)
    {
        $this->oauthToken = $oauthToken;

        return $this;
    }

    /**
     * @return string
     */
    public function getOauthToken()
    {
        return $this->oauthToken;
    }

    /**
     * @param string $scope
     *
     * @return ApiOauthTokens
     */
    public function setScope($scope)
    {
        $this->scope = $scope;

        return $this;
    }

    /**
     * @return string
     */
    public function getScope()
    {
        return $this->scope;
    }

    /**
     * @param Session $session
     *
     * @return ApiOauthTokens
     */
    public function setSession(Session $session)
    {
        $this->session = $session;

        return $this;
    }

    /**
     * @return Session
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * @param \DateTime $updated
     *
     * @return ApiOauthTokens
     */
    public function setUpdated(\DateTime $updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
    }
}
