<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Utils;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ControllerCollection;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class ConnectionTest implements ControllerProviderInterface
{

    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        $controllers->get('/mysql/', function() use ($app) {
                require_once __DIR__ . '/../../../../classes/connection/pdo.class.php';

                $request = $app['request'];
                $hostname = $request->get('hostname', '127.0.0.1');
                $port = (int) $request->get('port', 3306);
                $user = $request->get('user');
                $password = $request->get('password');
                $dbname = $request->get('dbname');

                $connection_ok = $db_ok = $is_databox = $is_appbox = $empty = false;

                try {
                    $conn = new \connection_pdo('test', $hostname, $port, $user, $password);
                    $connection_ok = true;
                } catch (\Exception $e) {

                }

                if ($dbname && $connection_ok === true) {
                    try {
                        $conn = new \connection_pdo('test', $hostname, $port, $user, $password, $dbname);
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

                $Serializer = $app['Core']['Serializer'];

                $datas = array(
                    'connection' => $connection_ok
                    , 'database'   => $db_ok
                    , 'is_empty'   => $empty
                    , 'is_appbox'  => $is_appbox
                    , 'is_databox' => $is_databox
                );

                return new Response(
                        $Serializer->serialize($datas, 'json')
                        , 200
                        , array('content-type' => 'application/json')
                );
            });

        return $controllers;
    }
}

