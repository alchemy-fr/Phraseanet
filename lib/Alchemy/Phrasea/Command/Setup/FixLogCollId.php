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

    const PLAYDRY_NONE = 0;
    const PLAYDRY_SQL  = 1;
    const PLAYDRY_CODE = 2;

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


        foreach($this->databoxes as $databox) {

            $this->output->writeln("");
            $this->output->writeln(sprintf("<info>================================ Working on databox %s (id:%s) ================================</info>", $databox->get_dbname(), $databox->get_sbas_id()));

            if (!$this->showCount($databox)) {
                // databox not patched
                break;
            }

            $this->createWorkingTable($databox);

            // loop to compute coll_id from top to bottom
            do {
                $n = $this->computeCollId($databox);   // in dry mode, n=0
            }
            while ($n > 0);

            // set the "from_coll_id"
            $this->computeCollIdFrom($databox);

            // copy results back to the log_docs
            $this->copyReults($databox);


            if (!$this->keep_tmp_table) {
                $this->dropWorkingTable($databox);
            }

            $this->output->writeln("");
        }

        return 0;
    }

    private function createWorkingTable(\databox $databox)
    {
        $this->output->writeln("");
        $this->output->writeln(sprintf("<info> ----------------- Creating working %s table -----------------</info>", ($this->keep_tmp_table ? "(temporary)" : "")));
        $this->output->writeln("");

        $sql = "CREATE " . ($this->keep_tmp_table ? "TABLE IF NOT EXISTS" : "TEMPORARY TABLE") . " `tmp_colls` (\n"
            . " `record_id` int(11) unsigned NOT NULL,\n"
            . " `from_id` int(11) unsigned NOT NULL,\n"
            . " `from_date` datetime NOT NULL,\n"
            . " `to_id` int(11) unsigned DEFAULT NULL,\n"
            . " `to_date` datetime DEFAULT NULL,\n"
            . " `coll_id_from` int(10) unsigned DEFAULT NULL,\n"
            . " `coll_id` int(10) unsigned DEFAULT NULL,\n"
            . " KEY `record_id` (`record_id`),\n"
            . " KEY `from_id` (`from_id`),\n"
            . " KEY `from_date` (`from_date`),\n"
            . " KEY `to_id` (`to_id`),\n"
            . " KEY `to_date` (`to_date`)\n"
            . ") ENGINE=InnoDB;";

        if($this->show_sql) {
            $this->output->writeln($sql);
            $this->output->writeln("");
        }
        $stmt = $databox->get_connection()->prepare($sql);
        $stmt->execute();
        $stmt->closeCursor();

        $sql = "TRUNCATE TABLE `tmp_colls`";

        if($this->show_sql) {
            $this->output->writeln($sql);
            $this->output->writeln("");
        }
        $stmt = $databox->get_connection()->prepare($sql);
        $stmt->execute();
        $stmt->closeCursor();
    }

    private function dropWorkingTable(\databox $databox)
    {
        $this->output->writeln(sprintf("<info> ----------------- Drop working table -----------------</info>"));
        $sql = "DROP TABLE `tmp_colls`";
        if($this->show_sql) {
            $this->output->writeln($sql);
        }
        $stmt = $databox->get_connection()->prepare($sql);
        $stmt->execute();
        $stmt->closeCursor();
    }

    private function showCount(\databox $databox)
    {
        $ret = true;
        $this->playSQL(
            $databox,
            [
                'msg' => "Count work to do",
                'sql' => "SELECT\n"
                    . "  SUM(IF(ISNULL(`coll_id`), 0, 1)) AS `n`,\n"
                    . "  COUNT(*) AS `t`,\n"
                    . "  SUM(IF(`coll_id`>0, 1, 0)) AS `p`,\n"
                    . "  SUM(IF(`coll_id`=0, 1, 0)) AS `z`\n"
                    . "  FROM `log_docs`",
                'code' => function(ResultStatement $stmt) use($ret) {
                    $row = $stmt->fetch();
                    if(is_null($row['n'])) {
                        // no coll_id ?
                        $this->output->writeln(sprintf("<error>The \"log_docs\" table has no \"coll_id\" column ? Please apply patch 410alpha12a</error>"));
                        $ret = false;
                    }
                    $this->output->writeln("");
                    $this->output->writeln(sprintf("done: %s / %s  (fixed: %s ; can't fix: %s)", $row['n'], $row['t'], $row['p'], $row['z']));
                },
                'playdry' =>  self::PLAYDRY_SQL | self::PLAYDRY_CODE,
            ]
        );

        return $ret;
    }

    private function computeCollId(\databox $databox)
    {
        static $sql_lastid = null;
        static $stmt_lastid = null;

        static $sql_insert = null;
        static $stmt_insert = null;

        $ret = 0;
        if(!$stmt_lastid) {
            $sql_lastid = "SELECT @m:=COALESCE(MAX(`from_id`), 0) AS `lastid` FROM `tmp_colls`";

            $stmt_lastid = $databox->get_connection()->prepare($sql_lastid);

            $sql_insert =  "INSERT INTO `tmp_colls`\n"
                . "    SELECT `r1`.`record_id`, `r1`.`id` AS `from_id`, `r1`.`date` AS `from_date`, MIN(`r2`.`id`) AS `to_id`, MIN(`r2`.`date`) AS `to_date`, NULL AS `coll_id_from`, `r1`.`final` AS `coll_id`\n"
                . "    FROM (\n"
                . "      SELECT `id`, `date`, `record_id`, `action`, `final` FROM `log_docs`\n"
                . "      WHERE `id` > @m AND `action` IN('add', 'collection')\n"
                . "      ORDER BY `id` ASC\n"
                . "      LIMIT " . $this->batch_size . "\n"
                . "    ) AS `r1`\n"
                . "    LEFT JOIN `log_docs` AS `r2`\n"
                . "    ON `r2`.`record_id`=`r1`.`record_id` AND `r2`.`action`='collection' AND `r2`.`id`>`r1`.`id`\n"
                . "    GROUP BY `r1`.`id`\n"
                // . "    ORDER BY `record_id` ASC, `from_id` ASC"
            ;

            $stmt_insert = $databox->get_connection()->prepare($sql_insert);

            $this->output->writeln("");
            $this->output->writeln(sprintf("<info> ----------------- Compute coll_id to working table ----------------- %s</info>",
                    $this->dry ? " -- NOT PLAYED IN DRY MODE --" : ""
                )
            );
            $this->output->writeln("");
            if ($this->show_sql) {
                $this->output->writeln($sql_lastid);
                $this->output->writeln($sql_insert);
            }
        }

        if(!$this->dry) {
            $stmt_lastid->execute();
            $stmt_lastid->closeCursor();

            $stmt_insert->execute();
            $ret = $stmt_insert->rowCount();
            $stmt_insert->closeCursor();
        }

        return $ret;
    }

    private function computeCollIdFrom(\databox $databox)
    {
        static $sql = null;
        static $stmt = null;

        $ret = 0;
        if(!$stmt) {
            $sql =  "UPDATE `tmp_colls` AS `t1` INNER JOIN `tmp_colls` AS `t2` ON `t2`.`from_id`=`t1`.`to_id`\n"
                . " SET `t2`.`coll_id_from` = `t1`.`coll_id`";

            $this->output->writeln("");
            $this->output->writeln(sprintf("<info> ----------------- Compute coll_id_from to working table ----------------- %s</info>",
                    $this->dry ? " -- NOT PLAYED IN DRY MODE --" : ""
                )
            );
            $this->output->writeln("");
            if ($this->show_sql) {
                $this->output->writeln($sql);
            }

            $stmt = $databox->get_connection()->prepare($sql);
        }

        if(!$this->dry) {
            $stmt->execute();
            $ret = $stmt->rowCount();
            $stmt->closeCursor();
        }

        return $ret;
    }

    private function copyReults(\databox $databox)
    {
        $this->playSQL(
            $databox,
            [
                'msg' => "Copy result back to \"log_docs\"",
                'sql' => "UPDATE `tmp_colls` AS `t` INNER JOIN `log_docs` AS `d`\n"
                    . " ON ISNULL(`d`.`coll_id`)\n"
                    . "    AND `t`.`record_id` = `d`.`record_id`\n"
                    . "    AND `d`.`id` >= `t`.`from_id`\n"
                    . "    AND (`d`.`id` < `t`.`to_id` OR ISNULL(`t`.`to_id`))\n"
                    . " SET `d`.`coll_id_from` = IF(`action`='collection', `t`.`coll_id_from`, NULL),\n"
                    . "     `d`.`coll_id` = `t`.`coll_id`",
                'code' => null,
                'playdry' => self::PLAYDRY_NONE,
            ]
        );

        $this->playSQL(
            $databox,
            [
                'msg' => "Copy result back to \"log_view\"",
                'sql' => "UPDATE `log_view` AS `v` INNER JOIN `tmp_colls` AS `t`\n"
                    . " ON ISNULL(`v`.`coll_id`)\n"
                    . "    AND `t`.`record_id` = `v`.`record_id`\n"
                    . "    AND `v`.`date` >= `t`.`from_date`\n"
                    . "    AND (`v`.`date` < `t`.`to_date` OR ISNULL(`t`.`to_date`))\n"
                    . " SET `v`.`coll_id` = `t`.`coll_id`",
                'code' => null,
                'playdry' => self::PLAYDRY_NONE,
            ]
        );

    }


    private function playSQL(\databox $databox, Array $work)
    {
        $this->output->writeln("");
        $this->output->writeln(sprintf("<info> ----------------- %s ----------------- %s</info>",
                $work['msg'],
                $this->dry && !($work['playdry'] & self::PLAYDRY_SQL) ? " -- NOT PLAYED IN DRY MODE --" : ""
            )
        );
        $this->output->writeln("");

        if ($this->show_sql) {
            $this->output->writeln($work['sql']);
        }
        $stmt = null;
        if(!$this->dry || ($work['playdry'] & self::PLAYDRY_SQL)) {
            $stmt = $databox->get_connection()->prepare($work['sql']);
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
}

