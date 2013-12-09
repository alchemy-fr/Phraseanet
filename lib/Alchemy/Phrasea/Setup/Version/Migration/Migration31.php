<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Setup\Version\Migration;

use Alchemy\Phrasea\Application;

class Migration31 implements MigrationInterface
{
    private $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function migrate()
    {
        if (!file_exists(__DIR__ . '/../../../../../../config/_GV.php')
            || !file_exists(__DIR__ . '/../../../../../../lib/conf.d/_GV_template.inc')) {
            throw new \LogicException('Required config files not found');
        }

        // required to load GV template
        $app = $this->app;

        require __DIR__ . '/../../../../../../config/_GV.php';
        $GV = require __DIR__ . '/../../../../../../lib/conf.d/_GV_template.inc';

        $retrieve_old_credentials = function () {
                require __DIR__ . '/../../../../../../config/connexion.inc';

                return [
                    'hostname' => $hostname,
                    'port'     => $port,
                    'user'     => $user,
                    'password' => $password,
                    'dbname'   => $dbname,
                ];
            };

        $params = $retrieve_old_credentials();

        $dsn = 'mysql:dbname=' . $params['dbname'] . ';host=' . $params['hostname'] . ';port=' . $params['port'] . ';';
        $connection = new \PDO($dsn, $params['user'], $params['password']);

        $connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $connection->query("
            SET character_set_results = 'utf8', character_set_client = 'utf8',
            character_set_connection = 'utf8', character_set_database = 'utf8',
            character_set_server = 'utf8'");

        define('GV_STATIC_URL', '');

        $connection->exec("CREATE TABLE IF NOT EXISTS `registry` (
            `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
            `key` char(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
            `value` varchar(1024) COLLATE utf8_unicode_ci NOT NULL,
            `type` enum('string','boolean','array','integer') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'string',
            PRIMARY KEY (`id`),
            UNIQUE KEY `UNIQUE` (`key`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;");

        $sql = 'REPLACE INTO registry (`id`, `key`, `value`, `type`)
            VALUES (null, :key, :value, :type)';
        $stmt = $connection->prepare($sql);

        foreach ($GV as $section => $datas_section) {
            foreach ($datas_section['vars'] as $datas) {
                if (defined($datas["name"])) {
                    $val = constant($datas["name"]);
                } elseif (isset($datas['default'])) {
                    $val = $datas['default'];
                } else {
                    continue;
                }

                $val = $val === true ? '1' : $val;
                $val = $val === false ? '0' : $val;

                $type = $datas['type'];
                switch ($datas['type']) {
                    case \registry::TYPE_ENUM_MULTI:
                        $val = serialize($val);
                        break;
                    case \registry::TYPE_INTEGER:
                        $val = (int) $val;
                        break;
                    case \registry::TYPE_BOOLEAN:
                        $val = $val ? '1' : '0';
                        break;
                    case \registry::TYPE_STRING:
                    case \registry::TYPE_BINARY:
                    case \registry::TYPE_TEXT:
                    case \registry::TYPE_TIMEZONE:
                    case \registry::TYPE_ENUM:
                        $val = (string) $val;
                        break;
                    default:
                        $val = (string) $val;
                        $type = \registry::TYPE_STRING;
                        break;
                }

                $stmt->execute([
                    ':key'   => $datas['name'],
                    ':value' => $val,
                    ':type'  => $type,
                ]);
            }
        }

        $stmt->execute([
            ':key'   => 'GV_sit',
            ':value' => GV_sit,
            ':type'  => \registry::TYPE_STRING,
        ]);

        $stmt->closeCursor();

        rename(__DIR__ . '/../../../../../../config/_GV.php', __DIR__ . '/../../../../../../config/_GV.php.old');

        $servername = '';
        if (defined('GV_ServerName')) {
            $servername = GV_ServerName;
        }

        file_put_contents(__DIR__ . '/../../../../../../config/config.inc', "<?php\n\$servername = \"" . str_replace('"', '\"', $servername) . "\";\n");

        return;
    }
}
