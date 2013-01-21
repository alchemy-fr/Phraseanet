<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 * @package     KonsoleKomander
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Alchemy\Phrasea\Command\Command;

class module_console_fieldsList extends Command
{

    public function __construct($name = null)
    {
        parent::__construct($name);

        $this->setDescription('List all databox fields');

        return $this;
    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        foreach ($this->getService('phraseanet.appbox')->get_databoxes() as $databox) {
            /* @var $databox \databox */
            $output->writeln(
                sprintf(
                    "\n ---------------- \nOn databox %s (sbas_id %d) :\n"
                    , $databox->get_viewname()
                    , $databox->get_sbas_id()
                )
            );

            foreach ($databox->get_meta_structure()->get_elements() as $field) {
                $output->writeln(
                    sprintf(
                        "  %2d - <info>%s</info> (%s) %s"
                        , $field->get_id()
                        , $field->get_name()
                        , $field->get_type()
                        , ($field->is_multi() ? '<comment>multi</comment>' : '')
                    )
                );
            }
        }

        return 0;
    }
}
