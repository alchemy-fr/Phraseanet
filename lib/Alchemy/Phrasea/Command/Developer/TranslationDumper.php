<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Command\Developer;

use Alchemy\Phrasea\Command\Command;
use JMS\TranslationBundle\Logger\OutputLogger;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\Comparison\ChangeSet;
use JMS\TranslationBundle\Translation\ConfigBuilder;
use JMS\TranslationBundle\Translation\Updater;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class TranslationDumper extends Command
{
    public function __construct()
    {
        parent::__construct('translation:dump');
    }

    protected function configure()
    {
        $this->setDescription('Dump translation files');
        $this
            ->addArgument('locales', InputArgument::IS_ARRAY, 'The locales for which to extract messages.')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'When specified, changes are _NOT_ persisted to disk.')
            ->addOption('keep', null, InputOption::VALUE_NONE, 'Define if the updater service should keep the old translation (defaults to false).')
        ;
    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        $builder = new ConfigBuilder();
        $builder
            ->setOutputFormat('xlf')
            ->setTranslationsDir(__DIR__ . '/../../../../../resources/locales')
            ->setScanDirs([
                $this->container['root.path'].'/lib',
                $this->container['root.path'].'/templates',
//                $this->container['root.path'].'/bin',
                $this->container['root.path'].'/www',
//                $this->container['root.path'].'/Phraseanet-production-client/src',
                $this->container['root.path'].'/Phraseanet-production-client/templates',
            ])
            ->setExcludedDirs([
                $this->container['root.path'].'/lib/conf.d',
                $this->container['root.path'].'/www/assets',
                $this->container['root.path'].'/www/custom',
                $this->container['root.path'].'/www/include',
                $this->container['root.path'].'/www/plugins',
                $this->container['root.path'].'/www/thumbnails',
            ])
        ;
        if ($input->hasParameterOption('--keep') || $input->hasParameterOption('--keep=true')) {
            $builder->setKeepOldTranslations(true);
        } else if ($input->hasParameterOption('--keep=false')) {
            $builder->setKeepOldTranslations(false);
        }

        $locales = $input->getArgument('locales');
        if (empty($locales)) {
            $locales = array_keys($this->container->getAvailableLanguages());
        }

        if (empty($locales)) {
            throw new \LogicException('No locales were given, and no locales are configured.');
        }

        foreach ($locales as $locale) {
            $config = $builder->setLocale($locale)->getConfig();

            $output->writeln(sprintf('Extracting Translations for locale <info>%s</info>', $locale));
            $output->writeln(sprintf('Keep old translations: <info>%s</info>', $config->isKeepOldMessages() ? 'Yes' : 'No'));
            $output->writeln(sprintf('Output-Path: <info>%s</info>', $config->getTranslationsDir()));
            $output->writeln(sprintf('Directories: <info>%s</info>', implode(', ', $config->getScanDirs())));
            $output->writeln(sprintf('Excluded Directories: <info>%s</info>', $config->getExcludedDirs() ? implode(', ', $config->getExcludedDirs()) : '# none #'));
            $output->writeln(sprintf('Excluded Names: <info>%s</info>', $config->getExcludedNames() ? implode(', ', $config->getExcludedNames()) : '# none #'));
            $output->writeln(sprintf('Output-Format: <info>%s</info>', $config->getOutputFormat() ? $config->getOutputFormat() : '# whatever is present, if nothing then '.$config->getDefaultOutputFormat().' #'));
            $output->writeln(sprintf('Custom Extractors: <info>%s</info>', $config->getEnabledExtractors() ? implode(', ', array_keys($config->getEnabledExtractors())) : '# none #'));
            $output->writeln('============================================================');

            /** @var Updater $updater */
            $updater = $this->container['translation-extractor.updater'];
            $updater->setLogger($logger = new OutputLogger($output));

            if (!$input->getOption('verbose')) {
                $logger->setLevel(OutputLogger::ALL ^ OutputLogger::DEBUG);
            }

            if ($input->getOption('dry-run')) {
                /** @var ChangeSet $changeSet */
                $changeSet = $updater->getChangeSet($config);

                $output->writeln('Added Messages: '.count($changeSet->getAddedMessages()));
                if ($input->getOption('verbose')){
                    /** @var Message $message */
                    foreach($changeSet->getAddedMessages() as $message){
                        $output->writeln($message->getId(). '-> '.$message->getDesc());
                    }
                }

                if ($config->isKeepOldMessages()) {
                    $output->writeln('Deleted Messages: # none as "Keep Old Translations" is true #');
                } else {
                    $output->writeln('Deleted Messages: '.count($changeSet->getDeletedMessages()));
                    if ($input->getOption('verbose')){
                        foreach($changeSet->getDeletedMessages() as $message){
                            $output->writeln($message->getId(). '-> '.$message->getDesc());
                        }
                    }
                }

                return 0;
            }

            $updater->process($config);
        }

        $output->writeln('done!');


        return 0;
    }
}
