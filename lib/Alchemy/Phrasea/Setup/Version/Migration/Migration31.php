<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
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
        if (!file_exists(__DIR__ . '/../../../../../../config/_GV.php')) {
            throw new \LogicException('Required config files not found');
        }

        require __DIR__ . '/../../../../../../config/_GV.php';
        $GV = [
                [
                    'type'      => 'string',
                    'name'      => 'GV_default_lng',
                    'default'   => 'fr_FR',
                ],
                [
                    'type'      => 'string',
                    'name'      => 'GV_STATIC_URL',
                    'default'   => '',
                ],
                [
                    'type'    => 'string',
                    'name'    => 'GV_message',
                    'default' => "May the force be with you"
                ],
                [
                    'type'    => 'boolean',
                    'name'    => 'GV_message_on',
                    'default' => false
                ],
                [
                    'type'    => 'boolean',
                    'name'    => 'GV_log_errors',
                    'default' => false
                ],
                [
                    'type'     => 'boolean',
                    'name'     => 'GV_google_api',
                    'default'  => true,
                ],
                [
                    'type'      => 'string',
                    'name'      => 'GV_i18n_service',
                    'default'   => 'https://geonames.alchemyasp.com/',
                ],
                [
                    'type'     => 'boolean',
                    'name'     => 'GV_captchas',
                    'default'  => false,
                ],
                [
                    'type'    => 'string',
                    'name'    => 'GV_captcha_public_key',
                    'default' => ''
                ],
                [
                    'type'    => 'string',
                    'name'    => 'GV_captcha_private_key',
                    'default' => ''
                ],
                [
                    'type'     => 'boolean',
                    'name'     => 'GV_youtube_api',
                    'default'  => false,
                ],
                [
                    'type'    => 'string',
                    'name'    => 'GV_youtube_client_id',
                    'default' => ''
                ],
                [
                    'type'    => 'string',
                    'name'    => 'GV_youtube_client_secret',
                    'default' => ''
                ],
                [
                    'type'    => 'string',
                    'name'    => 'GV_youtube_dev_key',
                    'default' => ''
                ],
                [
                    'type'     => 'boolean',
                    'name'     => 'GV_flickr_api',
                    'default'  => false,
                ],
                [
                    'type'    => 'string',
                    'name'    => 'GV_flickr_client_id',
                    'default' => ''
                ],
                [
                    'type'    => 'string',
                    'name'    => 'GV_flickr_client_secret',
                    'default' => ''
                ],
                [
                    'type'     => 'boolean',
                    'name'     => 'GV_dailymotion_api',
                    'default'  => false,
                ],
                [
                    'type'    => 'string',
                    'name'    => 'GV_dailymotion_client_id',
                    'default' => ''
                ],
                [
                    'type'    => 'string',
                    'name'    => 'GV_dailymotion_client_secret',
                    'default' => ''
                ],
                [
                    'type'     => 'boolean',
                    'name'     => 'GV_client_navigator',
                    'default'  => true,
                ],
                [
                    'type'     => 'boolean',
                    'name'     => 'GV_client_officeplugin',
                    'default'  => true,
                ],
                [
                    'type'      => 'string',
                    'name'      => 'GV_base_datapath_noweb',
                    'default'   => '',
                ],
                [
                    'type'    => 'boolean',
                    'name'    => 'GV_h264_streaming',
                    'default' => false
                ],
                [
                    'type'      => 'string',
                    'name'      => 'GV_mod_auth_token_directory',
                    'default'   => false
                ],
                [
                    'type'      => 'string',
                    'name'      => 'GV_mod_auth_token_directory_path',
                    'default'   => false
                ],
                [
                    'type'    => 'string',
                    'name'    => 'GV_mod_auth_token_passphrase',
                    'default' => false
                ],
                [
                    'type'    => 'string',
                    'name'    => 'GV_PHP_INI',
                    'default' => ''
                ],
                [
                    'type'      => 'string',
                    'name'      => 'GV_imagine_driver',
                    'default'   => '',
                ],
                [
                    'type'    => 'integer',
                    'name'    => 'GV_ffmpeg_threads',
                    'default' => 2
                ],
                [
                    'type'    => 'integer',
                    'name'    => 'GV_pdfmaxpages',
                    'default' => 5
                ],
                [
                    'type'    => 'string',
                    'name'    => 'GV_adminMail',
                    'default' => 'support@alchemy.fr'
                ],
                [
                    'type'     => 'boolean',
                    'name'     => 'GV_view_bas_and_coll',
                    'default'  => true,
                ],
                [
                    'type'     => 'boolean',
                    'name'     => 'GV_choose_export_title',
                    'default'  => false,
                ],
                [
                    'type'      => 'string',
                    'name'      => 'GV_default_export_title',
                    'default'   => 'title',
                ],
                [
                    'type'      => 'string',
                    'name'      => 'GV_social_tools',
                    'default'    => 'none',
                ],
                [
                    'type'      => 'string',
                    'name'      => 'GV_home_publi',
                    'default'   => 'COOLIRIS',
                ],
                [
                    'type'    => 'integer',
                    'name'    => 'GV_min_letters_truncation',
                    'default' => 1
                ],
                [
                    'type'    => 'string',
                    'name'    => 'GV_defaultQuery',
                    'default' => 'all'
                ],
                [
                    'type'      => 'string',
                    'name'      => 'GV_defaultQuery_type',
                    'default' => '0'
                ],
                [
                    'type'     => 'boolean',
                    'name'     => 'GV_anonymousReport',
                    'default'  => false,
                ],
                [
                    'type'    => 'boolean',
                    'name'    => 'GV_thesaurus',
                    'default' => true
                ],
                [
                    'type'    => 'boolean',
                    'name'    => 'GV_multiAndReport',
                    'default' => true
                ],
                [
                    'type'    => 'boolean',
                    'name'    => 'GV_seeOngChgDoc',
                    'default' => false
                ],
                [
                    'type'    => 'boolean',
                    'name'    => 'GV_seeNewThumb',
                    'default' => false
                ],
                [
                    'type'    => 'string',
                    'name'    => 'GV_defaulmailsenderaddr',
                    'default' => 'phraseanet@example.com'
                ],
                [
                    'type'    => 'string',
                    'name'    => 'GV_email_prefix',
                    'default' => ''
                ],
                [
                    'type'    => 'boolean',
                    'name'    => 'GV_smtp',
                    'default' => false
                ],
                [
                    'type'    => 'boolean',
                    'name'    => 'GV_smtp_auth',
                    'default' => false
                ],
                [
                    'type'    => 'string',
                    'name'    => 'GV_smtp_host',
                    'default' => ''
                ],
                [
                    'type'    => 'string',
                    'name'    => 'GV_smtp_port',
                    'default' => ''
                ],
                [
                    'type'      => 'string',
                    'name'      => 'GV_smtp_secure',
                    'default'   => 'tls',
                ],
                [
                    'type'    => 'string',
                    'name'    => 'GV_smtp_user',
                    'default' => ''
                ],
                [
                    'type'    => 'string',
                    'name'    => 'GV_smtp_password',
                    'default' => ''
                ],
                [
                    'type'    => 'boolean',
                    'name'    => 'GV_activeFTP',
                    'default' => false
                ],
                [
                    'type'    => 'boolean',
                    'name'    => 'GV_ftp_for_user',
                    'default' => false
                ],
                [
                    'type'    => 'integer',
                    'name'    => 'GV_download_max',
                    'default' => 120
                ],
                [
                    'type'    => 'integer',
                    'name'    => 'GV_ong_search',
                    'default' => 1
                ],
                [
                    'type'    => 'integer',
                    'name'    => 'GV_ong_advsearch',
                    'default' => 2
                ],
                [
                    'type'    => 'integer',
                    'name'    => 'GV_ong_topics',
                    'default' => 0
                ],
                [
                    'type'    => 'integer',
                    'name'    => 'GV_ong_actif',
                    'default' => 1
                ],
                [
                    'type'      => 'string',
                    'name'      => 'GV_client_render_topics',
                    'default' => 'tree'
                ],
                [
                    'type'    => 'boolean',
                    'name'    => 'GV_rollover_reg_preview',
                    'default' => true
                ],
                [
                    'type'    => 'boolean',
                    'name'    => 'GV_rollover_chu',
                    'default' => true
                ],
                [
                    'type'      => 'string',
                    'name'      => 'GV_client_coll_ckbox',
                    'default'   => 'checkbox',
                ],
                [
                    'type'    => 'boolean',
                    'name'    => 'GV_viewSizeBaket',
                    'default' => true
                ],
                [
                    'type'    => 'boolean',
                    'name'    => 'GV_clientAutoShowProposals',
                    'default' => true
                ],
                [
                    'type'    => 'boolean',
                    'name'    => 'GV_needAuth2DL',
                    'default' => true
                ],
                [
                    'type'    => 'boolean',
                    'name'    => 'GV_requireTOUValidationForExport',
                    'default' => false
                ],
                [
                    'type'    => 'boolean',
                    'name'    => 'GV_autoselectDB',
                    'default' => true
                ],
                [
                    'type'    => 'boolean',
                    'name'    => 'GV_autoregister',
                    'default' => false
                ],
                [
                    'type'    => 'integer',
                    'name'    => 'GV_validation_reminder',
                    'default' => 2
                ],
                [
                    'type'    => 'integer',
                    'name'    => 'GV_val_expiration',
                    'default' => 10
                ],
                [
                    'type'    => 'string',
                    'name'    => 'GV_homeTitle',
                    'default' => 'Phraseanet'
                ],
                [
                    'type'    => 'string',
                    'name'    => 'GV_metaKeywords',
                    'default' => ''
                ],
                [
                    'type'    => 'string',
                    'name'    => 'GV_metaDescription',
                    'default' => ''
                ],
                [
                    'type'    => 'string',
                    'name'    => 'GV_googleAnalytics',
                    'default' => ''
                ],
                [
                    'type'    => 'boolean',
                    'name'    => 'GV_allow_search_engine',
                    'default' => true
                ],
        ];

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

        foreach ($GV as $datas) {
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
                case 'integer':
                    $val = (int) $val;
                    break;
                case 'boolean':
                    $val = $val ? '1' : '0';
                    break;
                case 'string':
                    $val = (string) $val;
                    break;
                default:
                    $val = (string) $val;
                    $type = 'string';
                    break;
            }

            $stmt->execute([
                ':key'   => $datas['name'],
                ':value' => $val,
                ':type'  => $type,
            ]);
        }

        $stmt->execute([
            ':key'   => 'GV_sit',
            ':value' => constant("GV_sit"),
            ':type'  => 'string',
        ]);

        $stmt->closeCursor();

        rename(__DIR__ . '/../../../../../../config/_GV.php', __DIR__ . '/../../../../../../config/_GV.php.old');
        $servername = defined('GV_ServerName') ? constant('GV_ServerName') : '';
        file_put_contents(__DIR__ . '/../../../../../../config/config.inc', "<?php\n\$servername = \"" . str_replace('"', '\"', $servername) . "\";\n");
        return;
    }
}
