<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Command\Setup;

use Alchemy\Phrasea\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class FixAutoincrements extends Command
{
    /** @var  InputInterface */
    private $input;

    /** @var  OutputInterface */
    private $output;

    /** @var  \appbox */
    private $appBox;

    /** @var  \databox[] */
    private $databoxes;

    public function __construct($name = null)
    {
        parent::__construct($name);

        $this
            ->setDescription("Fix autoincrements")
            ->addOption('dry', null, InputOption::VALUE_NONE, 'Dry run : list but do not update.');

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        $this->input     = $input;
        $this->output    = $output;
        $this->appBox    = $this->getContainer()->getApplicationBox();
        $this->databoxes = [];
        foreach ($this->getContainer()->getDataboxes() as $databox) {
            $this->databoxes[] = $databox;
        }

        $this
            ->ab_fix_bas()
            ->ab_fix_BasketElements()
            ->ab_fix_Baskets()
            ->ab_fix_Sessions()
            ->db_fix_coll()
            ->db_fix_record()
        ;
    }

    /**
     * @param \Appbox|\databox $box
     * @param $table
     * @param $subtables
     */
    private function box_fixTable($box, $table, $subtables)
    {
        $ouputTable = new Table($this->output);

        $title = sprintf("fixing table \"%s.%s\"", $box->get_dbname(), $table);
        $ouputTable->setHeaders([new TableCell($title, ['colspan'=>2])]);

        $max_id = $this->box_getMax($box, $table, $subtables, $ouputTable);
        //if($this->output->getVerbosity())
        $this->box_setAutoIncrement($box, $table, $max_id, $ouputTable);

        $ouputTable->render();

        $this->output->writeln("");
    }

    /**
     * @param \Appbox|\databox $box
     * @param $table
     * @param $subtables
     * @param Table $ouputTable
     * @return int
     */
    private function box_getMax($box, $table, $subtables, $ouputTable)
    {
        $sql = "  SELECT '".$box->get_dbname().'.'.$table.".AUTO_INCREMENT' AS src, AUTO_INCREMENT AS `id` FROM information_schema.TABLES\n"
            . "         WHERE TABLE_SCHEMA = :dbname AND TABLE_NAME = '" . $table . "'\n";

        foreach ($subtables as $subtable => $fieldname) {
            $sql .=
                "    UNION\n"
                . "  SELECT '".$box->get_dbname().'.'.$subtable.'.'.$fieldname."' AS src, MAX(`" . $fieldname . "`)+1 AS `id` FROM `" . $subtable . "`\n";
        }

        $stmt = $box->get_connection()->executeQuery($sql, [':dbname' => $box->get_dbname()]);
        $max_id = 0;
        $rows = $stmt->fetchAll();
        foreach($rows as $row) {
            $id = $row['id'];

            $ouputTable->addRow([$row['src'], is_null($id) ? "null" : $id]);

            if(!is_null($id)){
                $id = (int)$id;
                if ($id > $max_id) {
                    $max_id = $id;
                }
            }
        }
        $stmt->closeCursor();

        return $max_id;
    }

    /**
     * @param \Appbox|\databox $box
     * @param $table
     * @param $max_id
     * @param Table $ouputTable
     * @throws \Doctrine\DBAL\DBALException
     */
    private function box_setAutoIncrement($box, $table, $max_id, $ouputTable)
    {
        $sql = "ALTER TABLE `" . $table . "` AUTO_INCREMENT = " . $max_id;
        $msg = [
            sprintf("%s.%s.AUTO_INCREMENT set to", $box->get_dbname(), $table),
            $max_id,
        ];

        $this->box_setSQL($box, $sql, [], $msg, $ouputTable);
    }

    /**
     * @param \Appbox|\databox $box
     * @param string $sql
     * @param array $parms
     * @param string $msg[]
     * @param Table $ouputTable
     */
    private function box_setSQL($box, $sql, $parms, $msg, $ouputTable)
    {
        $ouputTable->addRow(New TableSeparator());
        if($this->input->getOption("dry")) {
            $msg[1] = sprintf("<comment>%s</comment> (dry-run : not done)", $msg[1]);
            $ouputTable->addRow($msg);
        }
        else {
            try {
                $stmt = $box->get_connection()->executeQuery($sql, $parms);
                $stmt->closeCursor();

                $msg[1] = sprintf("<info>%s</info>", $msg[1]);
                $ouputTable->addRow($msg);
            }
            catch(\Exception $e) {
                $msg[1] = sprintf("<error>%s</error>)", $msg[1]);
                $ouputTable->addRow($msg);
            }
        }
    }


    //
    //
    //============================= Databoxes =======================
    //
    //

    /**
     * fix every Databox "record" autoincrement
     *
     * @return $this
     * @throws \Doctrine\DBAL\DBALException
     */
    private function db_fix_coll()
    {
        foreach ($this->databoxes as $databox) {
            $this->box_fixTable(
                $databox,
                'coll',
                [
                    'collusr' => 'coll_id',
                ]
            );
        }

        return $this;
    }

    /**
     * fix every Databox "record" autoincrement
     *
     * @return $this
     * @throws \Doctrine\DBAL\DBALException
     */
    private function db_fix_record()
    {
        foreach ($this->databoxes as $databox) {
            $this->box_fixTable(
                $databox,
                'record',
                [
                    'log_docs' => 'record_id',
                    'log_view' => 'record_id',
                    'record'   => 'record_id',
                ]
            );
        }

        return $this;
    }


    //
    //
    //============================= ApplicationBox =======================
    //
    //

    /**
     * fix AppBox "bas" autoincrement
     *
     * @return $this
     * @throws \Doctrine\DBAL\DBALException
     */
    private function ab_fix_bas()
    {
        $this->box_fixTable(
            $this->appBox,
            'bas',
            [
                'basusr'            => 'base_id',
                'demand'            => 'base_id',
                'Feeds'             => 'base_id',
                'FtpExportElements' => 'base_id',
                'LazaretFiles'      => 'base_id',
                'OrderElements'     => 'base_id',
                'Registrations'     => 'base_id',
            ]
        );

        return $this;
    }


    /**
     * fix AppBox "BasketElements" autoincrement
     *
     * @return $this
     * @throws \Doctrine\DBAL\DBALException
     */
    private function ab_fix_BasketElements()
    {
        $this->box_fixTable(
            $this->appBox,
            'BasketElements',
            [
                'ValidationDatas' => 'basket_element_id',
            ]
        );

        return $this;
    }


    /**
     * fix AppBox "Baskets" autoincrement
     *
     * @return $this
     * @throws \Doctrine\DBAL\DBALException
     */
    private function ab_fix_Baskets()
    {
        $this->box_fixTable(
            $this->appBox,
            'Baskets',
            [
                'BasketElements'     => 'basket_id',
                'Orders'             => 'basket_id',
                'ValidationSessions' => 'basket_id',
            ]
        );

        return $this;
    }


    /**
     * fix AppBox "Sessions" autoincrement
     *
     * @return $this
     * @throws \Doctrine\DBAL\DBALException
     */
    private function ab_fix_Sessions()
    {
        $ouputTable = new Table($this->output);

        $title = sprintf("fixing table \"%s.%s\"", $this->appBox->get_dbname(), "Sessions");
        $ouputTable->setHeaders([new TableCell($title, ['colspan'=>2])]);

        $site = $this->getContainer()['conf']->get(['main', 'key']);

        // if autoincrement, get the current value as a minimum
        $max_SessionId = $this->box_getMax(
            $this->appBox,
            'Sessions',
            [],          // no sub-tables
            $ouputTable
        );

        // get max session from databoxes, using the "log" table which refers to ab.Sessions ids
        foreach ($this->databoxes as $databox) {
            $db = $databox->get_connection();

            $sql = "SELECT MAX(`sit_session`) FROM `log` WHERE `site` = :site";
            $stmt = $db->executeQuery($sql, [':site' => $site]);
            $id = $stmt->fetchColumn(0);

            $ouputTable->addRow([sprintf("%s.log.sit_session", $databox->get_dbname()), sprintf("%s", is_null($id) ? 'null' : $id)]);

            if(!is_null($id)) {
                $id = (int)$id + 1;
                if ($id > $max_SessionId) {
                    $max_SessionId = $id;
                }
            }
            $stmt->closeCursor();
        }

        // fix using different methods
        foreach ([
                    // 4.0 with autoincrement
                    [
                        'sql'   => "ALTER TABLE `Sessions` AUTO_INCREMENT = " . $max_SessionId,   // can't use parameter here
                        'parms' => [],
                        'msg'   => ["ab.Sessions.AUTO_INCREMENT set to", $max_SessionId],
                    ],
                    /* --- custon generators not yet implemented in phraseanet
                    // 4.0 with custom generator already set
                    [
                        'sql'   => "UPDATE `Id` SET value = :v WHERE `id` = :k",
                        'parms' => [':v' => $max_SessionId, ':k' => 'Alchemy\\Phrasea\\Model\\Entities\\Session'],
                        'msg'   => ["ab.Id['Sessions']' set to", $max_SessionId],
                    ],
                    // 4.0 with custom generator not yet set
                    [
                        'sql'   => "INSERT INTO `Id` (`id`, `value`) VALUES (:k, :v)",
                        'parms' => [':v' => $max_SessionId, ':k' => 'Alchemy\\Phrasea\\Model\\Entities\\Session'],
                        'msg'   => ["ab.Id['Sessions']' set to", $max_SessionId],
                    ]
                    --- */
                ] as $sql) {

            // one or more sql will fail, no pb
            $this->box_setSQL($this->appBox, $sql['sql'], $sql['parms'], $sql['msg'], $ouputTable);
        }

        $ouputTable->render();

        $this->output->writeln("");

        return $this;
    }

}

