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

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractCheckCommand extends Command
{
    const CHECK_OK = 0;
    const CHECK_WARNING = 1;
    const CHECK_ERROR = 2;

    /**
     * {@inheritdoc}
     */
    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        $ret = static::CHECK_OK;

        foreach ($this->provideRequirements() as $collection) {

            $output->writeln('');
            $output->writeln($collection->getName() . ' requirements : ');
            $output->writeln('');

            foreach ($collection->getRequirements() as $requirement) {
                $result = $requirement->isFulfilled() ? '<info>OK       </info>' : ($requirement->isOptional() ? '<comment>WARNING</comment>  ' : '<error>ERROR</error>    ');
                $output->write(' ' . $result);

                $output->writeln($requirement->getTestMessage());

                if (!$requirement->isFulfilled()) {
                    $ret = static::CHECK_ERROR;
                    $output->writeln("          " . $requirement->getHelpText());
                    $output->writeln('');
                }
            }

            $output->writeln('');
            $output->writeln($collection->getName() . ' recommendations : ');
            $output->writeln('');

            foreach ($collection->getRecommendations() as $requirement) {
                $result = $requirement->isFulfilled() ? '<info>OK       </info>' : ($requirement->isOptional() ? '<comment>WARNING</comment>  ' : '<error>ERROR</error>    ');
                $output->write(' ' . $result);

                $output->writeln($requirement->getTestMessage());

                if (!$requirement->isFulfilled()) {
                    if ($ret === static::CHECK_OK) {
                        $ret = static::CHECK_WARNING;
                    }
                    $output->writeln("          " . $requirement->getHelpText());
                    $output->writeln('');
                }
            }
        }

        return $ret;
    }

    /**
     * @return array An array of RequirementsCollection
     */
    abstract protected function provideRequirements();
}
