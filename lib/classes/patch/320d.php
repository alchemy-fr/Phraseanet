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

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class patch_320d implements patchInterface
{
    /**
     *
     * @var string
     */
    private $release = '3.2.0.0.a5';

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

    public function apply(base $appbox, Application $app)
    {

        $sql = 'SELECT base_id, usr_id FROM order_masters';
        $stmt = $appbox->get_connection()->prepare($sql);
        $stmt->execute();
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $sql = 'UPDATE basusr SET order_master="1"
                        WHERE base_id = :base_id AND usr_id = :usr_id';
        $stmt = $appbox->get_connection()->prepare($sql);

        foreach ($rs as $row) {
            $params = array(
                ':base_id' => $row['base_id'],
                ':usr_id'  => $row['usr_id']
            );
            $stmt->execute($params);
        }

        $stmt->closeCursor();

        return true;
    }
}
