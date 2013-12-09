<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
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
use Symfony\Component\Translation\TranslatorInterface;

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
    const GENDER_MR = 'mr';
    const GENDER_MRS = 'mrs';
    const GENDER_MISS = 'miss';

    const USER_GUEST = 'guest';
    const USER_AUTOREGISTER = 'autoregister';

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=128)
     */
    private $login;

    /**
     * @ORM\Column(type="string", length=128, nullable=true)
     */
    private $email;

    /**
     * @ORM\Column(type="string", length=128, nullable=true)
     */
    private $password;

    /**
     * @ORM\Column(type="string", length=16, nullable=true)
     */
    private $nonce;

    /**
     * @ORM\Column(type="boolean", name="salted_password")
     */
    private $saltedPassword = false;

    /**
     * @ORM\Column(type="string", length=64, name="first_name")
     */
    private $firstName = '';

    /**
     * @ORM\Column(type="string", length=64, name="last_name")
     */
    private $lastName = '';

    /**
     * @ORM\Column(type="string", length=8, nullable=true)
     */
    private $gender;

    /**
     * @ORM\Column(type="text")
     */
    private $address = '';

    /**
     * @ORM\Column(type="string", length=64)
     */
    private $city = '';

    /**
     * @ORM\Column(type="string", length=64)
     */
    private $country = '';

    /**
     * @ORM\Column(type="string", length=32, name="zip_code")
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
     * @ORM\Column(type="string", length=128)
     */
    private $timezone = '';

    /**
     * @ORM\Column(type="string", length=128)
     */
    private $job = '';

    /**
     * @ORM\Column(type="string", length=256)
     */
    private $activity = '';

    /**
     * @ORM\Column(type="string", length=64)
     */
    private $company = '';

    /**
     * @ORM\Column(type="string", length=32)
     */
    private $phone = '';

    /**
     * @ORM\Column(type="string", length=32)
     */
    private $fax = '';

    /**
     * @ORM\Column(type="boolean")
     */
    private $admin = false;

    /**
     * @ORM\Column(type="boolean")
     */
    private $guest = false;

    /**
     * @ORM\Column(type="boolean", name="mail_notifications")
     */
    private $mailNotificationsActivated = false;

    /**
     * @ORM\Column(type="boolean", name="request_notifications")
     */
    private $requestNotificationsActivated = false;

    /**
     * @ORM\Column(type="boolean", name="ldap_created")
     */
    private $ldapCreated = false;

    /**
     * @ORM\Column(type="string", length=64, name="last_model", nullable=true)
     */
    private $lastModel;

    /**
     * @ORM\Column(type="text", name="push_list")
     */
    private $pushList = '';

    /**
     * @ORM\Column(type="boolean", name="can_change_profil")
     */
    private $canChangeProfil = true;

    /**
     * @ORM\Column(type="boolean", name="can_change_ftp_profil")
     */
    private $canChangeFtpProfil = true;

    /**
     * @ORM\Column(type="datetime", name="last_connection", nullable=true)
     */
    private $lastConnection;

    /**
     * @ORM\Column(type="boolean", name="mail_locked")
     */
    private $mailLocked = false;

    /**
     * @ORM\Column(type="boolean")
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
     * @ORM\OneToOne(targetEntity="User")
     * @ORM\JoinColumn(name="model_of", referencedColumnName="id")
     *
     * @var User
     **/
    private $modelOf;

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
     * @throws InvalidArgumentException
     */
    public function setGender($gender)
    {
        if (null !== $gender && !in_array($gender, [
            self::GENDER_MISS,
            self::GENDER_MR,
            self::GENDER_MRS
        ])) {
            throw new InvalidArgumentException(sprintf("Invalid gender %s.", $gender));
        }

        $this->gender = $gender;
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
    }

    /**
     * @return User
     */
    public function getModelOf()
    {
        return $this->modelOf;
    }

    /**
     * @param User $owner
     */
    public function setModelOf(User $owner)
    {
        $this->modelOf = $owner;
    }

    /**
     * @return string
     */
    public function getLastModel()
    {
        return $this->lastModel;
    }

    /**
     * @param string $lastModel
     */
    public function setLastModel($lastModel)
    {
        $this->lastModel = $lastModel;
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
    }

    /**
     * @param \Datetime $updated
     */
    public function setUpdated(\Datetime $updated)
    {
        $this->updated = $updated;
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
        return null !== $this->modelOf;
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
    public function getDisplayName(TranslatorInterface $translator)
    {
        if ($this->isTemplate()) {
            return $translator->trans('modele %name%', ['%name%' => $this->getLogin()]);
        }

        if (trim($this->lastName) !== '' || trim($this->firstName) !== '') {
           return $this->firstName . ('' !== $this->firstName && '' !== $this->lastName ? ' ' : '') . $this->lastName;
        }

        if (trim($this->email) !== '') {
            return $this->email;
        }

        return $translator->trans('Unnamed user');
    }
}
