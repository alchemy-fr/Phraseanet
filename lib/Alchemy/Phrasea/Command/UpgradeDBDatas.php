<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Command;

use Alchemy\Phrasea\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class UpgradeDBDatas extends Command
{
    protected $upgrades = array();

    /**
     * Constructor
     */
    public function __construct($name = null)
    {
        parent::__construct($name);

        $this
            ->setDescription("Upgrade Phraseanet datas")
            ->setHelp(<<<EOF
Upgrade Phraseanet datas from older version

Steps are

    - version 3.1 : records UUID
    - version 3.5 : metadatas upgrade
EOF
        );

        $this->addOption('from', 'f', null, 'The version where to start upgrade');
        $this->addOption('at-version', null, null, 'The version step to upgrade');

        return $this;
    }

    protected function generateUpgradesFromOption(InputInterface $input)
    {
        if (false === $input->getOption('from') && false === $input->getOption('at-version')) {
            throw new \Exception('You MUST provide a `from` or `at-version` option');
        }

        if (false !== $input->getOption('from') && false !== $input->getOption('at-version')) {
            throw new \Exception('You CAN NOT provide a `from` AND `at-version` option at the same time');
        }

        $versions = array(
            'Upgrade\\Step31' => '3.1',
            'Upgrade\\Step35' => '3.5',
        );

        if (null !== $input->getOption('from')) {
            foreach ($versions as $classname => $version) {
                if (version_compare($input->getOption('from'), $version) > 0) {
                    continue;
                }

                $classname = __NAMESPACE__ . '\\' . $classname;
                $this->upgrades[] = new $classname($this->container);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function requireSetup()
    {
        return true;
    }

    public function setUpgrades(array $upgrades)
    {
        $this->upgrades = array();

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

        if ( ! $this->upgrades) {
            throw new \Exception('No upgrade available');
        }

        $time = 0;

        foreach ($this->upgrades as $version) {
            $time += $version->getTimeEstimation();
        }

        $question = sprintf("This process is estimated to %s", $this->getFormattedDuration($time));

        $dialog = $this->getHelperSet()->get('dialog');

        do {
            $continue = strtolower($dialog->ask($output, $question . '<question>Continue ? (Y/n)</question>', 'Y'));
        } while ( ! in_array($continue, array('y', 'n')));

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
