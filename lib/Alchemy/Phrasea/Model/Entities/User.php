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

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Exception\InvalidArgumentException;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="Users",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(name="email_unique",columns={"email"}),
 *          @ORM\UniqueConstraint(name="login_unique",columns={"login"})
 *      },
 *      indexes={
 *          @ORM\index(name="login", columns={"login"}),
 *          @ORM\index(name="mail", columns={"email"}),
 *          @ORM\index(name="model_of", columns={"model_of"}),
 *          @ORM\index(name="salted_password", columns={"salted_password"}),
 *          @ORM\index(name="admin", columns={"admin"}),
 *          @ORM\index(name="guest", columns={"guest"})
 *      }
 * )
 * @ORM\Entity(repositoryClass="Alchemy\Phrasea\Model\Repositories\UserRepository")
 */
class User
{
    const GENDER_MR = 2;
    const GENDER_MRS = 1;
    const GENDER_MISS = 0;

    const USER_GUEST = 'guest';
    const USER_AUTOREGISTER = 'autoregister';

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @ORM\Column(type="binary_string", length=128)
     */
    private $login;

    /**
     * @ORM\Column(type="string", length=128, nullable=true)
     */
    private $email;

    /**
     * The password can be null when the user is a template.
     *
     * @ORM\Column(type="binary_string", length=128, nullable=true)
     */
    private $password;

    /**
     * @ORM\Column(type="binary_string", length=64, nullable=true)
     */
    private $nonce;

    /**
     * @ORM\Column(type="boolean", name="salted_password", options={"default" = 1})
     */
    private $saltedPassword = true;

    /**
     * @ORM\Column(type="string", length=64, name="first_name", options={"default" = ""})
     */
    private $firstName = '';

    /**
     * @ORM\Column(type="string", length=64, name="last_name", options={"default" = ""})
     */
    private $lastName = '';

    /**
     * @ORM\Column(type="smallint", nullable=true)
     */
    private $gender;

    /**
     * @ORM\Column(type="string", length=255, options={"default" = ""})
     */
    private $address = '';

    /**
     * @ORM\Column(type="string", length=64, options={"default" = ""})
     */
    private $city = '';

    /**
     * @ORM\Column(type="string", length=64, nullable=true, options={"default" = ""})
     */
    private $country = '';

    /**
     * @ORM\Column(type="string", length=32, name="zip_code", options={"default" = ""})
     */
    private $zipCode = '';

    /**
     * @ORM\Column(type="integer", name="geoname_id", nullable=true)
     */
    private $geonameId;

    /**
     * @ORM\Column(type="string", length=8, nullable=true)
     */
    private $locale;

    /**
     * @ORM\Column(type="string", length=128, options={"default" = ""})
     */
    private $timezone = '';

    /**
     * @ORM\Column(type="string", length=128, options={"default" = ""})
     */
    private $job = '';

    /**
     * @ORM\Column(type="string", length=256, options={"default" = ""})
     */
    private $activity = '';

    /**
     * @ORM\Column(type="string", length=64, options={"default" = ""})
     */
    private $company = '';

    /**
     * @ORM\Column(type="string", length=32, options={"default" = ""})
     */
    private $phone = '';

    /**
     * @ORM\Column(type="string", length=32, options={"default" = ""})
     */
    private $fax = '';

    /**
     * @ORM\Column(type="boolean", options={"default" = 0})
     */
    private $admin = false;

    /**
     * @ORM\Column(type="boolean", options={"default" = 0})
     */
    private $guest = false;

    /**
     * @ORM\Column(type="boolean", name="mail_notifications", options={"default" = 0})
     */
    private $mailNotificationsActivated = false;

    /**
     * @ORM\Column(type="boolean", name="request_notifications", options={"default" = 0})
     */
    private $requestNotificationsActivated = false;

