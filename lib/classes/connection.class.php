<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class connection
{
    /**
     *
     * @var Array
     */
    private static $_PDO_instance = array();

    /**
     *
     * @var boolean
     */
    private static $_selfinstance;

    /**
     *
     * @var Array
     */
    public static $log = array();

    /**
     *
     */
    public function __destruct()
    {
        self::printLog();

        return;
    }

    /**
     *
     * @return Void
     */
    public static function printLog()
    {
        $registry = registry::get_instance();
        if ( ! $registry->get('GV_debug')) {
            return;
        }

        $totalTime = 0;

        foreach (self::$log as $entry) {
            $query = $entry['query'];
            do {
                $query = str_replace(array("\n", "  "), " ", $query);
            } while ($query != str_replace(array("\n", "  "), " ", $query));

            $totalTime += $entry['time'];
            $string = $entry['time'] . "\t" . ' - ' . $query . ' - ' . "\n";
            file_put_contents($registry->get('GV_RootPath') . 'logs/mysql_log.log', $string, FILE_APPEND);
        }
        $string = count(self::$log) . ' queries - ' . $totalTime
            . "\nEND OF QUERY " . $_SERVER['PHP_SELF']
            . "?";
        foreach ($_GET as $key => $value) {
            $string .= $key . ' = ' . $value . ' & ';
        }
        $string .= "\nPOST datas :\n ";
        foreach ($_POST as $key => $value) {
            $string .= "\t\t" . $key . ' = ' . $value . "\n";
        }
        $string .= "\n\n\n\n";

        file_put_contents($registry->get('GV_RootPath') . 'logs/mysql_log.log', $string, FILE_APPEND);



        return;
    }

    /**
     *
     * @return type
     */
    protected static function instantiate()
    {
        if ( ! self::$_selfinstance)
            self::$_selfinstance = new self();

        return;
    }

    /**
     *
     * @param string $name
     * @return connection_pdo
     */
    public static function getPDOConnection($name = null, registryInterface $registry = null)
    {
        self::instantiate();
        if (trim($name) == '') {
            $name = 'appbox';
        } elseif (is_int((int) $name)) {
            $name = (int) $name;
        } else {
            return false;
        }

        if ( ! isset(self::$_PDO_instance[$name])) {
            $hostname = $port = $user = $password = $dbname = false;

            $connection_params = array();

            if (trim($name) !== 'appbox') {
                $connection_params = phrasea::sbas_params();
            } else {
                $configuration = \Alchemy\Phrasea\Core\Configuration::build();

                $choosenConnexion = $configuration->getPhraseanet()->get('database');

                $connexion = $configuration->getConnexion($choosenConnexion);

                $hostname = $connexion->get('host');
                $port = $connexion->get('port');
                $user = $connexion->get('user');
                $password = $connexion->get('password');
                $dbname = $connexion->get('dbname');
            }

            if (isset($connection_params[$name])) {
                $hostname = $connection_params[$name]['host'];
                $port = $connection_params[$name]['port'];
                $user = $connection_params[$name]['user'];
                $password = $connection_params[$name]['pwd'];
                $dbname = $connection_params[$name]['dbname'];
            }

            try {
                self::$_PDO_instance[$name] = new connection_pdo($name, $hostname, $port, $user, $password, $dbname, array(), $registry);
                self::$_PDO_instance[$name]->query("SET character_set_results = 'utf8', character_set_client = 'utf8', character_set_connection = 'utf8', character_set_database = 'utf8', character_set_server = 'utf8'");
            } catch (Exception $e) {
                throw new Exception('Connection not available');
            }
        }
        if (array_key_exists($name, self::$_PDO_instance)) {
            return self::$_PDO_instance[$name];
        }

        throw new Exception('Connection not available');
    }

    /**
     *
     * @param type $name
     * @return type
     */
    public static function close_PDO_connection($name)
    {
        if (isset(self::$_PDO_instance[$name])) {
            self::$_PDO_instance[$name] = null;
            unset(self::$_PDO_instance[$name]);
        }

        return;
    }
}
