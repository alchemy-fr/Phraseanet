<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;
use Doctrine\ORM\Query;

class patch_320alpha2a extends patchAbstract
{
    /** @var string */
    private $release = '3.2.0-alpha.2';

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
    public function apply(base $appbox, Application $app)
    {
        $dql = 'SELECT u FROM Phraseanet:User u WHERE u.nonce IS NULL';
        $q = $app['orm.em']->createQuery($dql);
        $q->setHint(Query::HINT_FORCE_PARTIAL_LOAD, true);
        $users = $q->getResult();

        $n = 0;
        foreach ($users as $user) {
            $user->setNonce($app['random.medium']->generateString(64));
            $app['orm.em']->persist($user);
            $n++;
            if ($n %100 === 0) {
                $app['orm.em']->flush();
            }
        }

        $app['orm.em']->flush();

        $sql = 'SELECT task_id, `class` FROM task2';
        $stmt = $appbox->get_connection()->prepare($sql);
        $stmt->execute();
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $sql = 'UPDATE task2 SET `class` = :class WHERE task_id = :task_id';
        $stmt = $appbox->get_connection()->prepare($sql);
        foreach ($rs as $row) {
            if (strpos($row['class'], 'task_period_') !== false)
                continue;

            $params = [
                ':task_id' => $row['task_id']
                , ':class'   => str_replace('task_', 'task_period_', $row['class'])
            ];

            $stmt->execute($params);
        }

        $stmt->closeCursor();

        return true;
    }
}
