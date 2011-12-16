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
class BasketRepository extends EntityRepository
{

  /**
   * Returns all basket for a given user that are not marked as archived
   *
   * @param \User_Adapter $user
   * @return \Doctrine\Common\Collections\ArrayCollection 
   */
  public function findActiveByUser(\User_Adapter $user)
  {
    $dql = 'SELECT b FROM Entities\Basket b 
            WHERE b.usr_id = :usr_id AND b.archived = false';
    
    $query = $this->_em->createQuery($dql);
    $query->setParameters(array('usr_id' => $user->get_id()));

    return $query->getResult();
  }
  
  
  /**
   * Returns all baskets that are in validation session not expired  and 
   * where a specified user is participant (not owner)
   *
   * @param \User_Adapter $user
   * @return \Doctrine\Common\Collections\ArrayCollection 
   */
  public function findActiveValidationByUser(\User_Adapter $user)
  {
    $dql = 'SELECT b FROM Entities\Basket b 
              JOIN b.validation s 
              JOIN s.participants p  
            WHERE b.usr_id != ?1 AND p.usr_id = ?2 
                  AND s.expires > CURRENT_TIMESTAMP()';
    
    $query = $this->_em->createQuery($dql);
    $query->setParameters(array(1 => $user->get_id(), 2 => $user->get_id()));

    return $query->getResult();
  }

}
