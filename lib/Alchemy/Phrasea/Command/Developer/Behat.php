<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Command\Developer;

use Alchemy\Phrasea\Command\Command;
use Alchemy\Phrasea\Filesystem\PhraseanetFilesystem as Filesystem;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

// use Symfony\Component\Filesystem\Filesystem;

class Behat extends Command
{
    public function __construct()
    {
        parent::__construct('behat:help');

        $this->setDescription('Prints helps about Phraseanet configuration for behat tests');
    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        $fs = new Filesystem();

        $output->writeln('To run behat test you must first get selenium :');
        $output->writeln('<info>http://selenium.googlecode.com/files/selenium-server-standalone-2.31.0.jar');
        $output->writeln('and run it with the following command "java -jar selenium-server-standalone-2.31.0.jar > /dev/null &"');
        $output->writeln('Then you must run the following command :');

        $relativePath = $fs->makePathRelative(getcwd(), $this->container['root.path'] . '/behat.yml');

        $cmd = sprintf('cp %sbehat.yml.dist %sbehat.yml', $relativePath, $relativePath);

        $output->writeln('<info>'.$cmd.'</info>');
    }
}
