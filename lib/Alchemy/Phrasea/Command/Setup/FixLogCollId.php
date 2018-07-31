<?php
/**
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Command\Setup;

use Alchemy\Phrasea\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Doctrine\DBAL\Driver\ResultStatement;


class FixLogCollId extends Command
{
    const OPTION_DISTINT_VALUES = 0;
    const OPTION_ALL_VALUES     = 1;

    /** @var InputInterface */
    private $input;
    /** @var OutputInterface */
    private $output;
    /** @var \Databox[] */
    private $databoxes;

    /** @var int */
    private $batch_size;
    /** @var bool */
    private $dry;
    /** @var bool */
    private $show_sql;
    /** @var bool */
    private $keep_tmp_table;

    private $parm_k = [':minid', ':maxid'];
    private $parm_v = [];
    private $again;

    const PLAYDRY_NONE = 0;
    const PLAYDRY_SQL  = 1;
    const PLAYDRY_CODE = 2;

    public function __construct($name = null)
    {
        parent::__construct("patch:log_coll_id");

        $this->setDescription('Fix empty (null) coll_id in "log_docs" and "log_view" tables.');
        $this->addOption('databox',            null, InputOption::VALUE_OPTIONAL, 'Mandatory : The id (or dbname or viewname) of the databox');
        $this->addOption('batch_size',         null, InputOption::VALUE_OPTIONAL, 'work on a batch of n entries (default=100000)');
        $this->addOption('dry',                null, InputOption::VALUE_NONE,     'dry run, list but don\'t act');
        $this->addOption('show_sql',           null, InputOption::VALUE_NONE,     'show sql pre-selecting records');
        $this->addOption('keep_tmp_table',     null, InputOption::VALUE_NONE,     'keep the working "tmp_coll" table (help debug)');

        $this->setHelp("help");
    }

    /**
     * sanity check the cmd line options
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return bool
     */
    protected function sanitizeArgs(InputInterface $input, OutputInterface $output)
    {
        $argsOK = true;

        // find the databox / collection by id or by name
        $this->databoxes = [];
        if(!is_null($d = $input->getOption('databox'))) {
            $d = trim($d);
        }
        foreach ($this->container->getDataboxes() as $db) {
            if(is_null($d)){
                $this->databoxes[] = $db;
            }
            else {
                if ($db->get_sbas_id() == (int)$d || $db->get_viewname() == $d || $db->get_dbname() == $d) {
                    $this->databoxes[] = $db;
                }
            }
        }
        if (empty($this->databoxes)) {
            if(is_null($d)) {
                $output->writeln(sprintf("<error>No databox found</error>", $d));
            }
            else {
                $output->writeln(sprintf("<error>Unknown databox \"%s\"</error>", $d));
            }
            $argsOK = false;
        }

        // get options
        $this->batch_size      = $input->getOption('batch_size');
        $this->show_sql       = $input->getOption('show_sql') ? true : false;
        $this->dry            = $input->getOption('dry') ? true : false;
        $this->keep_tmp_table = $input->getOption('keep_tmp_table') ? true : false;

        if(is_null($this->batch_size)) {
            $this->batch_size = 100000;
        }
        if($this->batch_size < 1) {
            $output->writeln(sprintf('<error>batch_size must be > 0</error>'));
            $argsOK = false;
        }

        return $argsOK;
    }

    /**
     * {@inheritdoc}
     */
    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        // $time_start = new \DateTime();

        if(!$this->sanitizeArgs($input, $output)) {
            return -1;
        }

        $this->input  = $input;
        $this->output = $output;

        $tsql = [
            'count' => [
                'msg' => "Count work to do",
                'sql' => "SELECT\n"
                        . "  SUM(IF(ISNULL(`coll_id`), 0, 1)) AS `n`,\n"
                        . "  COUNT(*) AS `t`,\n"
                        . "  SUM(IF(`coll_id`>0, 1, 0)) AS `p`,\n"
                        . "  SUM(IF(`coll_id`=0, 1, 0)) AS `z`\n"
                        . "  FROM `log_docs`",
                'fetch' => false,
                'code' => function(ResultStatement $stmt) {
                    $row = $stmt->fetch();
                    if(is_null($row['n'])) {
                        // no coll_id ?
                        $this->output->writeln(sprintf("<error>The \"log_docs\" table has no \"coll_id\" column ? Please apply patch 410alpha12a</error>"));
                        $this->again = false;
                    }
                    $this->output->writeln("");
                    $this->output->writeln(sprintf("done: %s / %s  (fixed: %s ; can't fix: %s)", $row['n'], $row['t'], $row['p'], $row['z']));
                },
                'playdry' =>  self::PLAYDRY_SQL | self::PLAYDRY_CODE,
            ],

            'minmax' => [
                'msg' => "Get a batch",
                'sql' => "SELECT MIN(`id`) AS `minid`, MAX(`id`) AS `maxid` FROM\n"
                        . "  (SELECT `id` FROM `log_docs` WHERE ISNULL(`coll_id`) ORDER BY `id` DESC LIMIT " . $this->batch_size . ") AS `t",
                'fetch' => false,
                'code' => function(ResultStatement $stmt) {
                    $row = $stmt->fetch();
                    $this->output->writeln("");
                    $this->output->writeln(sprintf("minid: %s ; maxid : %s\n", is_null($row['minid']) ? 'null' : $row['minid'], is_null($row['maxid']) ? 'null' : $row['maxid']));
                    if (is_null($row['minid']) || is_null($row['maxid'])) {
                        $this->again = false;
                    }
                    $this->parm_v = [(int)$row['minid'], (int)$row['maxid']];
                },
                'playdry' => self::PLAYDRY_SQL | self::PLAYDRY_CODE,
            ],

            'trunc' => [
                'msg' => "Empty working table",
                'sql' => "TRUNCATE TABLE `tmp_colls`",
                'fetch' => false,
                'code' => null,
                'playdry' => self::PLAYDRY_NONE,
            ],

            'offset' => [
                'msg' => "Make room for \"collection_from\" actions",
                'sql' => "UPDATE `log_docs` SET `id` = `id` * 2 WHERE `id` >= :minid AND `id` <= :maxid ORDER BY `id` DESC",
                'fetch' => false,
                'code' =>
                    function(/** @noinspection PhpUnusedParameterInspection */ $stmt) {
                    // fix new minmax values since id was changed
                    $this->parm_v = [$this->parm_v[0] << 1, $this->parm_v[1] << 1];
                },
                'playdry' => self::PLAYDRY_CODE,
            ],

            'compute' => [
                'msg' => "Compute coll_id to working table",
                'sql' => "INSERT INTO `tmp_colls`\n"
                        . "  SELECT `record_id`, `r1_id` AS `from_id`, `r1_date` AS `from_date`, MIN(`r2_id`) AS `to_id`, MIN(`r2_date`) AS `to_date`, `r1_final` AS `coll_id` FROM\n"
                        . "  (\n"
                        . "    SELECT `r1`.`record_id`, `r1`.`id` AS `r1_id`, `r1`.`date` AS `r1_date`, `r1`.`final` AS `r1_final`, `r2`.`id` AS `r2_id`, `r2`.`date` AS `r2_date` FROM\n"
                        . "      (SELECT `id`, `date`, `record_id`, `action`, `final` FROM `log_docs` WHERE `action` IN('add', 'collection') AND `id` >= :minid AND `id` <= :maxid) AS `r1`\n"
                        . "      LEFT JOIN `log_docs` AS `r2`\n"
                        . "      ON `r2`.`record_id`=`r1`.`record_id` AND `r2`.`action`='collection' AND `r2`.`id`>`r1`.`id`\n"
                        . "  )\n"
                        . "  AS `t` GROUP BY `r1_id` ORDER BY `record_id` ASC, `from_id` ASC",
                'fetch' => false,
                'code' => null,
                'playdry' => self::PLAYDRY_NONE,
            ],

            'copy_result' => [
                'msg' => "Copy result back to \"log_docs\"",
                'sql' => "UPDATE `tmp_colls` INNER JOIN `log_docs`\n"
                        . " ON `tmp_colls`.`record_id` = `log_docs`.`record_id`\n"
                        . "    AND `log_docs`.`id` >= `tmp_colls`.`from_id`\n"
                        . "    AND (`log_docs`.`id` < `tmp_colls`.`to_id` OR ISNULL(`tmp_colls`.`to_id`))\n"
                        . " SET `log_docs`.`coll_id` = `tmp_colls`.`coll_id`",
                'fetch' => false,
                'code' => null,
                'playdry' => self::PLAYDRY_NONE,
            ],

            'collection_from' => [
                'msg' => "Insert \"collection_from\" actions",
                'sql' => "INSERT INTO `log_docs` (`id`, `log_id`, `date`, `record_id`, `action`, `final`, `coll_id`)\n"
                        . " SELECT `r1`.`id`-1 AS `id`, `r1`.`log_id`, `r1`.`date`, `r1`.`record_id`, 'collection_from' AS `action`, `r1`.`final`,\n"
                        . "        SUBSTRING_INDEX(GROUP_CONCAT(`r2`.`coll_id` ORDER BY `r1`.`id` DESC), ',', 1) AS `coll_id`\n"
                        . " FROM `log_docs` AS `r1` LEFT JOIN `log_docs` AS `r2`\n"
                        . "   ON `r2`.`record_id` = `r1`.`record_id` AND `r2`.`id` < `r1`.`id` AND `r2`.`action` IN('collection', 'add')\n"
                        . " WHERE `r1`.`action` = 'collection' AND `r1`.`id` >= :minid AND `r1`.`id` <= :maxid\n"
                        . " GROUP BY `r1`.`id`",
                'fetch' => false,
                'code' => null,
                'playdry' => self::PLAYDRY_NONE,
            ],

            'fix_unfound' => [
                'msg' => "Set missing coll_id to 0",
                'sql' => "UPDATE `log_docs` SET `coll_id` = 0 WHERE `id` >= :minid AND `id` <= :maxid AND ISNULL(`coll_id`)",
                'fetch' => false,
                'code' => null,
                'playdry' => self::PLAYDRY_NONE,
            ],

            'fix_view' => [
                'msg' => "Fix \"log_view.coll_id\"",
                'sql' => "UPDATE `tmp_colls` AS `c` INNER JOIN `log_view` AS `v`\n"
                        . " ON `v`.`record_id` = `c`.`record_id` AND `v`.`date` >= `c`.`from_date` AND (`v`.`date` < `c`.`to_date` OR ISNULL(`c`.`to_date`))\n"
                        . " SET `v`.`coll_id` = `c`.`coll_id`",
                'fetch' => false,
                'code' => null,
                'playdry' => self::PLAYDRY_NONE,
            ],
        ];



        foreach($this->databoxes as $databox) {

            $this->output->writeln("");
            $this->output->writeln(sprintf("<info>================================ Working on databox %s (id:%s) ================================</info>", $databox->get_dbname(), $databox->get_sbas_id()));

            $sql = "CREATE " . ($this->keep_tmp_table ? "TABLE IF NOT EXISTS" : "TEMPORARY TABLE") . " `tmp_colls` (\n"
                . " `record_id` int(11) unsigned NOT NULL,\n"
                . " `from_id` int(11) unsigned NOT NULL,\n"
                . " `from_date` datetime NOT NULL,\n"
                . " `to_id` int(11) unsigned DEFAULT NULL,\n"
                . " `to_date` datetime DEFAULT NULL,\n"
                . " `coll_id` int(10) unsigned NOT NULL,\n"
                . " KEY `record_id` (`record_id`),\n"
                . " KEY `from_id` (`from_id`),\n"
                . " KEY `from_date` (`from_date`),\n"
                . " KEY `to_id` (`to_id`),\n"
                . " KEY `to_date` (`to_date`)\n"
                . ") ENGINE=InnoDB;";

            $this->output->writeln("");
            $this->output->writeln(sprintf("<info> ----------------- Creating working %s table -----------------</info>", ($this->keep_tmp_table ? "(temporary)" : "")));
            $this->output->writeln("");
            if($this->show_sql) {
                $this->output->writeln($sql);
            }
            $stmt = $databox->get_connection()->prepare($sql);
            $stmt->execute();
            $stmt->closeCursor();

            for ($this->again=true; $this->again; ) {

                foreach($tsql as $work) {
                    if(!$this->again) {
                        break;
                    }
                    $this->output->writeln("");
                    $this->output->writeln(sprintf("<info> ----------------- %s ----------------- %s</info>",
                                            $work['msg'],
                                            $this->dry && !($work['playdry'] & self::PLAYDRY_SQL) ? " -- NOT PLAYED IN DRY MODE --" : ""
                        )
                    );
                    $this->output->writeln("");

                    $sql = str_replace($this->parm_k, $this->parm_v, $work['sql']);
                    if ($this->show_sql) {
                        $this->output->writeln($sql);
                    }
                    $stmt = null;
                    if(!$this->dry || ($work['playdry'] & self::PLAYDRY_SQL)) {
                        $stmt = $databox->get_connection()->prepare($sql);
                        $stmt->execute();
                    }
                    if($work['code'] && (!$this->dry || ($work['playdry'] & self::PLAYDRY_CODE))) {
                        $code = $work['code'];
                        $code($stmt);
                    }
                    if(!$this->dry || ($work['playdry'] & self::PLAYDRY_SQL)) {
                        $stmt->closeCursor();
                    }
                }

                if($this->dry) {
                    // since there was no changes it will loop forever
                    $this->again = false;
                }
            }


            if (!$this->keep_tmp_table) {
                $this->output->writeln(sprintf("<info> ----------------- Drop working table -----------------</info>"));
                $sql = "DROP TABLE `tmp_colls`";
                if($this->show_sql) {
                    $this->output->writeln($sql);
                }
                $stmt = $databox->get_connection()->prepare($sql);
                $stmt->execute();
                $stmt->closeCursor();
            }

            $this->output->writeln("");
        }

        return 0;
    }

}
