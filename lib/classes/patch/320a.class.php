<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class patch_320a implements patchInterface
{
    /**
     *
     * @var string
     */
    private $release = '3.2.0.0.a2';

    /**
     *
     * @var Array
     */
    private $concern = array(base::APPLICATION_BOX);

    /**
     *
     * @return string
     */
    public function get_release()
    {
        return $this->release;
    }

    public function require_all_upgrades()
    {
        return false;
    }

    /**
     *
     * @return Array
     */
    public function concern()
    {
        return $this->concern;
    }

    public function apply(base &$appbox)
    {
        $sql = 'SELECT * FROM usr WHERE nonce IS NULL';

        $stmt = $appbox->get_connection()->prepare($sql);
        $stmt->execute();
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $sql = 'UPDATE usr SET nonce = :nonce WHERE usr_id = :usr_id';
        $stmt = $appbox->get_connection()->prepare($sql);
        foreach ($rs as $row) {
            $nonce = random::generatePassword(16);
            $params = array(':usr_id' => $row['usr_id'], ':nonce'  => $nonce);
            $stmt->execute($params);
        }
        $stmt->closeCursor();

        $sql = 'SELECT task_id, `class` FROM task2';
        $stmt = $appbox->get_connection()->prepare($sql);
        $stmt->execute();
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $tasks = array();

        $sql = 'UPDATE task2 SET `class` = :class WHERE task_id = :task_id';
        $stmt = $appbox->get_connection()->prepare($sql);
        foreach ($rs as $row) {
            if (strpos($row['class'], 'task_period_') !== false)
                continue;

            $params = array(
                ':task_id' => $row['task_id']
                , ':class'   => str_replace('task_', 'task_period_', $row['class'])
            );

            $stmt->execute($params);
        }

        $stmt->closeCursor();

        return true;
    }
}
