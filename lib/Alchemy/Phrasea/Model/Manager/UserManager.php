<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Model\Manager;

use Alchemy\Geonames\Connector as GeonamesConnector;
use Alchemy\Geonames\Exception\ExceptionInterface as GeonamesExceptionInterface;
use Alchemy\Phrasea\Exception\InvalidArgumentException;
use Doctrine\Common\Persistence\ObjectManager;
use Entities\EntityInterface;
use Entities\User;
use Entities\UserSetting;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;

class UserManager
{
    /** @var \appbox */
    protected $appbox;
    /** @var ObjectManager */
    protected $objectManager;
    /** @var PasswordEncoderInterface */
    protected $passwordEncoder;
    /** @var GeonamesConnector */
    private $geonamesConnector;

    /**
     * The default user setting values.
     *
     * @var array
     */
    private static $defaultUserSettings = array(
        'view'                    => 'thumbs',
        'images_per_page'         => '20',
        'images_size'             => '120',
        'editing_images_size'     => '134',
        'editing_top_box'         => '180px',
        'editing_right_box'       => '400px',
        'editing_left_box'        => '710px',
        'basket_sort_field'       => 'name',
        'basket_sort_order'       => 'ASC',
        'warning_on_delete_story' => 'true',
        'client_basket_status'    => '1',
        'css'                     => '000000',
        'start_page_query'        => 'last',
        'start_page'              => 'QUERY',
        'rollover_thumbnail'      => 'caption',
        'technical_display'       => '1',
        'doctype_display'         => '1',
        'bask_val_order'          => 'nat',
        'basket_caption_display'  => '0',
        'basket_status_display'   => '0',
        'basket_title_display'    => '0'
    );

    public function __construct(PasswordEncoderInterface $passwordEncoder, GeonamesConnector $connector, ObjectManager $om, \appbox $appbox)
    {
        $this->appbox = $appbox;
        $this->objectManager = $om;
        $this->passwordEncoder = $passwordEncoder;
        $this->geonamesConnector = $connector;
    }

    /**
     * @return User
     */
    public function create()
    {
        $user = new User();

        foreach(self::$defaultUserSettings as $name => $value) {
            $setting = new UserSetting();
            $setting->setName($name);
            $setting->setValue($value);
            $user->getSettings()->add($setting);
        };

        return $user;
    }

    /**
     * @{inheritdoc}
     */
    public function delete(EntityInterface $user, $flush = true)
    {
        $this->checkEntity($user);

        $user->setDeleted(true);
        $user->setEmail(null);
        $user->setLogin(sprintf('(#deleted_%s', $user->getLogin()));

        $this->cleanRelations($user);

        $this->objectManager->persist($user);
        if ($flush) {
            $this->objectManager->flush();
        }
    }

    /**
     * @{inheritdoc}
     */
    public function update(EntityInterface $user, $flush = true)
    {
        $this->checkEntity($user);

        $this->objectManager->persist($user);
        if ($flush) {
            $this->objectManager->flush();
        }
    }

    /**
     * Updates the modelOf field from the template field value.
     *
     * @param UserInterface $user
     * @param UserInterface $template
     */
    public function onUpdateModel(User $user, User $template)
    {
        $user->setModelOf($template);
        if (null !== $credential = $user->getFtpCredential()) {
            $credential->resetCredentials();
        }
        $this->cleanSettings($user);
        $user->reset();
    }

    /**
     * Sets the given password.
     *
     * @param UserInterface $user
     * @param password $password
     */
    public function onUpdatePassword(User $user, $password)
    {
        $user->setNonce(base_convert(sha1(uniqid(mt_rand(), true)), 16, 36));
        $user->setPassword($this->passwordEncoder->encodePassword($password, $user->getNonce()));
    }

    /**
     * Updates the country fields for a user according to the current geoname id field value.
     *
     * @param User $user
     */
    public function onUpdateGeonameId(User $user)
    {
        if (null === $user->getGeonameId()) {
            return;
        }

        try {
            $country = $this->geonamesConnector
                ->geoname($user->getGeonameId())
                ->get('country');

            if (isset($country['code'])) {
                $user->setCountry($country['code']);
            }
        } catch (GeonamesExceptionInterface $e) {

        }
    }

    /**
     * Removes user settings.
     *
     * @param User $user
     */
    public function cleanSettings(User $user)
    {
        foreach($user->getNotificationSettings() as $userNotificatonSetting) {
            $userNotificatonSetting->setUser(null);
        }

        $user->getNotificationSettings()->clear();

        foreach($user->getSettings() as $userSetting) {
            $userSetting->setUser(null);
        }

        $user->getSettings()->clear();
    }

    /**
     * Removes user queries.
     *
     * @param User $user
     */
    public function cleanQueries(User $user)
    {
        foreach($user->getQueries() as $userQuery) {
            $userQuery->setUser(null);
        }

        $user->getQueries()->clear();
    }

    /**
     * Removes all user's relations.
     *
     * @todo Removes order relationship, it is now a doctrine entity.
     *
     * @param User $user
     */
    private function cleanRelations(User $user)
    {
        $conn = $this->appbox->get_connection();
        foreach(array(
            'basusr',
            'sbasusr',
            'edit_presets',
            'ftp_export',
            'order',
            'sselnew',
            'tokens',
        ) as $table) {
            $stmt = $conn->prepare('DELETE FROM `' .$table. '` WHERE usr_id = :usr_id');
            $stmt->execute(array(':usr_id' => $user->getId()));
            $stmt->closeCursor();
        }
        unset($stmt);

        $this->cleanSettings($user);
        $this->cleanQueries($user);
    }

    /**
     * Checks whether given entity is an User one.
     *
     * @param EntityInterface $entity
     *
     * @throws InvalidArgumentException If provided entity is not an User one.
     */
    private function checkEntity(EntityInterface $entity)
    {
        if (!$entity instanceof User) {
            throw new InvalidArgumentException(sprintf('Entity of type `%s` should be a `Entities\User` entity.', get_class($entity)));
        }
    }
}
