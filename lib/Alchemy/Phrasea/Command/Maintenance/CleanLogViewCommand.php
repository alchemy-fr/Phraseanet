<?php

namespace Alchemy\Phrasea\Command\Maintenance;

use Alchemy\Phrasea\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CleanLogViewCommand extends Command
{
    public function __construct()
    {
        parent::__construct('clean:log_view');

        $this
            ->setDescription('Beta - clean the log_view for all databox (if not specified) or a specific databox_id ')
            ->addOption('databox_id',       null, InputOption::VALUE_REQUIRED,                             'the databox to clean')
            ->addOption('older_than',       null, InputOption::VALUE_REQUIRED,                             'delete older than <OLDER_THAN>')
            ->addOption('dry-run',        null, InputOption::VALUE_NONE,                                 'dry run, list and count')

            ->setHelp(
                "example: <info>bin/maintenance clean:log_view --dry-run --older_than '10 month'</info>\n"
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

        $older_than = str_replace(['-', '/', ' '], '-', $input->getOption('older_than'));
        if($older_than === "") {
            $output->writeln("<error>set '--older_than' option</error>");

            return 1;
        }

        $matches = [];
        preg_match("/(\d{4}-\d{2}-\d{2})|(\d+)-(day|week|month|year)s?/i", $older_than, $matches);
        $n = count($matches);
        if ($n === 2) {
            // yyyy-mm-dd
            $clauseWhere = "`date` < '" . $matches[1] ."'";
        } elseif ($n === 4 && empty($matches[1])) {
            // 1-day ; 2-weeks ; ...
            $expr = (int)$matches[2];
            $unit = strtoupper($matches[3]);
            $clauseWhere = sprintf("`date` < DATE_SUB(NOW(), INTERVAL %d %s)", $expr, $unit);
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
                    $sqlCount = 'SELECT COUNT(`id`) FROM log_view WHERE ' . $clauseWhere;
                    $stmt = $databox->get_connection()->prepare($sqlCount);
                    $stmt->execute();
                    $count = $stmt->fetchColumn(0);

                    $sql = 'SELECT id, `date`, record_id, coll_id FROM log_view WHERE ' . $clauseWhere . ' LIMIT 1000';

                    $stmt->closeCursor();
                    $stmt = $databox->get_connection()->prepare($sql);
                    $stmt->execute();
                    $displayedRows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                    $stmt->closeCursor();

                    $output->writeln(sprintf("\n \n dry-run , %d log view entry to delete for databox %s", $count, $databox->get_dbname()));
                    // displayed only the 1000 first row to avoid memory leak
                    if ($count > 1000) {
                        array_push($displayedRows, array_fill_keys(['id', 'date', 'record_id', 'coll_id'], ' ... '));
                        array_push($displayedRows, array_fill_keys(['id', 'date', 'record_id', 'coll_id'], ' ... '));
                    }
                    $logEntryTable = $this->getHelperSet()->get('table');
                    $headers = ['id', 'date', 'record_id', 'coll_id'];
                    $logEntryTable
                        ->setHeaders($headers)
                        ->setRows($displayedRows)
                        ->render($output);

                } else {
                    $cnx = $databox->get_connection();
                    $count = 0;
                    // group delete by 1000
                    $sqlDelete = 'DELETE FROM log_view WHERE ' . $clauseWhere . ' LIMIT 1000';
                    do {
                        $nbDeletedRow = $cnx->exec($sqlDelete);
                        $count += $nbDeletedRow;
                    } while ($nbDeletedRow > 0);

                    $output->writeln(sprintf("%d log view entry deleted on databox %s", $count, $databox->get_dbname()));
                }
            }
        }

        if (!$foundDatabox) {
            $output->writeln('databox_id not found!');
        }
    }
}
