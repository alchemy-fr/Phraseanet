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
            ->addOption('dry', null, InputOption::VALUE_NONE, 'dry run, count')

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

        if (!$input->getOption('dry')) {
            /** @var LazaretFile $lazaretNullSize */
            foreach ($lazaretNullSizes as $lazaretNullSize) {
                try {
                    $lazaretFileName = $path .'/'.$lazaretNullSize->getFilename();
                    $media = $this->container->getMediaFromUri($lazaretFileName);
                    $size = $media->getFile()->getSize();
                } catch (\Exception $e) {
                    $size = 0;
                }

                $lazaretNullSize->setSize($size);
                $em->persist($lazaretNullSize);
            }

            $em->flush();

            $output->writeln(sprintf("%d LazaretFiles done!", count($lazaretNullSizes)));
        } else {
            $output->writeln(sprintf("%d LazaretFiles to update!", count($lazaretNullSizes)));
        }
    }
}
