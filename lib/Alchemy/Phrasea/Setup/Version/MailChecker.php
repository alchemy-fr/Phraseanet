<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Setup\Version;

use Alchemy\Phrasea\Application;

/**
 * In version 3.9 the user table have been removed.
 * However for migration process we must be able to detect
 * duplicate emails.
 */
class MailChecker
{
    /**
     * Returns users with duplicated emails
     *
     * @param \Application $app
     * @param string       $table The table name where to look
     *
     * @return array An array of User_Adapter
     */
    public static function getWrongEmailUsers(Application $app, $table = 'usr')
    {
        $sql = 'SELECT usr_mail, usr_id, last_conn, usr_login FROM '. $table .' WHERE usr_mail IS NOT NULL';
        $stmt = $app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute();
        $rs = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $users = [];

        foreach ($rs as $row) {
            if (!isset($users[$row['usr_mail']])) {
                $users[$row['usr_mail']] = [];
            }

            $users[$row['usr_mail']][] = $row;
        }

        $badUsers = [];

        foreach ($users as $email => $usrs) {
            if (count($usrs) > 1) {
                $badUsers[$email] = [];
                foreach ($usrs as $usrInfo) {
                    $badUsers[$email][$usrInfo['usr_id']] = $usrInfo;
                }
            }
        }

        unset($users);

        return $badUsers;
    }
}
