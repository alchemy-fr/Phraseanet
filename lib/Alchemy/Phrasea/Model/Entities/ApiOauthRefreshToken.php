<?php

namespace Alchemy\Phrasea\Model\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="ApiOauthRefreshTokens", indexes={@ORM\Index(name="api_oauth_refresh_token_account_id", columns={"account_id"})})
 * @ORM\Entity(repositoryClass="Alchemy\Phrasea\Model\Repositories\ApiOauthRefreshTokenRepository")
 */
class ApiOauthRefreshToken
{
    /**
     * @var string
     *
     * @ORM\Column(name="refresh_token", type="string", length=128, nullable=false)
     * @ORM\Id
     */
    private $refreshToken;

    /**
     * @ORM\ManyToOne(targetEntity="ApiAccount")
     * @ORM\JoinColumn(name="account_id", referencedColumnName="id", nullable=false)
     *
     * @return ApiAccount
     **/
    private $account;

    /**
     * @ORM\Column(type="integer", nullable=false)
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
     * @return ApiOauthRefreshToken
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
     * @return ApiOauthRefreshToken
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
     * @return ApiOauthRefreshToken
     */
    public function setExpires($expires)
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
     * @param string $refreshToken
     *
     * @return ApiOauthRefreshToken
     */
    public function setRefreshToken($refreshToken)
    {
        $this->refreshToken = $refreshToken;

        return $this;
    }

    /**
     * @return string
     */
    public function getRefreshToken()
    {
        return $this->refreshToken;
    }

    /**
     * @param string $scope
     *
     * @return ApiOauthRefreshToken
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
     * @param \DateTime $updated
     *
     * @return ApiOauthRefreshToken
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
