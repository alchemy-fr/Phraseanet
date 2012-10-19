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
use Alchemy\Phrasea\Border\Checker;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class patch_3802 implements patchInterface
{
    /**
     *
     * @var string
     */
    private $release = '3.8.0.a2';

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
        return true;
    }

    /**
     *
     * @return Array
     */
    public function concern()
    {
        return $this->concern;
    }

    /**
     * @param base $appbox
     */
    public function apply(base $appbox, Application $app)
    {
        $sql = "SHOW TABLE STATUS LIKE 'cache'";
        $stmt = $app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute();
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        $stmt->closeCurosr();

        if ($row['Auto_increment']) {
            $sql = sprintf('ALTER TABLE Sessions  AUTO_INCREMENT = %d', $row['Auto_increment']);
            $app['phraseanet.appbox']->get_connection()->exec($sql);
        }

        return;
    }
}

