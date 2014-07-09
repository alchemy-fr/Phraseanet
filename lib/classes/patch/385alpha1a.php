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
use Doctrine\ORM\Query\ResultSetMapping;

class patch_383alpha4a implements patchInterface
{
    /** @var string */
    private $release = '3.8.5-alpha.1';

    /** @var array */
    private $concern = array(base::APPLICATION_BOX);

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
        $sql = "SHOW COLUMNS FROM `task2` WHERE Field IN('completed', 'todo', 'done')";
        $stmt = $appbox->get_connection()->prepare($sql);
        $stmt->execute();
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        $rs = array_column($rs, "Field", "Field");
        if(count($rs) == 1 && isset($rs['completed'])) {
            $sql = "ALTER TABLE `task2` DROP `completed`, ADD `todo` INT(11) NOT NULL DEFAULT 0, ADD `done` INT(11) NOT NULL DEFAULT 0;";
            $appbox->getConnection()->executeQuery($sql);
        }
        elseif(count($rs) == 2 && isset($rs['todo']) && isset($rs['done'])) {
            // patch already applied
        }
        else {
            // BAD
        }

        return true;
    }
}
