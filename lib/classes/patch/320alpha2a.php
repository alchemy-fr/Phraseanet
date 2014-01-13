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

class patch_320alpha2a implements patchInterface
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
    public function getDoctrineMigrations()
    {
        return [];
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
        $dql = 'SELECT u FROM Alchemy\Phrasea\Model\Entities\User u WHERE u.nonce IS NULL';
        $users = $app['EM']->createQuery($dql)->getResult();

        $n = 0;
        foreach ($users as $user) {
            $user->setNonce(random::generatePassword(16));
            $app['EM']->persist($user);
            $n++;
            if ($n %100 === 0) {
                $app['EM']->flush();
            }
        }

        $app['EM']->flush();

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
