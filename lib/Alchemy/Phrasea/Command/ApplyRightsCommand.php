<?php

/**
 * This file is part of Phraseanet
 *
 * (c) 2005-2020 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Alchemy\Phrasea\Model\Entities\User;

class ApplyRightsCommand extends Command
{
	public function __construct($name = null)
    {
        parent::__construct($name);

        $this->setDescription('Apply right on databox, inject appbox:basusr to dboxes:collusr')
            ->addOption('user_id', null, InputOption::VALUE_REQUIRED, 'the user ID to apply rights')
            ;

        return $this;
    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
    	$userId = $input->getOption('user_id');
    	$userRepository = $this->container['repo.users'];

    	if ($userId) {
    		if (($user = $userRepository->find($userId)) === null) {
    			$output->writeln('user not found!');

    			return 0;
    		}

           	$this->injectRightsSbas($user);
    	} else {
    		foreach ($userRepository->findAll() as $user) {
	            	$this->injectRightsSbas($user);
	        }
    	}

    	$output->writeln('Apply right on databox finished!');

    	return 0;
    }

    private function injectRightsSbas(User $user)
    {
    	$userAcl = $this->container->getAclForUser($user);

    	foreach ($userAcl->get_granted_sbas() as $databox) {

	    	$userAcl->delete_injected_rights_sbas($databox);

	        $sql = "INSERT INTO collusr
	              (site, usr_id, coll_id, mask_and, mask_xor, ord)
	              VALUES (:site_id, :usr_id, :coll_id, :mask_and, :mask_xor, :ord)";
	        $stmt = $databox->get_connection()->prepare($sql);
	        $iord = 0;

	        //  fix collusr if user has right on collection
	        foreach ($userAcl->get_granted_base([], [$databox->get_sbas_id()]) as $collection) {
	            try {
	                $stmt->execute([
	                    ':site_id'  => $this->container['conf']->get(['main', 'key']),
	                    ':usr_id'   => $user->getId(),
	                    ':coll_id'  => $collection->get_coll_id(),
	                    ':mask_and' => $userAcl->get_mask_and($collection->get_base_id()),
	                    ':mask_xor' => $userAcl->get_mask_xor($collection->get_base_id()),
	                    ':ord'      => $iord++
	                ]);
	            } catch (DBALException $e) {

	            }
	        }

	        $stmt->closeCursor();
    	}
    }
}
