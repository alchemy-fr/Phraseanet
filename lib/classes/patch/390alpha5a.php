<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Model\Entities\UserNotificationSetting;

class patch_390alpha5a implements patchInterface
{
    /** @var string */
    private $release = '3.9.0-alpha.5';

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
        return ['user-notif-setting'];
    }

    /**
     * {@inheritdoc}
     */
    public function apply(base $appbox, Application $app)
    {
        $sql = 'DELETE FROM UserNotificationSettings';
        $stmt = $app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute();
        $stmt->closeCursor();

        $conn = $app['phraseanet.appbox']->get_connection();
        $sql = 'SELECT * FROM usr_settings
                WHERE prop LIKE "notification_%"';
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $rs = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $n = 0;
        $em = $app['EM'];

        foreach ($rs as $row) {
            $user = $app['manipulator.user']->getRepository()->find($row['usr_id']);

            $userSetting = new UserNotificationSetting();
            $userSetting->setName($row['prop']);
            $userSetting->setValue($row['value']);
            $userSetting->setUser($user);

            $em->persist($userSetting);

            $n++;

            if ($n % 200 === 0) {
                $em->flush();
                $em->clear();
            }
        }

        $em->flush();
        $em->clear();

        return true;
    }
}
