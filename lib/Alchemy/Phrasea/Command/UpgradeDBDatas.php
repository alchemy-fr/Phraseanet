<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Command;

use Alchemy\Phrasea\Command\Upgrade\Step31;
use Alchemy\Phrasea\Command\Upgrade\Step35;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use vierbergenlars\SemVer\version;

class UpgradeDBDatas extends Command
{
    protected $upgrades = [];

    /**
     * Constructor
     */
    public function __construct($name = null)
    {
        parent::__construct($name);

        $this
            ->setDescription("Upgrades Phraseanet datas, useful after migrations")
            ->setHelp(<<<EOF
Upgrade Phraseanet datas from older version

Steps are

    - version 3.1 : records UUID
    - version 3.5 : metadatas upgrade
EOF
        );

        $this->addOption('from', 'f', InputOption::VALUE_REQUIRED, 'The version where to start upgrade');
        $this->addOption('at-version', null, InputOption::VALUE_REQUIRED, 'The version step to upgrade');

        return $this;
    }

    protected function generateUpgradesFromOption(InputInterface $input)
    {
        if (null === $input->getOption('from') && null === $input->getOption('at-version')) {
            throw new \Exception('You MUST provide a `from` or `at-version` option');
        }

        if (null !== $input->getOption('from') && null !== $input->getOption('at-version')) {
            throw new \Exception('You CAN NOT provide a `from` AND `at-version` option at the same time');
        }

        $versions = [
            'Upgrade\\Step31' => '3.1',
            'Upgrade\\Step35' => '3.5',
        ];

        if (null !== $input->getOption('from')) {
            foreach ($versions as $classname => $version) {
                if (version::lt($version, $input->getOption('from'))) {
                    continue;
                }

                $classname = __NAMESPACE__ . '\\' . $classname;
                $this->upgrades[] = new $classname($this->container);
            }
        }

        if (null !== $input->getOption('at-version')) {
            if ('3.1' === $input->getOption('at-version')) {
                $this->upgrades[] = new Step31($this->container);
            }
            if ('3.5' === $input->getOption('at-version')) {
                $this->upgrades[] = new Step35($this->container);
            }
        }
    }

    public function setUpgrades(array $upgrades)
    {
        $this->upgrades = [];

        foreach ($upgrades as $upgrade) {
            $this->addUpgrade($upgrade);
        }
    }

    public function addUpgrade(Upgrade\DatasUpgraderInterface $upgrade)
    {
        $this->upgrades[] = $upgrade;
    }

    public function getUpgrades()
    {
        return $this->upgrades;
    }

    /**
     * {@inheritdoc}
     */
    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        $this->generateUpgradesFromOption($input);

        if (! $this->upgrades) {
            throw new \Exception('No upgrade available');
        }

        $time = 30;

        foreach ($this->upgrades as $version) {
            $time += $version->getTimeEstimation();
        }

        $question = sprintf("This process is estimated to %s", $this->getFormattedDuration($time));

        $dialog = $this->getHelperSet()->get('dialog');

        do {
            $continue = strtolower($dialog->ask($output, $question . '<question>Continue ? (Y/n)</question>', 'Y'));
        } while ( ! in_array($continue, ['y', 'n']));

        if (strtolower($continue) !== 'y') {
            $output->writeln('Aborting !');

            return;
        }

        foreach ($this->upgrades as $version) {
            $version->execute($input, $output);
        }

        return;
    }
}
