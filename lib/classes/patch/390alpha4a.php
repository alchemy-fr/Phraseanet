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
use Alchemy\Phrasea\Model\Entities\UserSetting;

class patch_390alpha4a extends patchAbstract
{
    /** @var string */
    private $release = '3.9.0-alpha.4';

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
        return ['20131118000011'];
    }

    /**
     * {@inheritdoc}
     */
    public function apply(base $appbox, Application $app)
    {
        $sql = 'DELETE FROM UserSettings';
        $stmt = $app->getApplicationBox()->get_connection()->prepare($sql);
        $stmt->execute();
        $stmt->closeCursor();

        $conn = $app->getApplicationBox()->get_connection();
        $sql = 'SELECT * FROM usr_settings';
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $rs = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $n = 0;
        $em = $app['orm.em'];

        foreach ($rs as $row) {
            if (substr($row['prop'], 0, 13) === "notification_") {
                continue;
            }

            if (null === $user = $this->loadUser($app['orm.em'], $row['usr_id'])) {
                continue;
            }

            $userSetting = new UserSetting();
            $userSetting->setName($row['prop']);
            $userSetting->setValue($row['value']);
            $userSetting->setUser($user);

            $em->persist($userSetting);

            $n++;

            if ($n % 1000 === 0) {
                $em->flush();
                $em->clear();
            }
        }

        $em->flush();
        $em->clear();

        return true;
    }
}
