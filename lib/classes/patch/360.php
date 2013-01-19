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
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class patch_360 implements patchInterface
{
    /**
     *
     * @var string
     */
    private $release = '3.6.0a1';

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

    public function apply(base $appbox, Application $app)
    {
        $tables = array('StoryWZ', 'ValidationDatas', 'ValidationParticipants', 'ValidationSessions', 'BasketElements', 'Baskets');

        foreach ($tables as $table) {
            $sql = 'DELETE FROM ' . $table;
            $stmt = $appbox->get_connection()->prepare($sql);
            $stmt->execute();
            $stmt->closeCursor();
        }

        $stories = array();

        $sql = 'SELECT sbas_id, rid as record_id, usr_id
                                FROM ssel
                                WHERE temporaryType = "1"';

        $stmt = $appbox->get_connection()->prepare($sql);
        $stmt->execute();
        $rs_s = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $current = array();

        foreach ($rs_s as $row_story) {
            $serial = $row_story['sbas_id'] . '_' . $row_story['usr_id'] . '_' . $row_story['record_id'];

            if (isset($current[$serial])) {
                $stories[] = $row_story;
            }

            $current[$serial] = $serial;
        }

        $sql = 'DELETE FROM ssel
                             WHERE temporaryType="1" AND record_id = :record_id
                                AND usr_id = :usr_id AND sbas_id = :sbas_id';
        $stmt = $appbox->get_connection()->prepare($sql);

        foreach ($stories as $row) {
            $params = array(
                ':usr_id'    => $row['usr_id'],
                ':sbas_id'   => $row['sbas_id'],
                ':record_id' => $row['record_id']
            );
            $stmt->execute($params);
        }

        $stmt->closeCursor();

        $sql = 'INSERT INTO StoryWZ
            (
                SELECT null as id, sbas_id, rid as record_id, usr_id, date as created
                FROM ssel
                WHERE temporaryType = "1"
            )';

        $stmt = $appbox->get_connection()->prepare($sql);
        $stmt->execute();
        $stmt->closeCursor();

        $sql = 'INSERT INTO Baskets
            (
                SELECT ssel_id as id, name, descript as description, usr_id, 1 as is_read
                    , pushFrom as pusher_id,
                    0 as archived, date as created, updater as updated
                FROM ssel
                WHERE temporaryType = "0"
            )';

        $stmt = $appbox->get_connection()->prepare($sql);
        $stmt->execute();
        $stmt->closeCursor();

        $sql = 'SELECT ssel_id FROM ssel WHERE temporaryType = "0"';
        $stmt = $appbox->get_connection()->prepare($sql);
        $stmt->execute();
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $sselcont_ids = array();

        foreach ($rs as $row) {
            $sql = 'SELECT c.sselcont_id, c.record_id, b.sbas_id
                        FROM sselcont c, bas b, ssel s
                        WHERE s.temporaryType = "0" AND b.base_id = c.base_id
                            AND c.ssel_id = :ssel_id AND s.ssel_id = c.ssel_id';

            $stmt = $appbox->get_connection()->prepare($sql);
            $stmt->execute(array(':ssel_id' => $row['ssel_id']));
            $rs_be = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            $current = array();

            foreach ($rs_be as $row_sselcont) {
                $serial = $row_sselcont['sbas_id'] . '_' . $row_sselcont['record_id'];

                if (isset($current[$serial])) {
                    $sselcont_ids[] = $row_sselcont['sselcont_id'];
                }

                $current[$serial] = $serial;
            }
        }

        $sql = 'DELETE FROM sselcont WHERE sselcont_id = :sselcont_id';
        $stmt = $appbox->get_connection()->prepare($sql);

        foreach ($sselcont_ids as $sselcont_id) {
            $stmt->execute(array(':sselcont_id' => $sselcont_id));
        }

        $stmt->closeCursor();

        $sql = 'INSERT INTO BasketElements
            (
                SELECT sselcont_id as id, c.ssel_id as basket_id, record_id, b.sbas_id, c.ord,
                s.date as created, s.updater as updated
                FROM sselcont c, ssel s, bas b
                WHERE temporaryType = "0" AND b.base_id = c.base_id AND s.ssel_id = c.ssel_id
            )';

        $stmt = $appbox->get_connection()->prepare($sql);
        $stmt->execute();
        $stmt->closeCursor();

        $sql = 'UPDATE Baskets SET pusher_id = NULL WHERE pusher_id = 0';

        $stmt = $appbox->get_connection()->prepare($sql);
        $stmt->execute();
        $stmt->closeCursor();

        $sql = 'INSERT INTO ValidationSessions
            (
                SELECT null as id, v.ssel_id as basket_id ,created_on as created
                    ,updated_on as updated ,expires_on as expires
                    ,v.usr_id as initiator_id
                FROM ssel s, validate v
                WHERE v.ssel_id = s.ssel_id AND v.usr_id = s.usr_id
            )';

        $stmt = $appbox->get_connection()->prepare($sql);
        $stmt->execute();
        $stmt->closeCursor();

        $sql = 'INSERT INTO ValidationParticipants
            (
                SELECT v.id as id, v.usr_id
                        , 1 AS is_aware, confirmed as is_confirmed, 1 as can_agree
                        , can_see_others, last_reminder AS reminded
                        , vs.`id` AS ValidationSession_id
                    FROM validate v, ssel s, ValidationSessions vs
                    WHERE s.ssel_id = v.ssel_id AND vs.basket_id = v.ssel_id
            )';

        $stmt = $appbox->get_connection()->prepare($sql);
        $stmt->execute();
        $stmt->closeCursor();

        $sql = 'SELECT usr_id, basket_id, p.id as participant_id
                        FROM ValidationParticipants p, ValidationSessions s
                        WHERE p.ValidationSession_Id = s.id';

        $stmt = $appbox->get_connection()->prepare($sql);
        $stmt->execute();
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $sql = 'INSERT INTO ValidationDatas (
                            SELECT d.id , :participant_id as participant_id, d.sselcont_id, d.agreement,
                                d.note, d.updated_on as updated
                            FROM validate v, validate_datas d, sselcont c
                            WHERE c.sselcont_id = d.sselcont_id AND v.id = d.validate_id
                                AND v.usr_id = :usr_id AND v.ssel_id = :basket_id
                         )';
        $stmt = $appbox->get_connection()->prepare($sql);
        foreach ($rs as $row) {
            $params = array(
                ':participant_id' => $row['participant_id'],
                ':basket_id'      => $row['basket_id'],
                ':usr_id'         => $row['usr_id'],
            );
            $stmt->execute($params);
        }

        $stmt->closeCursor();

        $sql = 'UPDATE ValidationDatas
             SET agreement = NULL where agreement = "0"';

        $stmt = $appbox->get_connection()->prepare($sql);
        $stmt->execute();
        $stmt->closeCursor();

        $sql = 'UPDATE ValidationDatas
             SET agreement = "0" where agreement = "-1"';

        $stmt = $appbox->get_connection()->prepare($sql);
        $stmt->execute();
        $stmt->closeCursor();

        return true;
    }
}
