<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Model\Entities\UserQuery;

class patch_390alpha3a implements patchInterface
{
    /** @var string */
    private $release = '3.9.0-alpha.3';

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
        return array('user-query');
    }

    /**
     * {@inheritdoc}
     */
    public function apply(base $appbox, Application $app)
    {
        $sql = 'DELETE FROM UserQueries';
        $stmt = $app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute();
        $stmt->closeCursor();

        $conn = $app['phraseanet.appbox']->get_connection();
        $sql = 'SELECT * FROM dsel';
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $rs = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $n = 0;
        $em = $app['EM'];

        foreach ($rs as $row) {
            $userQuery = new UserQuery();
            $userQuery->setQuery($row['query']);
            $userQuery->setUsrId($row['usr_id']);

            $em->persist($userQuery);

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
