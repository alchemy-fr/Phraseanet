<?php

namespace Alchemy\Phrasea\Model\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="ApiOauthTokens", indexes={
 *      @ORM\Index(name="api_oauth_token_account_id", columns={"account_id"}),
 *      @ORM\Index(name="api_oauth_token_session_id", columns={"session_id"})
 * })
 * @ORM\Entity(repositoryClass="Alchemy\Phrasea\Model\Repositories\ApiOauthTokenRepository")
 */
class ApiOauthToken
{
    /**
     * @var string
     *
     * @ORM\Column(name="oauth_token", type="string", length=128, nullable=false)
     * @ORM\Id
     */
    private $oauthToken;

    /**
     * @ORM\Column(name="session_id", type="integer", nullable=true)
     */
    private $sessionId;

    /**
     * @ORM\ManyToOne(targetEntity="ApiAccount", inversedBy="tokens")
     * @ORM\JoinColumn(name="account_id", referencedColumnName="id", nullable=false)
     *
     * @return ApiAccount
     **/
    private $account;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $expires;

    /**
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime", name="last_used")
     */
    private $lastUsed;

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
        $account->addTokens($this);

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
     * @param integer $expires
     *
     * @return ApiOauthTokens
     */
    public function setExpires($expires = null)
    {
        $this->expires = $expires;

        return $this;
    }

    /**
     * @return integer
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
     * @param integer $sessionId
     *
     * @return ApiOauthTokens
     */
    public function setSessionId($sessionId)
    {
        $this->sessionId = $sessionId;

        return $this;
    }

    /**
     * @return Session
     */
    public function getSessionId()
    {
        return $this->sessionId;
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

    /**
     * @param \DateTime $lastUsed
     *
     * @return ApiOauthToken
     */
    public function setLastUsed(\DateTime $lastUsed)
    {
        $this->lastUsed = $lastUsed;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getLastUsed()
    {
        return $this->lastUsed;
    }
}
