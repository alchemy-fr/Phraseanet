<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Command\Developer;

use Alchemy\Phrasea\Command\Command;
use Doctrine\Common\Annotations\DocParser;
use JMS\TranslationBundle\Translation\ConfigBuilder;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TranslationDumper extends Command
{
    public function __construct()
    {
        parent::__construct('translation:dump');

        $this->setDescription('Dump translation files');
    }

    /**
     * {@inheritdoc}
     */
    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        foreach (array_keys($this->container->getAvailableLanguages()) as $code) {
            $builder = new ConfigBuilder();
            $config = $builder->setLocale($code)
                ->setOutputFormat('xliff')
                ->setTranslationsDir(__DIR__ . '/../../../../../resources/locales')
                ->setScanDirs(array(
                    $this->container['root.path'].'/templates/web/admin/user',
                    $this->container['root.path'].'/lib',
                    $this->container['root.path'].'/templates',
                    $this->container['root.path'].'/bin',
                    $this->container['root.path'].'/www',
                ))
                ->getConfig();

            $this->container['translation-extractor.updater']->process($config);
        }

        return 0;
    }
}
