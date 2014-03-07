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

class patch_380alpha10a extends patchAbstract
{
    /** @var string */
    private $release = '3.8.0-alpha.10';

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
    public function getDoctrineMigrations()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function require_all_upgrades()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(\appbox $appbox, Application $app)
    {
        $sql = 'SELECT id, `usage`
                FROM `order`';
        $stmt = $appbox->get_connection()->prepare($sql);
        $stmt->execute();
        $rs = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $sql = 'UPDATE `order` SET `usage` = :usage
                WHERE id = :id';
        $stmt = $appbox->get_connection()->prepare($sql);

        foreach ($rs as $row) {
            $stmt->execute([
                ':usage' => strip_tags($row['usage']),
                ':id' => $row['id'],
            ]);
        }

        $stmt->closeCursor();

        return true;
    }
}
