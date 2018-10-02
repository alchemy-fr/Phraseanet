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

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateCollection extends Command
{

    /**
     * Constructor
     */
    public function __construct($name = null)
    {
        parent::__construct($name);

        $this->setDescription('Creates a collection in Phraseanet')
            ->setHelp('')
            ->addArgument('databox_id', InputArgument::REQUIRED, 'The id of the databox where to create the collection', null)
            ->addArgument('collname', InputArgument::REQUIRED, 'The name of the new collection', null)
            ->addOption('base_id_rights', 'd', InputOption::VALUE_OPTIONAL, 'Duplicate rights from another collection', null);

        return $this;
    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        $databox = $this->container->findDataboxById((int) $input->getArgument('databox_id'));

        $new_collection = \collection::create($this->container, $databox, $this->container->getApplicationBox(), $input->getArgument('collname'));

        if ($new_collection && $input->getOption('base_id_rights')) {

            $query = $this->container['phraseanet.user-query'];
            $total = $query->on_base_ids([$input->getOption('base_id_rights')])->get_total();

            $n = 0;
            while ($n < $total) {
                $results = $query->limit($n, 40)->execute()->get_results();
                foreach ($results as $user) {
                    $this->container->getAclForUser($user)->duplicate_right_from_bas($input->getOption('base_id_rights'), $new_collection->get_base_id());
                }
                $n += 40;
            }
        }

        $app = $this->container;
        $this->container['manipulator.acl']->resetAdminRights($this->container['repo.users']->findAdmins());
    }
}
