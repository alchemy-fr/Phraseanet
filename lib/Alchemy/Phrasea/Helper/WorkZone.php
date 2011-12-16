<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Helper;

use Alchemy\Phrasea\Kernel;

/**
 * 
 * WorkZone provides methods for working with the working zone of Phraseanet
 * Production. This zones handles Non-Archived baskets, stories and Validation
 * people are waiting from me.
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class WorkZone extends Helper
{
  
  const BASKETS = 'baskets';
  const STORIES = 'stories';
  const VALIDATIONS = 'validations';
  
  /**
   * 
   * Returns an ArrayCollection containing three keys :
   *    - self::BASKETS : an ArrayCollection of the actives baskets 
   *     (Non Archived)
   *    - self::STORIES : an ArrayCollection of working stories
   *    - self::VALIDATIONS : the validation people are waiting from me
   *
   * @return \Doctrine\Common\Collections\ArrayCollection 
   */
  public function getContent()
  {
    $em = $this->getKernel()->getEntityManager();
    $current_user = $this->getKernel()->getAuthenticatedUser();

    /* @var $repo_baskets \Doctrine\Repositories\BasketRepository */
    $repo_baskets = $em->getRepository('Entities\Basket');


    $ret = new \Doctrine\Common\Collections\ArrayCollection();
    
    $baskets = $repo_baskets->findActiveByUser($current_user);
    $validations = $repo_baskets->findActiveValidationByUser($current_user);

    /* @var $repo_stories \Doctrine\Repositories\StoryWZRepository */
    $repo_stories = $em->getRepository('Entities\StoryWZ');
    
    $ret->set(self::BASKETS, $baskets);
    $ret->set(self::VALIDATIONS, $validations);
    $ret->set(self::STORIES, $repo_stories->findByUser($current_user));
    
    return $ret;
  }

}

