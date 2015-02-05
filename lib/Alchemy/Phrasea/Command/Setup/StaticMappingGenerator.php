<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Command\Setup;

use Alchemy\Phrasea\Command\Command;
use Alchemy\Phrasea\Http\StaticFile\StaticFileFactory;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class StaticMappingGenerator extends Command
{
    public function __construct($name = null)
    {
        parent::__construct('static-file:generate-mapping');

        $this->addOption('write', 'w', null, 'Writes the configuration')
             ->addOption('enabled', 'e', null, 'Set the enable toggle to `true`')
             ->addArgument('type', InputArgument::REQUIRED, 'The configuration type, either `nginx` or `apache`')
             ->setDescription('Generates Phraseanet Static file configuration');
    }

    /**
     * {@inheritdoc}
     */
    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        $enabled = $input->getOption('enabled');
        $type = strtolower($input->getArgument('type'));

        $factory = new StaticFileFactory($this->container['monolog'], true, $type, $this->container['phraseanet.thumb-symlinker']);
        $mode = $factory->getMode(true);

        $conf = array(
            'enabled' => $enabled,
            'type' => $type,
            'mapping' => $mode->getMapping(),
        );

        if ($input->getOption('write')) {
            $output->write("Writing configuration ...");
            $this->container['phraseanet.configuration']['static-file'] = $conf;
            $output->writeln(" <info>OK</info>");
            $output->writeln("");
            $output->write("It is now strongly recommended to use <info>static-file:dump-configuration</info> command to upgrade your virtual-host");
        } else {
            $output->writeln("Configuration will <info>not</info> be written, use <info>--write</info> option to write it");
            $output->writeln("");
            $output->writeln(Yaml::dump(array('static-file' => $conf), 4));
        }

        return 0;
    }
}
