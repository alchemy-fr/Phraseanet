<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Alchemy\Phrasea\Setup\Requirements\BinariesRequirements;
use Alchemy\Phrasea\Setup\Requirements\FilesystemRequirements;
use Alchemy\Phrasea\Setup\Requirements\LocalesRequirements;
use Alchemy\Phrasea\Setup\Requirements\PhraseaRequirements;
use Alchemy\Phrasea\Setup\Requirements\PhpRequirements;
use Alchemy\Phrasea\Setup\Requirements\SystemRequirements;
use Alchemy\Phrasea\Command\Setup\CheckEnvironment;

class CheckConfig extends CheckEnvironment
{
    const CHECK_OK = 0;
    const CHECK_WARNING = 1;
    const CHECK_ERROR = 2;

    public function __construct($name = null)
    {
        parent::__construct($name);

        $this->setDescription("Checks environment");

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        $ret = parent::doExecute($input, $output);

        foreach ($this->container['phraseanet.appbox']->get_databoxes() as $databox) {
            $output->writeln("\nDatabox <info>".$databox->get_viewname()."</info> fields configuration\n");
            foreach ($databox->get_meta_structure() as $field) {
                if ($field->get_original_source() !== $field->get_tag()->getTagname()) {
                    $status = ' <comment>WARNING</comment>  ';
                    $info = sprintf(" (Described as '<comment>%s</comment>', this source does not seem to exist)", $field->get_original_source());
                } else {
                    $status = ' <info>OK</info>       ';
                    $info = '';
                }
                $output->writeln($status.$databox->get_viewname() . "::".$field->get_name().$info);
            }
            $output->writeln("\n");
        }

        $output->writeln("\nCache configuration\n");

        $cache = str_replace('Alchemy\\Phrasea\\Cache\\', '', get_class($this->container['cache']));
        $opCodeCache = str_replace('Alchemy\\Phrasea\\Cache\\', '', get_class($this->container['opcode-cache']));

        if ('ArrayCache' === $cache) {
            $output->writeln(' <comment>WARNING</comment>  Current cache configuration uses <comment>ArrayCache</comment> (Or cache server is unreachable). Please check your cache configuration to use a cache server.');
        } else {
            $output->writeln(' <info>OK</info>       Current cache configuration uses <info>'. $cache .'</info>');
        }

        if ('ArrayCache' === $opCodeCache) {
            $output->writeln(' <comment>WARNING</comment>  Current opcode cache configuration uses <comment>ArrayCache</comment>. Please check your cache configuration to use an opcode cache.');
        } else {
            $output->writeln(' <info>OK</info>       Current opcode cache configuration uses <info>'. $opCodeCache .'</info>');
        }

        return $ret;
    }
}
