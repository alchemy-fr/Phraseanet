<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Helper;

use Symfony\Component\HttpFoundation\Request;

class DatabaseHelper extends Helper
{
    public function checkConnection()
    {
        $hostname = $this->request->query->get('hostname', '127.0.0.1');
        $port = (int) $this->request->query->get('port', 3306);
        $user = $this->request->query->get('user');
        $password = $this->request->query->get('password');
        $dbname = $this->request->query->get('dbname');

        $connection_ok = $db_ok = $is_databox = $is_appbox = $empty = false;

        try {
            $conn = new \connection_pdo('test', $hostname, $port, $user, $password, null, array(), false);
            $connection_ok = true;
        } catch (\Exception $e) {

        }

        if ($dbname && $connection_ok === true) {
            try {
                $conn = new \connection_pdo('test', $hostname, $port, $user, $password, $dbname, array(), false);
                $db_ok = true;

                $sql = "SHOW TABLE STATUS";
                $stmt = $conn->prepare($sql);
                $stmt->execute();

                $empty = $stmt->rowCount() === 0;

                $rs = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                $stmt->closeCursor();

                foreach ($rs as $row) {
                    if ($row["Name"] === 'sitepreff') {
                        $is_appbox = true;
                    }
                    if ($row["Name"] === 'pref') {
                        $is_databox = true;
                    }
                }
            } catch (\Exception $e) {

            }
        }

        return array(
            'connection' => $connection_ok,
            'database'   => $db_ok,
            'is_empty'   => $empty,
            'is_appbox'  => $is_appbox,
            'is_databox' => $is_databox
        );
    }
}
