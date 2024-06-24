<?php

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Core\Configuration\PropertyAccess;
use Doctrine\DBAL\Exception\TableExistsException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Yaml\Yaml;

class patch_419PHRAS4078 implements patchInterface
{
    /** @var string */
    private $release = '4.1.9';

    /** @var array */
    private $concern = [base::APPLICATION_BOX];

    /**
     * {@inheritdoc}
     */
    public function get_release()
    {
        return $this->release;
    }

    /**
     * {@inheritdoc}
     */
    public function getDoctrineMigrations()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function require_all_upgrades()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function concern()
    {
        return $this->concern;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(base $base, Application $app)
    {
        $this->app = $app;
        $this->logger = $app['logger'];

        if ($base->get_base_type() === base::DATA_BOX) {
            $this->patch_databox($base, $app);
        } elseif ($base->get_base_type() === base::APPLICATION_BOX) {
            $this->patch_appbox($base, $app);
        }

        return true;
    }

    private function patch_databox(databox $databox, Application $app)
    {
    }

    private function patch_appbox(base $appbox, Application $app)
    {
        $this->migrateConfig();
        $this->migrateTable($appbox);
    }

    const TABLENAME = "_ExpiringRights";
    const TABLENAME3 = "ExpiringRights";
    const CONFIG_DIR = "/config/plugins/expirating-rights-plugin/";
    const CONFIG_FILE = "configuration.yml";
    /** @var Application $app */
    private $app;
    /** @var LoggerInterface $logger */
    private $logger;
    private $config;

    private function migrateConfig()
    {
        /** @var PropertyAccess $conf */
        $phrconfig = $this->app['conf'];

        if ($phrconfig->has(['expiring-rights'])) {
            return;
        }

        // locate the config for the ExpiringRights plugin (v1 or v3)
        $config_dir = $this->app['root.path'] . self::CONFIG_DIR;
        $config_file = $config_dir . self::CONFIG_FILE;
        $piconfig = ['jobs' => []];
        if (file_exists($config_file)) {
            try {
                $piconfig = Yaml::parse(file_get_contents($config_file));
            }
            catch (\Exception $e) {
                $piconfig = ['jobs' => []];
            }

            if (array_key_exists('databoxes', $piconfig)) {
                // migrate the job settings to v3
                $jobs3 = [];
                foreach ($piconfig['jobs'] as $job) {
                    $jobname = $job['job'];

                    // find databox
                    $found = 0;
                    foreach ($piconfig['databoxes'] as $db) {
                        if ($db['databox'] === $job['databox']) {
                            unset($job['job']);
                            $jobs3[$jobname] = $job;
                            $jobs3[$jobname]['target'] = "downloader";
                            $jobs3[$jobname]['collection'] = array_key_exists('collection', $db) ? $db['collection'] : [];
                            $jobs3[$jobname]['downloaded'] = array_key_exists('downloaded', $db) ? $db['downloaded'] : [];
                            $jobs3[$jobname]['expire_field'] = $db['expire_field'];
                            $found++;
                        }
                    }

                    if ($found != 1) {
                        $msg = sprintf("error migrating job \"%s\": databox not found or not unique", $jobname);
                        $this->logger->error(sprintf("<error>%s</error>", $msg));
                    }
                }

                $piconfig = [
                    'version' => 3,
                    'jobs'    => $jobs3
                ];
            }

            rename($config_file, $config_file . "_bkp");
        }

        $phrconfig->set(['plugins', 'expirating-rights-plugin', 'enabled'], false);
        $phrconfig->set(['expiring-rights'], $piconfig);
    }


    private function migrateTable(appbox $appbox)
    {
        // create the table
        $sql = "CREATE TABLE `ExpiringRights` (\n"
            . "`id` int(11) unsigned NOT NULL AUTO_INCREMENT,\n"
            . "`job` char(128) DEFAULT NULL,\n"
            . "`downloaded` datetime DEFAULT NULL,\n"
            . "`user_id` int(11) unsigned DEFAULT NULL,\n"
            . "`email` char(128) DEFAULT NULL,\n"
            . "`sbas_id` int(11) unsigned DEFAULT NULL,\n"
            . "`base` char(50) DEFAULT NULL,\n"
            . "`collection` char(50) DEFAULT NULL,\n"
            . "`record_id` int(11) unsigned DEFAULT NULL,\n"
            . "`title` char(200) DEFAULT NULL,\n"
            . "`expire` datetime DEFAULT NULL,\n"
            . "`new_expire` datetime DEFAULT NULL,\n"
            . "`alerted` datetime DEFAULT NULL,\n"
            . "PRIMARY KEY (`id`),\n"
            . "KEY `job` (`job`),\n"
            . "KEY `sbas_id` (`sbas_id`),\n"
            . "KEY `expire` (`expire`),\n"
            . "KEY `new_expire` (`new_expire`),\n"
            . "KEY `alerted` (`alerted`),\n"
            . "UNIQUE KEY `unique` (job,user_id,sbas_id,record_id)"
            . ") ENGINE=InnoDB CHARSET=utf8;";

        $stmt = $appbox->get_connection()->prepare($sql);
        try {
            $stmt->execute();
        }
        catch(TableExistsException $e) {
            // table v3 already exists, skip migration
            return;
        }
        catch(\Exception $e) {
            // fatal
            return;
        }
        $stmt->closeCursor();

        // migrate data from v-1 ?
        try {
            $appbox->get_connection()->exec("SELECT `id` FROM `_ExpiringRights` LIMIT 1");
        }
        catch (\Exception $e) {
            // failed : no table to copy ?
            return;
        }

        /** @var PropertyAccess $conf */
        $phrconfig = $this->app['conf'];

        if (!$phrconfig->has(['expiring-rights'])) {
            return;
        }

        // table v3 was just created, insert from table v-1
        $sql = "INSERT INTO `ExpiringRights` (\n"
            . " `job`, \n"
            . " `alerted`,\n"
            . " `base`,\n"
            . " `collection`,\n"
            . " `downloaded`,\n"
            . " `email`,\n"
            . " `expire`,\n"
            . " `new_expire`,\n"
            . " `record_id`,\n"
            . " `sbas_id`,\n"
            . " `title`,\n"
            . " `user_id`)\n"
            . "SELECT\n"
            . " :job AS `job`,\n"
            . " MAX(`alerted`),\n"
            . " `base`,\n"
            . " `collection`,\n"
            . " MAX(`downloaded`),\n"
            . " `email`,\n"
            . " MAX(`expire`),\n"
            . " MAX(`new_expire`),\n"
            . " `record_id`,\n"
            . " `sbas_id`,\n"
            . " `title`,\n"
            . " `user_id`\n"
            . "FROM `_ExpiringRights` GROUP BY `user_id`, `sbas_id`, `record_id` ORDER BY `id` ASC";
        $stmtCopy = $appbox->get_connection()->prepare($sql);

        $config = $phrconfig->get(['expiring-rights']);
        foreach ($config['jobs'] as $jobname => $job) {

            // copy v-1 rows to v3 (add job)
            $stmtCopy->execute([':job' => $jobname]);

            // fix alerted too early
            $delta = $job['prior_notice'];
            if ($delta > 0) {
                $value = "(`expire`+INTERVAL " . $delta . " DAY)";
            } elseif ($delta < 0) {
                $value = "(`expire`-INTERVAL " . -$delta . " DAY)";
            } else {
                $value = "`expire`";
            }
            $sql = "UPDATE `ExpiringRights` SET `alerted`=NULL WHERE `job` = :job AND `alerted` < " . $value;
            $stmtEarly = $appbox->get_connection()->prepare($sql);
            $stmtEarly->execute([':job' => $jobname]);
            $stmtEarly->closeCursor();
        }
        $stmtCopy->closeCursor();

        // fix bad date
        $sql = "UPDATE `ExpiringRights` SET `expire`=NULL WHERE `expire`='0000-00-00'";
        $stmt = $appbox->get_connection()->prepare($sql);
        $stmt->execute([]);
        $stmt->closeCursor();

        // fix useless new_expire
        $sql = "UPDATE `ExpiringRights` SET `new_expire`=NULL WHERE `new_expire` = `expire`";
        $stmt = $appbox->get_connection()->prepare($sql);
        $stmt->execute([]);
        $stmt->closeCursor();
    }

}
