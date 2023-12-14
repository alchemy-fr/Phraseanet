<?php

namespace Alchemy\Phrasea\Command\Maintenance;

use Alchemy\Phrasea\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CleanLogDocsCommand extends Command
{
    const ACTIONS = ['push','add','validate','edit','collection','status','print','substit','publish','download','mail','ftp','delete'];
    const AVAILABLE_ACTIONS = ['download', 'mail', 'ftp', 'delete'];

    public function __construct()
    {
        parent::__construct('clean:log_docs');

        $this
            ->setDescription('Beta - clean the log_docs for all databox (if not specified) or a specific databox_id ')
            ->addOption('databox_id', null, InputOption::VALUE_REQUIRED,                             'the databox to clean')
            ->addOption('older_than',       null, InputOption::VALUE_REQUIRED,                             'delete older than <OLDER_THAN>')
            ->addOption('action',       null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,                             'download, mail, ftp, delete (if delete , delete also log entry for all events like push, add , validate, edit, collection, status, print, substit, publish for this record_id)')
            ->addOption('dry-run',        null, InputOption::VALUE_NONE,                                 'dry run, list and count')

            ->setHelp(
                "example: <info>bin/maintenance clean:log_docs --dry-run --action download --action mail --older_than '10 month'</info>\n"
                . "\<ACTION> is some of <info>'download','mail','ftp','delete'</info> do not use action=delete with another action in the same time\n"
                . "\<OLDER_THAN> can be absolute or relative from now, e.g.:\n"
                . "- <info>2022-01-01</info>\n"
                . "- <info>10 days</info>\n"
                . "- <info>2 weeks</info>\n"
                . "- <info>6 months</info>\n"
                . "- <info>1 year</info>\n"
            );
    }

    public function doExecute(InputInterface $input, OutputInterface $output)
    {
        $dry = false;
        // an array
        $action = $input->getOption('action');

        if (empty($action)) {
            $output->writeln("<error>set '--action' option</error>");

            return 1;
        } elseif (!empty(array_diff($action, self::AVAILABLE_ACTIONS))) {
            $output->writeln("<error>invalid value from '--action' option</error> (see possible value with --help)");

            return 1;
        } elseif (count($action) > 1 && in_array('delete', $action)) {
            $output->writeln("<error>Not use delete with another action ! </error>");

            return 1;
        }

        $older_than = str_replace(['-', '/', ' '], '-', $input->getOption('older_than'));
        if($older_than === "") {
            $output->writeln("<error>set '--older_than' option</error>");

            return 1;
        }

        $clauseWhere = '`action` IN ("' . implode('", "', $action) . '")';
        $matches = [];
        preg_match("/(\d{4}-\d{2}-\d{2})|(\d+)-(day|week|month|year)s?/i", $older_than, $matches);
        $n = count($matches);
        if ($n === 2) {
            // yyyy-mm-dd
            $clauseWhere .= " AND `date` < '" . $matches[1] ."'";
        } elseif ($n === 4 && empty($matches[1])) {
            // 1-day ; 2-weeks ; ...
            $expr = (int)$matches[2];
            $unit = strtoupper($matches[3]);
            $clauseWhere .= sprintf(" AND `date` < DATE_SUB(NOW(), INTERVAL %d %s)", $expr, $unit);
        } else {
            $output->writeln("<error>invalid value form '--older_than' option</error>");

            return 1;
        }

        if ($input->getOption('dry-run')) {
            $dry = true;
        }

        $databoxId = $input->getOption('databox_id');

        $foundDatabox = false;
        foreach ($this->container->getDataboxes() as $databox) {
            if (empty($databoxId) || (!empty($databoxId) && $databox->get_sbas_id() == $databoxId)) {
                $foundDatabox = true;

                if ($dry) {
                    // for delete action, delete all event for the records
                    if (in_array('delete', $action)) {
                        $sqlRecordIds =  'SELECT record_id FROM log_docs WHERE ' . $clauseWhere;
                        $sqlActionDelete = "SELECT id, log_id, `date`, record_id, final, `action` FROM log_docs WHERE record_id IN (" . $sqlRecordIds. ") ORDER BY record_id, id LIMIT 1000";

                        $sqlCount = "SELECT COUNT(`id`) AS n FROM log_docs WHERE record_id IN (" . $sqlRecordIds. ")";

                        $stmt = $databox->get_connection()->prepare($sqlActionDelete);
                        $stmt->execute();
                        // result is limited to 1000 rows to avoid memory leak
                        $displayedRows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                        $stmt->closeCursor();
                    } else {
                        $sql = 'SELECT id, log_id, `date`, record_id, final, `action` FROM log_docs WHERE ' . $clauseWhere . ' LIMIT 1000';

                        $sqlCount = 'SELECT COUNT(`id`) AS n FROM log_docs WHERE ' . $clauseWhere;

                        $stmt = $databox->get_connection()->prepare($sql);
                        $stmt->execute();
                        // result is limited to 1000 rows to avoid memory leak
                        $displayedRows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                        $stmt->closeCursor();
                    }

                    $stmt = $databox->get_connection()->prepare($sqlCount);
                    $stmt->execute();
                    $count = $stmt->fetchColumn(0);
                    $stmt->closeCursor();


                    $output->writeln(sprintf("\n \n dry-run , %d log docs entry to delete for databox %s", $count, $databox->get_dbname()));

                    // displayed only the 1000 first row to avoid memory leak
                    if ($count> 1000) {
                        array_push($displayedRows, array_fill_keys(['id', 'log_id', 'date', 'record_id', 'final', 'action'], ' ... '));
                        array_push($displayedRows, array_fill_keys(['id', 'log_id', 'date', 'record_id', 'final', 'action'], ' ... '));
                    }

                    $logEntryTable = $this->getHelperSet()->get('table');
                    $headers = ['id', 'log_id', 'date', 'record_id', 'final', 'action'];
                    $logEntryTable
                        ->setHeaders($headers)
                        ->setRows($displayedRows)
                        ->render($output);

                } else {
                    if (in_array('delete', $action)) {
                        $sqlRecordIds =  'SELECT record_id FROM log_docs WHERE ' . $clauseWhere;
                        $sqlDeleteAction = 'DELETE FROM log_docs WHERE record_id IN(' . $sqlRecordIds . ') LIMIT 1000';
                    } else {
                        $sqlDeleteAction = 'DELETE FROM log_docs WHERE ' . $clauseWhere . ' LIMIT 1000';
                    }

                    $cnx = $databox->get_connection();
                    $count = 0;
                    do {
                        $nbDeletedRow = $cnx->exec($sqlDeleteAction);
                        $count += $nbDeletedRow;
                    } while ($nbDeletedRow > 0);

                    $output->writeln(sprintf("%d log docs entry deleted on databox %s", $count, $databox->get_dbname()));
                }
            }
        }

        if (!$foundDatabox) {
            $output->writeln('databox_id not found!');
        }
    }
}
