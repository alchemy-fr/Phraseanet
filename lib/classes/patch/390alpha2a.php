<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;
use Doctrine\ORM\EntityManager;
use Alchemy\Phrasea\Model\Entities\User;
use Gedmo\Timestampable\TimestampableListener;

class patch_390alpha2a implements patchInterface
{
    /** @var string */
    private $release = '3.9.0-alpha.2';

    /** @var array */
    private $concern = [base::APPLICATION_BOX];

    /**
     * {@inheritdoc}
     */
    public function get_release()
    {
        return $this->release;
    }

    /**
     * {@inheritdoc}
     */
    public function require_all_upgrades()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function concern()
    {
        return $this->concern;
    }

    /**
     * {@inheritdoc}
     */
    public function getDoctrineMigrations()
    {
        return ['user'];
    }

    /**
     * {@inheritdoc}
     */
    public function apply(base $appbox, Application $app)
    {
        $sql = 'DELETE FROM Users';
        $stmt = $app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute();
        $stmt->closeCursor();

        $conn = $app['phraseanet.appbox']->get_connection();
        $em = $app['EM'];

        $em->getEventManager()->removeEventSubscriber(new TimestampableListener());

        $this->updateUsers($em, $conn);
        $this->updateModels($em, $conn);

        $em->getEventManager()->addEventSubscriber(new TimestampableListener());

        return true;
    }

    /**
     * Sets user entity from usr table.
     */
    private function updateUsers(EntityManager $em, $conn)
    {
        $sql = 'SELECT activite, adresse, create_db, canchgftpprofil, canchgprofil, ville,
                    societe, pays, usr_mail, fax, usr_prenom, geonameid, invite, fonction, last_conn, lastModel,
                    usr_nom, ldap_created, locale, usr_login, mail_locked, mail_notifications, nonce, usr_password, push_list,
                    request_notifications, salted_password, usr_sexe, tel, timezone, cpostal, usr_creationdate, usr_modificationdate
                FROM usr';
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $n = 0;

        foreach ($rows as $row) {
            $user = new User();
            $user->setActivity($row['activite']);
            $user->setAddress($row['adresse']);
            $user->setAdmin(!!$row['create_db']);
            $user->setCanChangeFtpProfil(!!$row['canchgftpprofil']);
            $user->setCanChangeProfil(!!$row['canchgprofil']);
            $user->setCity($row['ville']);
            $user->setCompany($row['societe']);
            $user->setCountry((string) $row['pays']);
            $user->setEmail($row['usr_mail']);
            $user->setFax($row['fax']);
            $user->setFirstName($row['usr_prenom']);
            if ($row['geonameid'] > 0) {
                $user->setGeonameId($row['geonameid']);
            }
            $user->setGuest(!!$row['invite']);
            $user->setJob($row['fonction']);
            $user->setLastConnection(new \DateTime($row['last_conn']));
            $user->setLastModel($row['lastModel']);
            $user->setLastName($row['usr_nom']);
            $user->setLdapCreated(!!$row['ldap_created']);
            try {
                $user->setLocale($row['locale']);
            } catch (\InvalidArgumentException $e ) {

            }
            $user->setLogin($row['usr_login']);

            if (substr($row['usr_login'], 0, 10) === '(#deleted_') {
                $user->setDeleted(true);
            }

            $user->setMailLocked(!!$row['mail_locked']);
            $user->setMailNotificationsActivated(!!$row['mail_notifications']);
            $user->setNonce($row['nonce']);
            $user->setPassword($row['usr_password']);
            $user->setPushList($row['push_list']);
            $user->setRequestNotificationsActivated(!!$row['request_notifications']);
            $user->setSaltedPassword(!!$row['salted_password']);

            switch ($row['usr_sexe']) {
                case 0:
                    $gender = User::GENDER_MISS;
                    break;
                case 1:
                    $gender = User::GENDER_MRS;
                    break;
                case 2:
                    $gender = User::GENDER_MR;
                    break;
                default:
                    $gender = null;
            }

            $user->setGender($gender);
            $user->setPhone($row['tel']);
            $user->setTimezone($row['timezone']);
            $user->setZipCode($row['cpostal']);
            $user->setCreated(new \DateTime($row['usr_creationdate']));
            $user->setupdated(new \DateTime($row['usr_modificationdate']));

            $em->persist($user);

            $n++;

            if ($n % 100 === 0) {
                $em->flush();
                $em->clear();
            }
        }

        $em->flush();
        $em->clear();
    }

    /**
     * Sets model from usr table.
     */
    private function updateModels(EntityManager $em, $conn)
    {
        $sql = "SELECT model_of, usr_login
                FROM usr
                WHERE model_of > 0";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $n = 0;

        $repository = $em->getRepository('Alchemy\Phrasea\Model\Entities\User');

        foreach ($rows as $row) {
            $template = $repository->findOneByLogin($row['usr_login']);

            if (null === $loginOwner = $this->getLoginFromId($conn, $row['model_of'])) {
                // remove template with no owner
                $em->remove($template);
            } else {
                $owner = $repository->findOneByLogin($loginOwner);
                $template->setModelOf($owner);
                $em->persist($owner);
            }

            $n++;

            if ($n % 100 === 0) {
                $em->flush();
                $em->clear();
            }
        }

        $em->flush();
        $em->clear();
    }

    /**
     * Returns user login from its id.
     */
    private function getLoginFromId($conn, $id)
    {
        $sql = "SELECT usr_login
                FROM usr
                WHERE usr_id = :id";

        $stmt = $conn->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if (count($row) === 0) {
            return null;
        }

        return $row['usr_login'];
    }
}
