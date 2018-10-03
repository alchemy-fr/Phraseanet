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

class patch_320alpha5a extends patchAbstract
{
    /** @var string */
    private $release = '3.2.0-alpha.5';

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
        $sql = 'SELECT base_id, usr_id FROM order_masters';
        $stmt = $appbox->get_connection()->prepare($sql);
        $stmt->execute();
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $sql = 'UPDATE basusr SET order_master="1"
                WHERE base_id = :base_id
                  AND usr_id = :usr_id';
        $stmt = $appbox->get_connection()->prepare($sql);

        foreach ($rs as $row) {
            $params = [
                ':base_id' => $row['base_id'],
                ':usr_id'  => $row['usr_id']
            ];
            $stmt->execute($params);
        }

        $stmt->closeCursor();

        return true;
    }
}
