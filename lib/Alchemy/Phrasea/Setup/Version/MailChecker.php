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
use Doctrine\DBAL\Driver\Statement;
use Exception;

/**
 * In version 3.9 the user table have been removed.
 * However for migration process we must be able to detect
 * duplicate emails.
 */
class MailChecker
{
    /** @var \appbox */
    private $appbox;
    /** @var string */
    private $table;

    /**
     * Constructor
     *
     * @param \appbox $appbox
     * @param string  $table
     */
    public function __construct(\appbox $appbox, $table = 'usr')
    {
        $this->appbox = $appbox;
        $this->table = $table;
    }

    /**
     * Returns users with duplicated emails
     *
     * @return array An array of User
     * @throws Exception
     */
    public function getWrongEmailUsers()
    {
        $tests = [
            "SELECT usr_mail, usr_id, last_conn, usr_login FROM usr WHERE NOT ISNULL(usr_mail)",
            "SELECT email AS usr_mail, id AS usr_id, last_connection AS last_conn, login AS usr_login FROM Users WHERE NOT ISNULL(email)"
        ];

        $e = null;
        foreach($tests as $sql) {
            try {
                $rs = null;
                $stmt = $this->appbox->get_connection()->prepare($sql);
                $stmt->execute();
                $rs = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                $stmt->closeCursor();
                break;
            }
            catch (Exception $e) {
                // no-op
            }
        }

        if(is_null($rs)) {
            throw $e;
        }

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

    /**
     * Whether there is users with same emails
     *
     * @return bool
     */
    public function hasWrongEmailUsers()
    {
        return count($this->getWrongEmailUsers()) > 0;
    }
}
