<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;

/**
 * Pool of PDO connections to phraseanet databoxes and appbox;
 */
class connection
{
    private static $_PDO_instances = array();
    private static $_self;

    public static function getPDOConnection(Application $app, $name = null)
    {
        if (!self::$_self) {
            self::$_self = new self();
        }
        if (null !== $name && !is_numeric($name)) {
            return false;
        }
        if (null === $name) {
            $name = 'appbox';
        } else {
            $name = (int) $name;
        }

        if (isset(self::$_PDO_instances[$name]) && self::$_PDO_instances[$name] instanceof \connection_pdo) {
            return self::$_PDO_instances[$name];
        }

        $hostname = $port = $user = $password = $databaseName = false;

        if (trim($name) !== 'appbox') {
            $params = phrasea::sbas_params($app);
            if (!isset($params[$name])) {
                throw new \Exception(sprintf('Unknown connection parameters for databox "%s"', $name));
            }
            $hostname = $params[$name]['host'];
            $port = $params[$name]['port'];
            $user = $params[$name]['user'];
            $password = $params[$name]['pwd'];
            $databaseName = $params[$name]['dbname'];
        } else {
            $params = $app['phraseanet.configuration']['main']['database'];

            $hostname = $params['host'];
            $port = $params['port'];
            $user = $params['user'];
            $password = $params['password'];
            $databaseName = $params['dbname'];
        }

        try {
            return self::$_PDO_instances[$name] = new \connection_pdo($name, $hostname, $port, $user, $password, $databaseName, array(), $app['debug']);
        } catch (\Exception $e) {
            throw new \Exception('Connection not available', 0, $e);
        }
    }

    public function __destruct()
    {
        foreach (self::$_PDO_instances as $conn) {
            $conn->close();
        }
        self::$_PDO_instances = array();

        return;
    }
}
