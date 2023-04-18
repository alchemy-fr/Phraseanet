<?php

namespace Alchemy\Phrasea\Command\Report;

use Alchemy\Phrasea\Report\ReportUsers;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CountUsersCommand extends AbstractReportCommand
{
    const TYPES = [
        'added,year',
        'added,year,month',
       ];

    public function __construct()
    {
        parent::__construct('count:users');

        $this
            ->setDescription('BETA - Get users count')
            ->addOption('type', null, InputOption::VALUE_REQUIRED, 'type of users count report')

            ->setHelp(
                "eg: bin/report count:users --databox_id 2 --email 'noreply@mydomaine.com' --dmin '2022-01-01' --dmax '2023-01-01' --type 'added,year,month' \n"
                . "\<TYPE>type users count report\n"
                . "- <info>'added,year'</info> number of newly added user per year\n"
                . "- <info>'added,year,month' </info> number of newly added user per year, month\n"
            );
    }

    /**
     * @inheritDoc
     */
    protected function getReport(InputInterface $input, OutputInterface $output)
    {
        $type = $input->getOption('type');
        $this->isAppboxConnection = true;

        if (!empty($type) && !in_array($type, self::TYPES)) {
            $output->writeln("<error>wrong '--type' option (--help for available value)</error>");

            return 1;
        }

        // get just one databox registered to initialize the base class Report
        $databoxes = $this->container->getDataboxes();

        if (count($databoxes) > 0) {
            $databox = current($databoxes);
        } else {
            throw new NotFoundHttpException("NO databox set on this application");
        }

        return new ReportUsers(
            $databox,
            [
                'dmin'      => $this->dmin,
                'dmax'      => $this->dmax,
                'group'     => $type
            ]
        );
    }
}
