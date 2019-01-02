<?php
namespace App\Command\Setup;

use Symfony\Component\Console\Command\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\ArrayInput;

use App\Core\Version;

class About extends Command
{
    protected function configure()
    {
        $this
            ->setName('setup:about')
            ->setDescription('Installs Phraseanet.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $version = new Version();

        $out_txt = "
  _____  _    _ _____            _____ ______          _   _ ______ _______
 |  __ \| |  | |  __ \    /\    / ____|  ____|   /\   | \ | |  ____|__   __|
 | |__) | |__| | |__) |  /  \  | (___ | |__     /  \  |  \| | |__     | |
 |  ___/|  __  |  _  /  / /\ \  \___ \|  __|   / /\ \ | . ` |  __|    | |
 | |    | |  | | | \ \ / ____ \ ____) | |____ / ____ \| |\  | |____   | |
 |_|    |_|  |_|_|  \_|_/    \_\_____/|______/_/    \_\_| \_|______|  |_|
                          __
               ________  / /___  ______
              / ___/ _ \/ __/ / / / __ \
             (__  )  __/ /_/ /_/ / /_/ /
            /____/\___/\__/\__,_/ .___/
                              /_/

 Phraseanet Copyright (C) 2004 Alchemy
 This program comes with ABSOLUTELY NO WARRANTY.
 This is free software, and you are welcome to redistribute it
 under certain conditions; type `about:license' for details.\n\n"
            . $version->getName() . ' ' . $version->getNumber();

        $output->writeln($out_txt);

        $output->writeln('');

        $command = $this->getApplication()->find('about:authors');
        $command->run(new ArrayInput(array('command' => 'about:authors')), $output);

        $output->writeln('');

        $command = $this->getApplication()->find('about:license');
        $command->run(new ArrayInput(array('command' => 'about:license')), $output);
    }
}