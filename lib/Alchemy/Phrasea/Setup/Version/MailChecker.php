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
     */
    public function getWrongEmailUsers()
    {
        if (version_compare($this->appbox->get_version(), '3.9', '>=')) {
            return [];
        }

        $builder = $this->appbox->get_connection()->createQueryBuilder();
        /** @var Statement $stmt */
        $stmt = $builder
            ->select('u.usr_mail', 'u.usr_id', 'u.last_conn', 'u.usr_login')
            ->from($this->table, 'u')
            ->where($builder->expr()->isNotNull('u.usr_mail'))
            ->execute()
        ;
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
