<?php

namespace Alchemy\Phrasea\Command\Maintenance;

use Alchemy\Phrasea\Command\Command;
use Alchemy\Phrasea\Model\Entities\LazaretFile;
use Alchemy\Phrasea\Model\Repositories\LazaretFileRepository;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class LazaretFilesSetSizeCommand extends Command
{
    public function __construct()
    {
        parent::__construct('lazaret:set_sizes');

        $this
            ->setDescription('Set the null size in the LazaretFiles table')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'dry run, count')

            ->setHelp('');
    }

    public function doExecute(InputInterface $input, OutputInterface $output)
    {
        /** @var LazaretFileRepository $lazaretRepository */
        $lazaretRepository = $this->container['repo.lazaret-files'];

        $lazaretNullSizes = $lazaretRepository->findBy(['size' => null]);

        $path = $this->container['tmp.lazaret.path'];
        /** @var EntityManager $em */
        $em = $this->container['orm.em'];

        if (!$input->getOption('dry-run')) {
            /** @var LazaretFile $lazaretNullSize */
            foreach ($lazaretNullSizes as $lazaretNullSize) {
                $lazaretFileName = $path .'/'.$lazaretNullSize->getFilename();
                $media = $this->container->getMediaFromUri($lazaretFileName);

                $lazaretNullSize->setSize($media->getFile()->getSize());
                $em->persist($lazaretNullSize);
            }

            $em->flush();

            $output->writeln(sprintf("%d LazaretFiles done!", count($lazaretNullSizes)));
        } else {
            $output->writeln(sprintf("%d LazaretFiles to update!", count($lazaretNullSizes)));
        }
    }
}
