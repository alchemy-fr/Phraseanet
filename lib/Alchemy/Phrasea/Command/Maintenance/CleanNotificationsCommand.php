<?php

/**
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Command\Maintenance;

use Alchemy\Phrasea\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CleanNotificationsCommand extends Command
{
    public function __construct()
    {
        parent::__construct('clean:notifications');

        $this
            ->setDescription('Delete old user notifications')
            ->addOption('what',       null, InputOption::VALUE_REQUIRED|InputOption::VALUE_IS_ARRAY, '"read" and/or "unread" (default both)')
            ->addOption('older_than', null, InputOption::VALUE_REQUIRED,                             'delete older than \<OLDER_THAN>')
            ->addOption('dry',        null, InputOption::VALUE_NONE,                                 'dry run, count but don\'t delete')
            ->addOption('show_sql',   null, InputOption::VALUE_NONE,                                 'show sql pre-selecting records')

            ->setHelp(
                "\<OLDER_THAN> can be absolute or relative from now, e.g.:\n"
                . "- <info>2022-01-01</info> (please use strict date format, do not add time)\n"
                . "- <info>10 days</info>\n"
                . "- <info>2 weeks</info>\n"
                . "- <info>6 months</info>\n"
                . "- <info>1 year</info>"
            );
    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        $clauses = [];  // sql clauses
        $dry = false;

        // sanity check options
        //

        // --what

        $what = $input->getOption('what');
        if(empty($what)) {
            $what = ['read', 'unread'];
        }
        switch (join('-', $what)) {
            case 'read' :
                $clauses[] = "`unread` = 0";
                break;
            case 'unread' :
                $clauses[] = "`unread` = 1";
                break;
            case 'read-unread' :
            case 'unread-read' :
                // no clause
                break;
            default:
                $output->writeln("<error>invalid value form '--what' option</error>");
                return 1;
        }

        // --older_than

        $older_than = str_replace(['-', '/', ' '], '-', $input->getOption('older_than'));
        if($older_than === "") {
            $output->writeln("<error>set '--older_than' option</error>");
            return 1;
        }
        $matches = [];
        preg_match("/(\d{4}-\d{2}-\d{2})|(\d+)-(day|week|month|year)s?/i", $older_than, $matches);
        $n = count($matches);
        if($n === 2) {
            // yyyy-mm-dd
            $clauses[] = "`created_on` < " . $matches[1];
        }
        elseif($n === 4 && empty($matches[1])) {
            // 1-day ; 2-weeks ; ...
            $expr = (int)$matches[2];
            $unit = strtoupper($matches[3]);
            $clauses[] = sprintf("`created_on` < DATE_SUB(NOW(), INTERVAL %d %s)", $expr, $unit);
        }
        else {
            $output->writeln("<error>invalid value form '--older_than' option</error>");
            return 1;
        }

        // --dry

        if($input->getOption('dry')) {
            $dry = true;
        }


        // do the job
        //
        $sql_0      = "FROM `notifications` WHERE (" . join(") AND (", $clauses) . ")";
        $sql_count  = "SELECT COUNT(`id`) AS n " . $sql_0;
        $sql_delete = "DELETE " . $sql_0;

        if($input->getOption('show_sql')) {
            $output->writeln(sprintf("sql: \"<info>%s</info>\"", $sql_delete));
        }

        $stmt = $this->container->getApplicationBox()->get_connection()->prepare($sql_count);
        $stmt->execute();
        $n = $stmt->fetchColumn(0);
        $stmt->closeCursor();

        $output->writeln(sprintf("%d notifications will be deleted.", $n));

        if(!$dry) {
            $n = $this->container->getApplicationBox()->get_connection()->exec($sql_delete);
            $output->writeln(sprintf("%d notifications have been be deleted.", $n));
        }
        else {
            $output->writeln("Dry mode: Not executed.");
        }

        return 0;
    }

}
