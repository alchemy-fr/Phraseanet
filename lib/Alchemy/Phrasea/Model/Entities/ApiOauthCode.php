<?php

namespace Alchemy\Phrasea\Model\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="ApiOauthCodes", indexes={@ORM\Index(name="api_oauth_code_account_id", columns={"account_id"})})
 * @ORM\Entity(repositoryClass="Alchemy\Phrasea\Model\Repositories\ApiOauthCodeRepository")
 */
class ApiOauthCode
{
    /**
     * @var string
     *
     * @ORM\Column(name="code", type="string", length=128, nullable=false)
     * @ORM\Id
     */
    private $code;

    /**
     * @ORM\ManyToOne(targetEntity="ApiAccount")
     * @ORM\JoinColumn(name="account_id", referencedColumnName="id", nullable=false)
     *
     * @return ApiAccount
     **/
    private $account;

    /**
     * @var string
     *
     * @ORM\Column(name="redirect_uri", type="string", length=128, nullable=false)
     */
    private $redirectUri;

    /**
     * @ORM\Column(type="integer")
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
     * @return ApiOauthCode
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
     * @param string $code
     *
     * @return ApiOauthCode
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param \DateTime $created
     *
     * @return ApiOauthCode
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
     * @param integer $timestamp
     *
     * @return ApiOauthCode
     */
    public function setExpires($timestamp)
    {
        $this->expires = $timestamp;

        return $this;
    }

    /**
     * @return $timestamp
     */
    public function getExpires()
    {
        return $this->expires;
    }

    /**
     * @param string $redirectUri
     *
     * @return ApiOauthCode
     */
    public function setRedirectUri($redirectUri)
    {
        $this->redirectUri = $redirectUri;

        return $this;
    }

    /**
     * @return string
     */
    public function getRedirectUri()
    {
        return $this->redirectUri;
    }

    /**
     * @param string $scope
     *
     * @return ApiOauthCode
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
     * @return ApiOauthCode
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
