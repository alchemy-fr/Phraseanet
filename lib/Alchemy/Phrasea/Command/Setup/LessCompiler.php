<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Command\Setup;

use Alchemy\Phrasea\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\ProcessBuilder;

/**
 * This command builds less file
 */
class LessCompiler extends Command
{
    public function __construct()
    {
        parent::__construct('assets:compile-less');

        $this->setDescription('Compile less files');
    }

    /**
     * {@inheritdoc}
     */
    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        $files = array(
            $this->container['root.path'] . '/www/skins/login/less/login.less' => $this->container['root.path'] . '/www/skins/build/login.css',
            $this->container['root.path'] . '/www/skins/account/account.less' => $this->container['root.path'] . '/www/skins/build/account.css',
            $this->container['root.path'] . '/www/assets/bootstrap/less/bootstrap.less' => $this->container['root.path'] . '/www/skins/build/bootstrap/css/bootstrap.css',
            $this->container['root.path'] . '/www/assets/bootstrap/less/responsive.less' => $this->container['root.path'] . '/www/skins/build/bootstrap/css/bootstrap-responsive.css',
        );

        $output->writeln('Building Assets...');

        $failures = 0;
        $errors = array();
        foreach ($files as $lessFile => $buildFile) {
            $this->container['filesystem']->mkdir(dirname($buildFile));
            $output->writeln(sprintf('Building %s', basename($lessFile)));

            if (!is_file($lessFile)) {
                throw new \Exception(realpath($lessFile) . ' does not exists');
            }

            if (!is_writable(dirname($buildFile))) {
                throw new \Exception(realpath(dirname($buildFile)) . ' is not writable');
            }


            $builder = ProcessBuilder::create(array(
                'recess',
                '--compile',
                $lessFile,
            ));
            $process = $builder->getProcess();
            $process->run();

            if (!$process->isSuccessful()) {
                $failures++;
                $errors[] = $process->getErrorOutput();
            }
            file_put_contents($buildFile, $process->getOutput());
        }

        $copies = array(
            $this->container['root.path'] . '/www/assets/bootstrap/img/glyphicons-halflings-white.png' => $this->container['root.path'] . '/www/skins/build/bootstrap/img/glyphicons-halflings-white.png',
            $this->container['root.path'] . '/www/assets/bootstrap/img/glyphicons-halflings.png' => $this->container['root.path'] . '/www/skins/build/bootstrap/img/glyphicons-halflings.png',
        );

        foreach ($copies as $source => $target) {
            $this->container['filesystem']->mkdir(dirname($target));
            $this->container['filesystem']->copy($source, $target);
        }

        if (0 === $failures) {
            $output->writeln('<info>Build done !</info>');

            return 0;
        }

        $output->writeln(sprintf('<error>%d errors occured during the build %s</error>', $failures, implode(', ', $errors)));

        return 1;
    }
}