    /**
     * @ORM\Column(type="boolean", name="ldap_created", options={"default" = 0})
     */
    private $ldapCreated = false;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="last_model", referencedColumnName="id")
     **/
    private $lastAppliedTemplate;

    /**
     * @ORM\Column(type="string", length=255, name="push_list", options={"default" = ""})
     */
    private $pushList = '';

    /**
     * @ORM\Column(type="boolean", name="can_change_profil", options={"default" = 1})
     */
    private $canChangeProfil = true;

    /**
     * @ORM\Column(type="boolean", name="can_change_ftp_profil", options={"default" = 1})
     */
    private $canChangeFtpProfil = true;

    /**
     * @ORM\Column(type="datetime", name="last_connection", nullable=true)
     */
    private $lastConnection;

    /**
     * @ORM\Column(type="boolean", name="mail_locked", options={"default" = 0})
     */
    private $mailLocked = false;

    /**
     * @ORM\Column(type="boolean", options={"default" = 0})
     */
    private $deleted = false;

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
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="model_of", referencedColumnName="id")
     *
     * @var User
     **/
    private $templateOwner;

     /**
     * @ORM\OneToOne(targetEntity="FtpCredential", mappedBy="user", cascade={"all"})
      *
      * @var FtpCredential
     **/
    private $ftpCredential;

    /**
     * @ORM\OneToMany(targetEntity="UserQuery", mappedBy="user", cascade={"all"})
     *
     * @var UserQuery[]
     **/
    private $queries;

    /**
     * @ORM\OneToMany(targetEntity="UserSetting", mappedBy="user", cascade={"all"}, indexBy="name")
     *
     * @var UserSetting[]
     **/
    private $settings;

    /**
     * @ORM\OneToMany(targetEntity="UserNotificationSetting", mappedBy="user", cascade={"all"}, indexBy="name")
     *
     * @var UserNotificationSetting[]
     **/
    private $notificationSettings;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->queries = new ArrayCollection();
        $this->notificationSettings = new ArrayCollection();
        $this->settings = new ArrayCollection();
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getLogin()
    {
        return $this->login;
    }

    /**
     * @param string $login
     */
    public function setLogin($login)
    {
        $this->login = $login;

        return $this;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $email
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     *
     * @param string $password
     */
    public function setPassword($password)
    {
        $this->password = $password;

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
     * @param string $nonce
     */
    public function setNonce($nonce)
    {
        $this->nonce = $nonce;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isSaltedPassword()
    {
        return $this->saltedPassword;
    }

    /**
     * @param boolean $saltedPassword
     */
    public function setSaltedPassword($saltedPassword)
    {
        $this->saltedPassword = (Boolean) $saltedPassword;

        return $this;
    }

    /**
     * @return string
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * @param string $firstName
     */
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;

        return $this;
    }

    /**
     * @return string
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     *
     * @param string $lastName
     */
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * @return string
     */
    public function getGender()
    {
        return $this->gender;
    }

    /**
     * @param string $gender
     *
     * @return $this
     * @throws InvalidArgumentException
     */
    public function setGender($gender)
    {
        if (null !== $gender) {
            $gender = (string)$gender;

        }

        if (!in_array($gender, [
            null,
            (string)self::GENDER_MISS,
            (string)self::GENDER_MR,
            (string)self::GENDER_MRS,
        ], true)) {
            throw new InvalidArgumentException(sprintf("Invalid gender %s.", $gender));
        }

        $this->gender = $gender ? (int)$gender : null;

        return $this;
    }

    /**
     * @return string
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * @param string $address
     */
    public function setAddress($address)
    {
        $this->address = $address;

        return $this;
    }

    /**
     * @return string
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * @param string $city
     */
    public function setCity($city)
    {
        $this->city = $city;

        return $this;
    }

    /**
     * @return string
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * @param string $country
     */
    public function setCountry($country)
    {
        $this->country = $country;

        return $this;
    }

    /**
     * @return string
     */
    public function getZipCode()
    {
        return $this->zipCode;
    }

    /**
     * @param string $zipCode
     */
    public function setZipCode($zipCode)
    {
        $this->zipCode = $zipCode;

        return $this;
    }

    /**
     * @return integer
     */
    public function getGeonameId()
    {
        return $this->geonameId;
    }

    /**
     * @param integer $geonameId
     */
    public function setGeonameId($geonameId)
    {
        if (null !== $geonameId && $geonameId < 1) {
            throw new InvalidArgumentException(sprintf('Invalid geonameid %s.', $geonameId));
        }

        $this->geonameId = $geonameId;

        return $this;
    }

    /**
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * @param string $locale
     *
     * @throws InvalidArgumentException
     */
    public function setLocale($locale)
    {
        if (null !== $locale && !array_key_exists($locale, Application::getAvailableLanguages())) {
            throw new InvalidArgumentException(sprintf('Invalid locale %s.', $locale));
        }

        $this->locale = $locale;

        return $this;
    }

    /**
     * @return string
     */
    public function getTimezone()
    {
        return $this->timezone;
    }

    /**
     * @param string $timezone
     */
    public function setTimezone($timezone)
    {
        $this->timezone = $timezone;

        return $this;
    }

    /**
     * @return string
     */
    public function getJob()
    {
        return $this->job;
    }

    /**
     * @param string $job
     */
    public function setJob($job)
    {
        $this->job = $job;

        return $this;
    }

    /**
     * @return string
     */
    public function getActivity()
    {
        return $this->activity;
    }

    /**
     * @param string $activity
     */
    public function setActivity($activity)
    {
        $this->activity = $activity;

        return $this;
    }

    /**
     * @return string
     */
    public function getCompany()
    {
        return $this->company;
    }

    /**
     * @param string $company
     */
    public function setCompany($company)
    {
        $this->company = $company;

        return $this;
    }

    /**
     * @return string
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * @param string $phone
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;

        return $this;
    }

    /**
     * @return string
     */
    public function getFax()
    {
        return $this->fax;
    }

    /**
     * @param string $fax
     */
    public function setFax($fax)
    {
        $this->fax = $fax;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isAdmin()
    {
        return $this->admin;
    }

    /**
     * @param boolean $admin
     */
    public function setAdmin($admin)
    {
        $this->admin = (Boolean) $admin;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isGuest()
    {
        return $this->guest;
    }

    /**
     * @param boolean $guest
     */
    public function setGuest($guest)
    {
        $this->guest = (Boolean) $guest;

        return $this;
    }

    /**
     * @return boolean
     */
    public function hasMailNotificationsActivated()
    {
        return $this->mailNotificationsActivated;
    }

    /**
     * @param boolean $mailNotifications
     */
    public function setMailNotificationsActivated($mailNotifications)
    {
        $this->mailNotificationsActivated = (Boolean) $mailNotifications;

        return $this;
    }

    /**
     * @return boolean
     */
    public function hasRequestNotificationsActivated()
    {
        return $this->requestNotificationsActivated;
    }

    /**
     * @param boolean $requestNotifications
     */
    public function setRequestNotificationsActivated($requestNotifications)
    {
        $this->requestNotificationsActivated = (Boolean) $requestNotifications;

        return $this;
    }

    /**
     * @return boolean
     */
    public function hasLdapCreated()
    {
        return $this->ldapCreated;
    }

    /**
     * @param boolean $ldapCreated
     */
    public function setLdapCreated($ldapCreated)
    {
        $this->ldapCreated = (Boolean) $ldapCreated;

        return $this;
    }

    /**
     * @return User
     */
    public function getTemplateOwner()
    {
        return $this->templateOwner;
    }

    /**
     * @param User $owner
     */
    public function setTemplateOwner(User $owner)
    {
        $this->templateOwner = $owner;

        return $this;
    }

    /**
     * @return User
     */
    public function getLastAppliedTemplate()
    {
        return $this->lastAppliedTemplate;
    }

    /**
     * @param User $lastAppliedTemplate
     */
    public function setLastAppliedTemplate(User $lastAppliedTemplate)
    {
        $this->lastAppliedTemplate = $lastAppliedTemplate;

        return $this;
    }

    /**
     * @return string
     */
    public function getPushList()
    {
        return $this->pushList;
    }

    /**
     * @param string $pushList
     */
    public function setPushList($pushList)
    {
        $this->pushList = $pushList;

        return $this;
    }

    /**
     * @return boolean
     */
    public function canChangeProfil()
    {
        return $this->canChangeProfil;
    }

    /**
     * @param boolean $canChangeProfil
     */
    public function setCanChangeProfil($canChangeProfil)
    {
        $this->canChangeProfil = (Boolean) $canChangeProfil;

        return $this;
    }

    /**
     * @return boolean
     */
    public function canChangeFtpProfil()
    {
        return $this->canChangeFtpProfil;
    }

    /**
     * @param boolean $canChangeFtpProfil
     */
    public function setCanChangeFtpProfil($canChangeFtpProfil)
    {
        $this->canChangeFtpProfil = (Boolean) $canChangeFtpProfil;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getLastConnection()
    {
        return $this->lastConnection;
    }

    /**
     * @param \DateTime $lastConnection
     */
    public function setLastConnection(\DateTime $lastConnection)
    {
        $this->lastConnection = $lastConnection;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isMailLocked()
    {
        return $this->mailLocked;
    }

    /**
     * @param boolean $mailLocked
     */
    public function setMailLocked($mailLocked)
    {
        $this->mailLocked = (Boolean) $mailLocked;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isDeleted()
    {
        return $this->deleted;
    }

    /**
     * @param boolean $deleted
     *
     * @return User
     */
    public function setDeleted($deleted)
    {
        $this->deleted = (Boolean) $deleted;

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
     * @return \DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * @param \Datetime $created
     */
    public function setCreated(\Datetime $created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * @param \Datetime $updated
     */
    public function setUpdated(\Datetime $updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * @return FtpCredential
     */
    public function getFtpCredential()
    {
        return $this->ftpCredential;
    }

    /**
     * @param FtpCredential $ftpCredential
     *
     * @return User
     */
    public function setFtpCredential(FtpCredential $ftpCredential = null)
    {
        $this->ftpCredential = $ftpCredential;

        return $this;
    }

    /**
     * @return ArrayCollection
     */
    public function getQueries()
    {
        return $this->queries;
    }

    /**
     * @param UserQuery $query
     *
     * @return User
     */
    public function addQuery(UserQuery $query)
    {
        $this->queries->add($query);

        return $this;
    }

    /**
     * @return ArrayCollection
     */
    public function getSettings()
    {
        return $this->settings;
    }

    /**
     * @param UserSetting $setting
     *
     * @return User
     */
    public function addSetting(UserSetting $setting)
    {
        $this->settings->set($setting->getName(), $setting);

        return $this;
    }

    /**
     * @return ArrayCollection
     */
    public function getNotificationSettings()
    {
        return $this->notificationSettings;
    }

    /**
     * @param UserNotificationSetting $notificationSetting
     *
     * @return User
     */
    public function addNotificationSettings(UserNotificationSetting $notificationSetting)
    {
        $this->notificationSettings->set($notificationSetting->getName(), $notificationSetting);

        return $this;
    }

    /**
     * @return boolean
     */
    public function isTemplate()
    {
        return null !== $this->templateOwner;
    }

    /**
     * @return boolean
     */
    public function isSpecial()
    {
        return in_array($this->login, [self::USER_GUEST, self::USER_AUTOREGISTER]);
    }

    /**
     * @return string
     */
    public function getDisplayName()
    {
        if ($this->isTemplate()) {
            return $this->getLogin();
        }

        if (trim($this->lastName) !== '' || trim($this->firstName) !== '') {
           return $this->firstName . ('' !== $this->firstName && '' !== $this->lastName ? ' ' : '') . $this->lastName;
        }

        if (trim($this->email) !== '') {
            return $this->email;
        }

        if ('' !== trim($this->getLogin())) {
            return $this->getLogin();
        }

        return 'Unnamed user';
    }
}
