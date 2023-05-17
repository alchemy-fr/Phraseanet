<?php

namespace Alchemy\Phrasea\Command\Report;

use Alchemy\Phrasea\Report\ReportCountAssets;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CountAssetsCommand extends AbstractReportCommand
{
    const TYPES = [
        'added,year',
        'added,year,month',
        'downloaded,year',
        'downloaded,year,month',
        'downloaded,year,month,action',
        'most-downloaded'
    ];

    public function __construct()
    {
        parent::__construct('count:assets');

        $this
            ->setDescription('BETA - Get assets count')
            ->addOption('type', null, InputOption::VALUE_REQUIRED, 'type of count assets report ')

            ->setHelp(
                "eg: bin/report count:assets --databox_id 2 --email 'noreply@mydomaine.com' --dmin '2021-12-01' --dmax '2023-01-01' --type 'added,year,month' \n"
                . "\<TYPE>type of report\n"
                . "- <info>'added,year' </info> number of added assets per year\n"
                . "- <info>'added,year,month' </info> number of added assets per year, month\n"
                . "- <info>'downloaded,year' </info> number of downloaded per year \n"
                . "- <info>'downloaded,year,month' </info> number of downloaded per year, month \n"
                . "- <info>'downloaded,year,month,action' </info> number of downloaded per year, month, action (direct download or by email) \n"
                . "- <info>'most-downloaded' </info> The 10 most downloaded assets \n"
            );
    }

    /**
     * @inheritDoc
     */
    protected function getReport(InputInterface $input, OutputInterface $output)
    {
        $type = $input->getOption('type');

        if (!empty($type) && !in_array($type, self::TYPES)) {
            $output->writeln("<error>wrong '--type' option (--help for available value)</error>");

            return 1;
        }

        return new ReportCountAssets(
            $this->findDbOr404($this->sbasId),
            [
                'dmin'      => $this->dmin,
                'dmax'      => $this->dmax,
                'group'     => $type
            ]
        );
    }
}
