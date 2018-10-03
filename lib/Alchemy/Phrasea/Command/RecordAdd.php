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

use Alchemy\Phrasea\Border\File;
use Alchemy\Phrasea\Border\Manager;
use Alchemy\Phrasea\Model\Entities\LazaretFile;
use Alchemy\Phrasea\Model\Entities\LazaretSession;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RecordAdd extends Command
{
    /**
     * Constructor
     */
    public function __construct($name = null)
    {
        parent::__construct($name);

        $this->setDescription('Adds a record to Phraseanet')
            ->setHelp('')
            ->addArgument('base_id', InputArgument::REQUIRED, 'The target collection id', null)
            ->addArgument('file', InputArgument::REQUIRED, 'The file to archive', null)
            ->addOption('force', 'f', InputOption::VALUE_OPTIONAL, 'Force a behavior (record|quarantine)', 'record')
            ->addOption('yes', 'y', InputOption::VALUE_NONE, 'Answer yes to all questions')
            ->addOption('in-place', 'i', InputOption::VALUE_NONE, 'Set this flag to archive record in place. When record is added, it is copied to a temporary folder and file has some metadatas written. If you choose to archive in place, please be warned that the file will be updated (UUID will be written in it)');

        return $this;
    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        try {
            $collection = \collection::getByBaseId($this->container, $input->getArgument('base_id'));
        } catch (\Exception $e) {
            throw new \InvalidArgumentException(sprintf('Collection %s is invalid', $input->getArgument('base_id')));
        }

        $file = $input->getArgument('file');

        if (false === $this->container['filesystem']->exists($file)) {
            throw new \InvalidArgumentException(sprintf('File %s does not exists', $file));
        }

        $media = $this->container['mediavorus']->guess($file);

        $dialog = $this->getHelperSet()->get('dialog');

        if (!$input->getOption('yes')) {
            do {
                $continue = strtolower($dialog->ask($output, sprintf("Will add record <info>%s</info> (%s) on collection <info>%s</info>\n<question>Continue ? (y/N)</question>", $file, $media->getType(), $collection->get_label($this->container['locale'])), 'N'));
            } while (!in_array($continue, ['y', 'n']));

            if (strtolower($continue) !== 'y') {
                $output->writeln('Aborted !');

                return;
            }
        }

        $tempfile = $originalName = null;

        if ($input->getOption('in-place') !== '1') {
            $originalName = pathinfo($file, PATHINFO_BASENAME);
            $tempfile = $this->container['temporary-filesystem']->createTemporaryFile('add_record', null, pathinfo($file, PATHINFO_EXTENSION));
            $this->container['monolog']->addInfo(sprintf('copy file from `%s` to temporary `%s`', $file, $tempfile));
            $this->container['filesystem']->copy($file, $tempfile, true);
            $file = $tempfile;
            $media = $this->container['mediavorus']->guess($file);
        }

        $file = new File($this->container, $media, $collection, $originalName);
        $session = new LazaretSession();
        $this->container['orm.em']->persist($session);

        $forceBehavior = null;

        if ($input->getOption('force')) {
            switch ($input->getOption('force')) {
                default:
                    $this->container['temporary-filesystem']->clean('add_record');
                    throw new \InvalidArgumentException(sprintf('`%s` is not a valid force option', $input->getOption('force')));
                    break;
                case 'record':
                    $forceBehavior = Manager::FORCE_RECORD;
                    break;
                case 'quarantine':
                    $forceBehavior = Manager::FORCE_LAZARET;
                    break;
            }
        }

        $elementCreated = null;
        $callback = function ($element, $visa, $code) use (&$elementCreated) {
            $elementCreated = $element;
        };

        $this->container['border-manager']->process($session, $file, $callback, $forceBehavior);

        if ($elementCreated instanceof \record_adapter) {
            $output->writeln(
                sprintf(
                    "Record id <info>%d</info> on collection `%s` (databox `%s`) has been created",
                    $elementCreated->getRecordId(),
                    $elementCreated->getCollection()->get_label($this->container['locale']),
                    $elementCreated->getDatabox()->get_label($this->container['locale'])
                )
            );
        } elseif ($elementCreated instanceof LazaretFile) {
            $output->writeln(
                sprintf("Quarantine item id <info>%d</info> has been created", $elementCreated->getId())
            );
        }

        if ($tempfile) {
            $this->container['monolog']->addInfo(sprintf('Remove temporary file `%s`', $tempfile));
            $this->container['temporary-filesystem']->clean('add_record');
        }

        return;
    }
}
