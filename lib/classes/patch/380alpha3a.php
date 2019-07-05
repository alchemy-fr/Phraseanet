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

class patch_380alpha3a extends patchAbstract
{
    /** @var string */
    private $release = '3.8.0-alpha.3';

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
    public function require_all_upgrades()
    {
        return true;
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
        $conn = $databox->get_connection();

        $sql = "CREATE TABLE IF NOT EXISTS `log_colls` (\n"
            . " `id` int(11) unsigned NOT NULL AUTO_INCREMENT,\n"
            . " `log_id` int(11) unsigned NOT NULL,\n"
            . " `coll_id` int(11) unsigned NOT NULL,\n"
            . " PRIMARY KEY (`id`),\n"
            . " UNIQUE KEY `couple` (`log_id`,`coll_id`),\n"
            . " KEY `log_id` (`log_id`),\n"
            . " KEY `coll_id` (`coll_id`)\n"
            . ") ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";

        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $stmt->closeCursor();
        unset($stmt);

        $removeProc = "DROP PROCEDURE IF EXISTS explode_log_table";

        $stmt = $conn->prepare($removeProc);
        $stmt->execute();
        $stmt->closeCursor();
        unset($stmt);

        $procedure = "
        CREATE PROCEDURE explode_log_table(bound VARCHAR(255))
        BEGIN
            DECLARE l_log_id INT UNSIGNED DEFAULT 0;
            DECLARE l_coll_list TEXT;
            DECLARE occurance INT DEFAULT 0;
            DECLARE i INT DEFAULT 0;
            DECLARE dest_coll_id INT;
            DECLARE done INT DEFAULT 0;
            DECLARE result_set CURSOR FOR
            SELECT l.id, l.coll_list
            FROM log l
            LEFT JOIN log_colls lc ON (lc.log_id = l.id)
            WHERE (lc.log_id IS NULL) AND coll_list != '';
            DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;
            OPEN result_set;
            read_loop: LOOP
                FETCH result_set INTO l_log_id, l_coll_list;
                IF done THEN
                LEAVE read_loop;
                END IF;
                SET occurance = (SELECT  LENGTH(l_coll_list) - LENGTH(REPLACE(l_coll_list, bound, ''))+1);
                SET i=1;
            START TRANSACTION;
                WHILE i <= occurance DO
                    SET dest_coll_id = (SELECT REPLACE(
                        SUBSTRING(
                            SUBSTRING_INDEX(l_coll_list, bound, i),
                            LENGTH(SUBSTRING_INDEX(l_coll_list, bound, i - 1)) + 1
                        ),
                        ',',
                        ''
                    ));
                    IF dest_coll_id > 0 THEN
                        INSERT INTO log_colls VALUES (null, l_log_id, dest_coll_id);
                    END IF;
                    SET i = i + 1;
                END WHILE;
            COMMIT;
            END LOOP;
            CLOSE result_set;
        END;";

        $stmt = $conn->prepare($procedure);
        $stmt->execute();
        $stmt->closeCursor();

        $sql = "CALL explode_log_table(',')";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $stmt->closeCursor();

        $stmt = $conn->prepare($removeProc);
        $stmt->execute();
        $stmt->closeCursor();

        return true;
    }
}
