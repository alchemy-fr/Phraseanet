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
class LessCompile extends Command
{
    public function __construct($name = null)
    {
        parent::__construct($name);

        $this->setDescription('Compile less files');
    }

    /**
     * {@inheritdoc}
     */
    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        $files = array(
            __DIR__ . '/../../../../../www/skins/build/login.css' => realpath(__DIR__ . '/../../../../../www/skins/login/less/login.less'),
            __DIR__ . '/../../../../../www/skins/build/account.css' => realpath(__DIR__ . '/../../../../../www/skins/account/account.less'),
        );

        $output->writeln('Building Assets...');

        $failures = 0;
        $errors = array();
        foreach ($files as $buildFile => $lessFile) {
            $output->writeln(sprintf('Building %s', basename($lessFile)));
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

        if (0 === $failures) {
            $output->writeln('<info>Build done !</info>');
            return 0;
        }

        $output->writeln(sprintf('<error>%d errors occured during the build %s</error>', $failures, implode(', ', $errors)));

        return 1;
    }
}
