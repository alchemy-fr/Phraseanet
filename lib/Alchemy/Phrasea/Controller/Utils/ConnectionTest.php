<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Utils;

use Silex\Application;
use Silex\ControllerProviderInterface;

class ConnectionTest implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $app['controller.utils.connection-test'] = $this;

        $controllers = $app['controllers_factory'];

        /**
         * @todo : check this as it would lead to a security issue
         */
        $controllers->get('/mysql/', function (Application $app) {

            $request = $app['request'];
            $hostname = $request->query->get('hostname', '127.0.0.1');
            $port = (int) $request->query->get('port', 3306);
            $user = $request->query->get('user');
            $password = $request->query->get('password');
            $dbname = $request->query->get('dbname');

            $connection_ok = $db_ok = $is_databox = $is_appbox = $empty = false;

            try {
                $conn = new \connection_pdo('test', $hostname, $port, $user, $password, null, [], false);
                $connection_ok = true;
            } catch (\Exception $e) {

            }

            if ($dbname && $connection_ok === true) {
                try {
                    $conn = new \connection_pdo('test', $hostname, $port, $user, $password, $dbname, [], false);
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

            $datas = [
                'connection' => $connection_ok
                , 'database'   => $db_ok
                , 'is_empty'   => $empty
                , 'is_appbox'  => $is_appbox
                , 'is_databox' => $is_databox
            ];

            return $app->json($datas);
        });

        return $controllers;
    }
}
