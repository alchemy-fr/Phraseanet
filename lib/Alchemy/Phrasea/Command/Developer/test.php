<?php
// *******************************************************************
// ********************** TO BE DELETED AFTER TESTS ******************
// *******************************************************************

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Command\Developer;

use Alchemy\Phrasea\Command\Command;
use Alchemy\Phrasea\Model\Repositories\PsSettings\Expose;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class test extends Command
{
    public function __construct()
    {
        parent::__construct('test');
    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {

        $d0 = new \DateTime("2021/05/12 08:00:00");
        $d1 = new \DateTime("now");
        $u = $d1->getTimestamp() - $d0->getTimestamp();  // small unique

//        $one_parent = new PsSettings();
//        $one_parent->setRole("P_".$u);
//
//        $one_child = new PsSettings();
//        $one_child->setRole("C_".$u);
//        $one_child->setParent($one_parent);
//
//        $one_parent->getChildren()->add($one_child);
//
//        /** @var EntityManager $em */
//        $em = $this->container['orm.em'];
//        $em->persist($one_parent);
//        $em->flush();
//        return 1;


        /** @var Expose $ex */
        $ex = $this->container['ps_settings.expose'];

        foreach($ex->getInstances(1) as $exposeInstance) {
            $output->writeln(sprintf("expose: '%s'", $exposeInstance->getName()));
            $output->writeln(sprintf("  front-uri: '%s'", $exposeInstance->getFrontUri()));

            $a = $exposeInstance->asArray();
        }
        $output->writeln('');

        $z = $ex->create("expose-".$u);
        $z->setFrontUri("bad uri will be fixed");
        $z->setFrontUri("https://expose.new_expose.phrasea.io");
        $output->writeln(sprintf("  front-uri: '%s'", $z->getFrontUri()));

        $z->canSee(666, true);  // will create a "ACE"

        // $z->canSee(666, false);     // will delete the "ACE" and keys

        $a = $z->asArray();
        $a['value_string'] .= "#2";

        $z2 = $ex->fromArray($a);

        $output->writeln('');
        foreach($ex->getInstances() as $exposeInstance) {
            $output->writeln(sprintf("expose: '%s'", $exposeInstance->getName()));
            $output->writeln(sprintf("  front-uri: '%s'", $exposeInstance->getFrontUri()));
        }

        return 0;
    }
}
