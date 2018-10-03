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

class patch_360alpha1a extends patchAbstract
{
    /** @var string */
    private $release = '3.6.0-alpha.1';

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
    public function getDoctrineMigrations()
    {
        return ['20131118000002', '20131118000006'];
    }

    /**
     * {@inheritdoc}
     */
    public function apply(base $appbox, Application $app)
    {
        $tables = ['StoryWZ', 'ValidationDatas', 'ValidationParticipants', 'ValidationSessions', 'BasketElements', 'Baskets'];

        foreach ($tables as $table) {
            $sql = 'DELETE FROM ' . $table;
            $stmt = $appbox->get_connection()->prepare($sql);
            $stmt->execute();
            $stmt->closeCursor();
        }

        $stories = [];

        $sql = <<<SQL
        SELECT
          sbas_id,
          rid as record_id,
          usr_id
        FROM ssel
        WHERE temporaryType = "1"
SQL;

        $stmt = $appbox->get_connection()->prepare($sql);
        $stmt->execute();
        $rs_s = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $current = [];

        foreach ($rs_s as $row_story) {
            $serial = $row_story['sbas_id'] . '_' . $row_story['usr_id'] . '_' . $row_story['record_id'];

            if (isset($current[$serial])) {
                $stories[] = $row_story;
            }

            $current[$serial] = $serial;
        }

        $sql = <<<SQL
        DELETE FROM ssel
        WHERE temporaryType="1"
        AND record_id = :record_id
        AND usr_id = :usr_id
        AND sbas_id = :sbas_id
SQL;

        $stmt = $appbox->get_connection()->prepare($sql);

        foreach ($stories as $row) {
            $params = [
                ':usr_id'    => $row['usr_id'],
                ':sbas_id'   => $row['sbas_id'],
                ':record_id' => $row['record_id']
            ];
            $stmt->execute($params);
        }

        $stmt->closeCursor();

        $sql = <<<SQL
        INSERT INTO StoryWZ (
            SELECT
                null as id,
                usr_id as user_id,
                sbas_id,
                rid as record_id,
                date as created
            FROM ssel
            INNER JOIN Users ON usr_id = Users.id
            WHERE temporaryType = "1"
        )
SQL;

        $stmt = $appbox->get_connection()->prepare($sql);
        $stmt->execute();
        $stmt->closeCursor();

        $sql = <<<SQL
        INSERT INTO Baskets  (
            SELECT
                ssel_id as id,
                usr_id as user_id,
                pushFrom as pusher_id,
                name,
                descript as description,
                1 as is_read,
                0 as archived,
                date as created,
                updater as updated
            FROM ssel
            INNER JOIN Users a ON ssel.usr_id = a.id
            INNER JOIN Users b ON ssel.pushFrom = b.id
            WHERE temporaryType = "0"
        )
SQL;

        $stmt = $appbox->get_connection()->prepare($sql);
        $stmt->execute();
        $stmt->closeCursor();

        $sql = <<<SQL
        SELECT ssel_id
        FROM ssel
        WHERE temporaryType = "0"
SQL;
        $stmt = $appbox->get_connection()->prepare($sql);
        $stmt->execute();
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $sselcont_ids = [];

        foreach ($rs as $row) {
            $sql = <<<SQL
            SELECT
                c.sselcont_id,
                c.record_id,
                b.sbas_id
            FROM sselcont c
            INNER JOIN bas b ON (b.base_id = c.base_id)
            INNER JOIN ssel s ON (s.ssel_id = c.ssel_id)
            INNER JOIN Baskets ba ON (ba.id = s.ssel_id)
            WHERE s.temporaryType = "0"
            AND c.ssel_id = :ssel_id
SQL;

            $stmt = $appbox->get_connection()->prepare($sql);
            $stmt->execute([':ssel_id' => $row['ssel_id']]);
            $rs_be = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            $current = [];

            foreach ($rs_be as $row_sselcont) {
                $serial = $row_sselcont['sbas_id'] . '_' . $row_sselcont['record_id'];

                if (isset($current[$serial])) {
                    $sselcont_ids[] = $row_sselcont['sselcont_id'];
                }

                $current[$serial] = $serial;
            }
        }

        $sql = <<<SQL
        DELETE FROM sselcont
        WHERE sselcont_id = :sselcont_id
SQL;
        $stmt = $appbox->get_connection()->prepare($sql);

        foreach ($sselcont_ids as $sselcont_id) {
            $stmt->execute([':sselcont_id' => $sselcont_id]);
        }

        $stmt->closeCursor();

        $sql = <<<SQL
        INSERT INTO BasketElements (
            SELECT
                sselcont_id as id,
                c.ssel_id as basket_id,
                record_id,
                b.sbas_id,
                c.ord,
                s.date as created,
                s.updater as updated
            FROM sselcont c
            INNER JOIN ssel s ON (s.ssel_id = c.ssel_id)
            INNER JOIN Baskets a ON (a.id = s.ssel_id)
            INNER JOIN bas b ON (b.base_id = c.base_id)
            WHERE s.temporaryType = "0"
        )
SQL;

        $stmt = $appbox->get_connection()->prepare($sql);
        $stmt->execute();
        $stmt->closeCursor();

        $sql = <<<SQL
        UPDATE Baskets
        SET pusher_id = NULL
        WHERE pusher_id = 0
SQL;

        $stmt = $appbox->get_connection()->prepare($sql);
        $stmt->execute();
        $stmt->closeCursor();

        $sql = <<<SQL
        INSERT INTO ValidationSessions (
            SELECT null as id,
                v.usr_id as initiator_id,
                v.ssel_id as basket_id,
                v.created_on as created,
                v.updated_on as updated,
                v.expires_on as expires
            FROM validate v
            INNER JOIN Baskets b ON (b.id = v.ssel_id)
            INNER JOIN Users u ON (u.id = v.usr_id)
        )
SQL;


        $stmt = $appbox->get_connection()->prepare($sql);
        $stmt->execute();
        $stmt->closeCursor();

        $sql = <<<SQL
        INSERT INTO ValidationParticipants (
            SELECT
                v.id AS id,
                v.usr_id AS user_id,
                1 AS is_aware,
                confirmed AS is_confirmed,
                1 AS can_agree,
                can_see_others,
                last_reminder AS reminded,
                vs.`id` AS validation_session_id
            FROM validate v
            INNER JOIN Baskets b ON (b.id = v.ssel_id)
            INNER JOIN ValidationSessions vs ON (vs.basket_id = b.id)
            INNER JOIN Users u ON (u.id = v.usr_id)
        )
SQL;

        $stmt = $appbox->get_connection()->prepare($sql);
        $stmt->execute();
        $stmt->closeCursor();

        $sql = <<<SQL
        SELECT
            p.user_id,
            s.basket_id,
            p.id as participant_id
        FROM ValidationParticipants p
        INNER JOIN ValidationSessions s ON (s.id = p.validation_session_id)
        INNER JOIN Users u ON (u.id = p.user_id)
        INNER JOIN Baskets b ON (b.id = s.basket_id)
SQL;

        $stmt = $appbox->get_connection()->prepare($sql);
        $stmt->execute();
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $sql = <<<SQL
        INSERT INTO ValidationDatas (
            SELECT
                d.id ,
                :participant_id AS participant_id,
                d.sselcont_id AS basket_element_id,
                d.agreement,
                d.note, d.updated_on AS updated
            FROM validate v
            INNER JOIN validate_datas d ON (v.id = d.validate_id)
            INNER JOIN Baskets b ON (v.ssel_id = b.id)
            INNER JOIN BasketElements be ON (be.id = d.sselcont_id)
            AND v.usr_id = :usr_id AND v.ssel_id = :basket_id
        )
SQL;

        $stmt = $appbox->get_connection()->prepare($sql);
        foreach ($rs as $row) {
            $params = [
                ':participant_id' => $row['participant_id'],
                ':basket_id'      => $row['basket_id'],
                ':usr_id'         => $row['user_id'],
            ];
            $stmt->execute($params);
        }

        $stmt->closeCursor();

        $sql = <<<SQL
        UPDATE ValidationDatas
        SET agreement = NULL
        WHERE agreement = "0"
SQL;

        $stmt = $appbox->get_connection()->prepare($sql);
        $stmt->execute();
        $stmt->closeCursor();

        $sql = <<<SQL
        UPDATE ValidationDatas
        SET agreement = "0"
        WHERE agreement = "-1"
SQL;

        $stmt = $appbox->get_connection()->prepare($sql);
        $stmt->execute();
        $stmt->closeCursor();

        return true;
    }
}
