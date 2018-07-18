<?php

/**
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use Alchemy\Phrasea\Application;


class patch_410alpha13a implements patchInterface
{
    /** @var string */
    private $release = '4.1.0-alpha.13';

    /** @var array */
    private $concern = [base::DATA_BOX];

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
    public function apply(base $databox, Application $app)
    {
        $sql = "DROP TABLE IF EXISTS `log_colls`";
        $databox->get_connection()->prepare($sql)->execute();

        $sql = "ALTER TABLE `log_docs` ADD `coll_id` INT(11) UNSIGNED NULL DEFAULT NULL, ADD INDEX(coll_id)";
        try {
            $databox->get_connection()->prepare($sql)->execute();
        }
        catch(\Exception $e) {
            // no-op
        }

        $sql = "CREATE TEMPORARY TABLE `tmp_colls` (\n"
            . " `id` int(11) unsigned NOT NULL,\n"
            . " `coll_id` int(11) unsigned NOT NULL,\n"
            . " PRIMARY KEY (`id`)\n"
            . ")";
        $databox->get_connection()->prepare($sql)->execute();

        $tsql = [
            [
                'sql' => "TRUNCATE tmp_colls",
                'stmt' => null,
            ],
            [
                'sql' => "INSERT INTO tmp_colls\n"
                        . "   SELECT id, COALESCE(SUBSTRING_INDEX(GROUP_CONCAT(final ORDER BY r2_id DESC), ',', 1), 0) AS coll_id FROM\n"
                        . "   (\n"
                        . "    SELECT r1.record_id, r1.id, r2.id AS r2_id, r2.final FROM\n"
                        . "     (select id, record_id FROM log_docs WHERE ISNULL(coll_id) LIMIT 1000000) AS r1\n"
                        . "     LEFT JOIN log_docs AS r2\n"
                        . "     ON r2.record_id=r1.record_id AND r2.action IN('add', 'collection') AND r2.id<=r1.id\n"
                        . "   )\n"
                        . "   AS t GROUP BY id",
                'stmt' => null,
            ],
            [
                'sql' => "UPDATE tmp_colls INNER JOIN log_docs USING(id) SET log_docs.coll_id=tmp_colls.coll_id",
                'stmt' => null,
            ]
        ];
        foreach($tsql as $k => $v) {
            $tsql[$k]['stmt'] = $databox->get_connection()->prepare($v['sql']);
        }


        $nchanged = 0;
        do {
            foreach($tsql as $k => $v) {
                printf("%s\n\n", $v['sql']);
                /** @var \Doctrine\DBAL\Driver\Statement $stmt */
                $stmt = $v['stmt'];
                $stmt->execute();
                $nchanged = $stmt->rowCount();
                $stmt->closeCursor();
            }
        }
        while($nchanged != 0);


        $sql = "DROP TABLE `tmp_colls`";
        $databox->get_connection()->prepare($sql)->execute();


        return true;
    }
}
