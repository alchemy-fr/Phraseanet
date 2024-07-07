<?php

namespace Alchemy\Phrasea\Command\Report;

use Alchemy\Phrasea\Report\ReportConnections;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ConnectionsCommand extends AbstractReportCommand
{
    const TYPES = ['user', 'nav', 'nav,version', 'os', 'os,nav', 'os,nav,version', 'res'];

    public function __construct()
    {
        parent::__construct('connections:all');

        $this
            ->setDescription('BETA - Get all connections report')
            ->addOption('type', null, InputOption::VALUE_REQUIRED, 'type of report connections, if not defined or empty it is for all connections')

            ->setHelp(
                "eg: bin/report connections:all --databox_id 2 --email 'noreply@mydomaine.com' --dmin '2022-12-01' --dmax '2023-01-01' --type 'os,nav' \n"
                . "\<TYPE>type of report\n"
                . "- <info>'' or not defined </info>all connections\n"
                . "- <info>'user' </info> connections by user\n"
                . "- <info>'nav' </info> connections by browser\n"
                . "- <info>'nav,version' </info> connections by browser, version \n"
                . "- <info>'os' </info> connections by OS \n"
                . "- <info>'os,nav' </info> connections by OS, browser \n"
                . "- <info>'os,nav,version' </info> connections by OS, Browser, Version\n"
                . "- <info>'res' </info> connections by Screen Res \n"
            );
    }

    public function getReport(InputInterface $input, OutputInterface $output)
    {
        $type = $input->getOption('type');

        if (!empty($type) && !in_array($type, self::TYPES)) {
            $output->writeln("<error>wrong '--type' option (--help for available value)</error>");

            return 1;
        }

        return
            (new ReportConnections(
                $this->findDbOr404($this->sbasId),
                [
                    'dmin'      => $this->dmin,
                    'dmax'      => $this->dmax,
                    'group'     => $type,
                    'anonymize' => $this->container['conf']->get(['registry', 'modules', 'anonymous-report'])
                ]
        ))
            ->setAppKey($this->container['conf']->get(['main', 'key']));
    }
}
