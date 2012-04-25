<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class patch_3102 implements patchInterface
{

    /**
     *
     * @var string
     */
    private $release = '3.1.20';

    /**
     *
     * @var Array
     */
    private $concern = array(base::APPLICATION_BOX, base::DATA_BOX);

    /**
     *
     * @return string
     */
    function get_release()
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
    function concern()
    {
        return $this->concern;
    }

    function apply(base &$base)
    {
        $conn = connection::getPDOConnection();

        $sql = 'SELECT task_id FROM task2 WHERE `class` = "task_period_upgradetov31"';

        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $rowcount = $stmt->rowCount();
        $stmt->closeCursor();

        if ($rowcount == 0)
        {
            $sql = 'INSERT INTO `task2`
                                (`task_id`, `usr_id_owner`, `pid`, `status`, `crashed`,
                    `active`, `name`, `last_exec_time`, `class`, `settings`, `completed`)
                                VALUES
                                (null, 0, 0, "stopped", 0, 1, "upgrade to v3.1",
                "0000-00-00 00:00:00", "task_period_upgradetov31",
                                        "<?xml version=\"1.0\" encoding=\"UTF-8\"?>' .
                            '<tasksettings></tasksettings>", -1)';

            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $stmt->closeCursor();
        }


        if ($base->get_base_type() == base::DATA_BOX)
        {
            $sql = 'UPDATE record SET sha256 = ""
                            WHERE sha256 IS NULL AND parent_record_id = 0';
            $stmt = $base->get_connection()->prepare($sql);
            $stmt->execute();
            $stmt->closeCursor();
        }

        return true;
    }

}
