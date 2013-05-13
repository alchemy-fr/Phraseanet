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
use Alchemy\Phrasea\Setup\Requirements\BinariesRequirements;
use Alchemy\Phrasea\Setup\Requirements\FilesystemRequirements;
use Alchemy\Phrasea\Setup\Requirements\LocalesRequirements;
use Alchemy\Phrasea\Setup\Requirements\PhraseaRequirements;
use Alchemy\Phrasea\Setup\Requirements\PhpRequirements;
use Alchemy\Phrasea\Setup\Requirements\SystemRequirements;

class CheckEnvironment extends Command
{
    public function __construct($name = null)
    {
        parent::__construct($name);

        $this->setDescription("Check environment");

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        foreach(array(
                new BinariesRequirements(),
                new FilesystemRequirements(),
                new LocalesRequirements(),
                new PhraseaRequirements(),
                new PhpRequirements(),
                new SystemRequirements(),
            ) as $collection) {

            $output->writeln('');
            $output->writeln($collection->getName() . ' requirements : ');
            $output->writeln('');

            foreach ($collection->getRequirements() as $requirement) {
                $result = $requirement->isFulfilled() ? '<info>OK       </info>' : ($requirement->isOptional() ? '<comment>WARNING</comment>  ' : '<error>ERROR</error>    ');
                $output->write(' ' . $result);

                $output->writeln($requirement->getTestMessage());

                if (!$requirement->isFulfilled()) {
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
                    $output->writeln("          " . $requirement->getHelpText());
                    $output->writeln('');
                }
            }
        }

        return;
    }
}
