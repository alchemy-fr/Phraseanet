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
class patch_3103 implements patchInterface
{
    /**
     *
     * @var string
     */
    private $release = '3.1.0';

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

    public function apply(base &$appbox, Application $app)
    {
        $conn = $appbox->get_connection();

        $validate_process = array();

        $sql = 'SELECT id, ssel_id, usr_id FROM validate';
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        foreach ($rs as $row) {
            $validate_process[$row['ssel_id']][$row['usr_id']] = $row['id'];
        }

        $sql = 'SELECT u.*, s.ssel_id, c.base_id, c.record_id , s.usr_id as pushFrom
                            FROM sselcontusr u, sselcont c, ssel s' .
            ' WHERE s.ssel_id = c.ssel_id AND u.sselcont_id = c.sselcont_id' .
            ' AND s.deleted="0" ' .
            ' ORDER BY s.ssel_id ASC, c.sselcont_id ASC';

        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        foreach ($rs as $row) {
            if ( ! isset($validate_process[$row['ssel_id']]) ||
                ! array_key_exists($row['usr_id'], $validate_process[$row['ssel_id']])
            ) {

                $sql = 'INSERT INTO validate
                        (id, ssel_id, created_on, updated_on, expires_on, last_reminder,
                            usr_id, confirmed, can_agree, can_see_others)
                            VALUES
                        (null, :ssel_id, :created_on, :updated_on, :expires_on, null,
                            :usr_id, "0", :can_agree, :can_see)';

                $stmt = $conn->prepare($sql);

                $expire = new DateTime($row['dateFin']);
                $expire = $expire->format('u') == 0 ?
                    null : $app['date-formatter']->format_mysql($expire);

                $params = array(
                    ':ssel_id'    => $row['ssel_id']
                    , ':created_on' => $row['date_maj']
                    , ':updated_on' => $row['date_maj']
                    , ':expires_on' => $expire
                    , ':usr_id'     => $row['usr_id']
                    , ':can_agree'  => $row['canAgree']
                    , ':can_see'    => $row['canSeeOther']
                );
                $stmt->execute($params);

                $validate_process[$row['ssel_id']][$row['usr_id']] = $conn->lastInsertId();
                $stmt->closeCursor();

                $sbas_id = phrasea::sbasFromBas($app, $row['base_id']);
                $record = new record_adapter($app, $sbas_id, $row['record_id']);

                $user = User_Adapter::getInstance($row['usr_id'], $app);
                $pusher = User_Adapter::getInstance($row['pushFrom'], $app);

                if ($row['canHD'])
                    $user->ACL()->grant_hd_on($record, $pusher, 'validate');
                else
                    $user->ACL()->grant_preview_on($record, $pusher, 'validate');
            }

            $sql = 'REPLACE INTO validate_datas
                                (id, validate_id, sselcont_id, updated_on, agreement)
                                VALUES
                (null, :validate_id, :sselcont_id, :updated_on, :agreement)';

            $stmt = $conn->prepare($sql);

            $params = array(
                ':validate_id' => $validate_process[$row['ssel_id']][$row['usr_id']]
                , ':sselcont_id' => $row['sselcont_id']
                , ':updated_on'  => $row['date_maj']
                , ':agreement'   => $row['agree']
            );
            $stmt->execute($params);
            $stmt->closeCursor();
        }

        return true;
    }
}
