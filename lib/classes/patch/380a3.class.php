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

/**
 *
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class patch_380a3 implements patchInterface
{
    /**
     *
     * @var string
     */
    private $release = '3.8.0.a3';

    /**
     *
     * @var Array
     */
    private $concern = array(base::DATA_BOX);

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

    public function apply(base $databox, Application $app)
    {
        $conn = $databox->get_connection();

        try {
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
              DECLARE cur1 CURSOR FOR SELECT  l.id, l.coll_list FROM log l LEFT JOIN log_colls lc ON (lc.log_id = l.id) WHERE (lc.log_id IS NULL) AND coll_list != '';
              DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;
              OPEN cur1;
                read_loop: LOOP
                  FETCH cur1 INTO l_log_id, l_coll_list;
                  IF done THEN
                    LEAVE read_loop;
                  END IF;

                  SET occurance = (SELECT  LENGTH(l_coll_list) - LENGTH(REPLACE(l_coll_list, bound, ''))+1);
                  SET i=1;
              START TRANSACTION;
                  WHILE i <= occurance DO
                    SET dest_coll_id = (SELECT  REPLACE(SUBSTRING(SUBSTRING_INDEX(l_coll_list, bound, i), LENGTH(SUBSTRING_INDEX(l_coll_list, bound, i - 1)) + 1), ',', ''));
                    IF dest_coll_id > 0 THEN
                      INSERT INTO log_colls VALUES (null, l_log_id, dest_coll_id);
                END IF;
                    SET i = i + 1;
                  END WHILE;
              COMMIT;
                END LOOP;
              CLOSE cur1;
            END;";

            $stmt = $conn->prepare($procedure);
            $stmt->execute();
            $stmt->closeCursor();
            unset($stmt);


            $sql = "CALL explode_log_table(',')";
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $stmt->closeCursor();
            unset($stmt);
        } catch (\PDOEXception $e) {
            echo $e->getCode() . '  ' . $e->getMessage();
            return false;
        }

        return true;
    }
}