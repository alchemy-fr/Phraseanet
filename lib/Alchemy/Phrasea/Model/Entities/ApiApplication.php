<?php

namespace Alchemy\Phrasea\Model\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="ApiApplications", uniqueConstraints={
 *      @ORM\UniqueConstraint(name="client_id", columns={"client_id"})}, indexes={
 *          @ORM\Index(name="api_application_creator_id", columns={"creator_id"})
 *      })
 * @ORM\Entity(repositoryClass="Alchemy\Phrasea\Model\Repositories\ApiApplicationRepository")
 */
class ApiApplication
{
    /** desktop application */
    const DESKTOP_TYPE = 'desktop';
    /** web application */
    const WEB_TYPE = 'web';
    /** Uniform Resource Name */
    const NATIVE_APP_REDIRECT_URI = "urn:ietf:wg:oauth:2.0:oob";

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="creator_id", referencedColumnName="id", nullable=true)
     *
     * @return User|null
     **/
    private $creator;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=128, nullable=false)
     */
    private $type;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=128, nullable=false)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(type="text", nullable=false)
     */
    private $description;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=128, nullable=false)
     */
    private $website;

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
     * @var string
     *
     * @ORM\Column(name="client_id", type="string", length=128, nullable=false)
     */
    private $clientId;

    /**
     * @var string
     *
     * @ORM\Column(name="client_secret", type="string", length=128, nullable=false)
     */
    private $clientSecret;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=64, nullable=false)
     */
    private $nonce;

    /**
     * @var string
     *
     * @ORM\Column(name="redirect_uri", type="string", length=128, nullable=false)
     */
    private $redirectUri;

    /**
     * @var integer
     *
     * @ORM\Column(type="boolean", nullable=false)
     */
    private $activated = true;

    /**
     * @var integer
     *
     * @ORM\Column(name="grant_password", type="boolean", nullable=false)
     */
    private $grantPassword = false;

    /**
     * @ORM\OneToMany(targetEntity="ApiAccount", mappedBy="application", cascade={"remove"})
     **/
    private $accounts;

    /**
     * @var string
     *
     * @ORM\Column(name="webhook_url", type="string", length=1024, nullable=true)
     */
    private $webhookUrl;

    /**
     * List of events to trigger webhook
     * @var array
     *
     * @ORM\Column(name="listened_events", type="json_array", nullable=true)
     */
    private $listenedEvents;

    /**
     * @var string
     *
     * @ORM\Column(name="hmac_key", type="string", length=1024, nullable=true)
     */
    private $hmacKey;

    /**
     * @var integer
     *
     * @ORM\Column(name="webhook_active", type="boolean", nullable=false)
     */
    private $webhookActive = false;

    public function __construct()
    {
        $this->accounts = new ArrayCollection();
    }

    /**
     * @param boolean $activated
     *
     * @return ApiApplication
     */
    public function setActivated($activated)
    {
        $this->activated = (Boolean) $activated;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isActivated()
    {
        return $this->activated;
    }

    /**
     * @param string $clientId
     *
     * @return ApiApplication
     */
    public function setClientId($clientId)
    {
        $this->clientId = $clientId;

        return $this;
    }

    /**
     * @return string
     */
    public function getClientId()
    {
        return $this->clientId;
    }

    /**
     * @param string $clientSecret
     *
     * @return ApiApplication
     */
    public function setClientSecret($clientSecret)
    {
        $this->clientSecret = $clientSecret;

        return $this;
    }

    /**
     * @return string
     */
    public function getClientSecret()
    {
        return $this->clientSecret;
    }

    /**
     * @param \DateTime $created
     *
     * @return ApiApplication
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
     * @param User $creator
     *
     * @return ApiApplication
     */
    public function setCreator(User $creator = null)
    {
        $this->creator = $creator;

        return $this;
    }

    /**
     * @return User|null
     */
    public function getCreator()
    {
        return $this->creator;
    }

    /**
     * @param string $description
     *
     * @return ApiApplication
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param boolean $grantPassword
     *
     * @return ApiApplication
     */
    public function setGrantPassword($grantPassword)
    {
        $this->grantPassword = (Boolean) $grantPassword;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isPasswordGranted()
    {
        return $this->grantPassword;
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $name
     *
     * @return ApiApplication
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $nonce
     *
     * @return ApiApplication
     */
    public function setNonce($nonce)
    {
        $this->nonce = $nonce;

        return $this;
    }

    /**
     * @return string
     */
    public function getNonce()
    {
        return $this->nonce;
    }

    /**
     * @param string $redirectUri
     *
     * @return ApiApplication
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
     * @param string $type
     *
     * @return ApiApplication
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param \DateTime $updated
     *
     * @return ApiApplication
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
     * @param string $website
     *
     * @return ApiApplication
     */
    public function setWebsite($website)
    {
        $this->website = $website;

        return $this;
    }

    /**
     * @return string
     */
    public function getWebsite()
    {
        return $this->website;
    }

    /**
     * @param string $webhookUrl
     */
    public function setWebhookUrl($webhookUrl)
    {
        $this->webhookUrl = $webhookUrl;
    }

    /**
     * @return string
     */
    public function getWebhookUrl()
    {
        return $this->webhookUrl;
    }

    /**
     * @param ApiAccount $account
     *
     * @return $this
     */
    public function addAccount(ApiAccount $account)
    {
        $this->accounts->add($account);

        return $this;
    }

    /**
     * @param array $listenedEvents
     */
    public function setListenedEvents(array $listenedEvents)
    {
        $this->listenedEvents = $listenedEvents;
    }

    /**
     * @param $eventName
     */
    public function addListenedEvent($eventName)
    {
        $this->listenedEvents[] = $eventName;
    }

    /**
     * @param $eventName
     * @return $this
     */
    public function removeListenedEvent($eventName)
    {
        $keys = array_keys($this->listenedEvents, $eventName, true);

        foreach ($keys as $key) {
            unset($this->listenedEvents[$key]);
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getListenedEvents()
    {
        return $this->listenedEvents;
    }

    public function getHmacKey()
    {
        return $this->hmacKey;
    }

    public function setHmacKey($hmacKey)
    {
        $this->hmacKey = $hmacKey;
    }

    /**
     * @return boolean
     */
    public function isWebhookActive()
    {
        return $this->webhookActive;
    }

    /**
     * @param boolean $webhookActive
     */
    public function setWebhookActive($webhookActive)
    {
        $this->webhookActive = (Boolean) $webhookActive;
    }
}
