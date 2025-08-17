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
        // get a small unique int to generate new unique instance (unique name)
        $d0 = new \DateTime("2021/07/01 08:00:00");
        $d1 = new \DateTime("now");
        $u = $d1->getTimestamp() - $d0->getTimestamp();  // small unique



        // the Expose object embraces all instances of "expose" settings)
        //
        /** @var Expose $ex */
        $ex = $this->container['ps_settings.expose'];



        // list all the instances that the user 666 can see
        //
        $output->writeln(sprintf("\n\n\n=========== listing ALL \"expose\" application(s) ========================"));
        foreach($ex->getInstances() as $exposeInstance) {
            $output->writeln(sprintf("expose: '%s'", $exposeInstance->getName()));
            $output->writeln(sprintf("  front-uri: '%s'", $exposeInstance->getFrontUri()));

            // $a = $exposeInstance->asArray();
        }
        $output->writeln('');



        // create a new "expose" app
        //
        $name = "expose-".$u;   // $u = small random int.
        $output->writeln(sprintf("\n\n\n=========== creating \"%s\" application ========================", $name));

        $z = $ex->create($name);
        // test that a property can be created, and changed
        $z->setFrontUri("bad uri will be fixed");
        $z->setFrontUri("https://expose".$u.".phrasea.io");
        $output->writeln(sprintf("  front-uri: '%s'", $z->getFrontUri()));

        // user 1 can see
        $z->canSee(1, true);  // will create a "ACE"
        // $z->canSee(666, false);     // will delete the "ACE" and keys



        // get this expose as array (for ex. to save it as yaml or xml)
        //
        $output->writeln(sprintf("\n\n\n=========== dump as array ========================"));
        $a = $z->asArray();
        var_dump($a);

        // patch by changing the name (stored into "valueString" on the first level row, role="EXPOSE")
        $a['valueString'] .= "(copy)";

        $output->writeln(sprintf("\n\n\n=========== duplicated as (copy) ========================"));
        // and save it as a new expose
        $z2 = $ex->fromArray($a);


        //

        $output->writeln(sprintf("\n\n\n=========== list what 1 can see  ========================"));
        foreach($ex->getInstances(1) as $exposeInstance) {
            $output->writeln(sprintf("expose: '%s'", $exposeInstance->getName()));
            $output->writeln(sprintf("  front-uri: '%s'", $exposeInstance->getFrontUri()));
        }

        $output->writeln(sprintf("\n\n\n=========== list what 666 can see (nothing) ========================"));
        foreach($ex->getInstances(666) as $exposeInstance) {
            $output->writeln(sprintf("expose: '%s'", $exposeInstance->getName()));
            $output->writeln(sprintf("  front-uri: '%s'", $exposeInstance->getFrontUri()));
        }

        return 0;
    }
}
