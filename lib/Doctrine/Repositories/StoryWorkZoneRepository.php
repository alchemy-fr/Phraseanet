<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Repositories;

use Doctrine\ORM\EntityRepository;

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class StoryWorkzoneRepository extends EntityRepository
{
  
  /**
   * Returns all StoryWorkZone currently attached to a user
   *
   * @param \User_Adapter $user
   * @return \Doctrine\Common\Collections\ArrayCollection 
   */
  public function findByUser(\User_Adapter $user)
  {

    return $this->findBy(array('usr_id'=>$user->get_id()));
  }
  
}

