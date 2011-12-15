<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\RequestHandler;

use Alchemy\Phrasea\Kernel;

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class WorkZone extends RequestHandlerAbstract
{
  
  const BASKETS = 'baskets';
  const STORIES = 'stories';
  const VALIDATIONS = 'validations';
  
  public function getContent()
  {
    $em = $this->kernel->getEntityManager();
    $current_user = $this->kernel->getAuthenticatedUser();

    /* @var $repo_baskets \Repositories\BasketRepository */
    $repo_baskets = $em->getRepository('Entities\Baskets');

    /* @var $repo_stories \Repositories\StoryWorkzoneRepository */
    $repo_stories = $em->getRepository('Entities\StoryWorkZone');

    $ret = new \Doctrine\Common\Collections\ArrayCollection();
    
    $baskets = $repo_baskets->findActiveByUser($current_user);
    $validations = $repo_baskets->findActiveValidationByUser($current_user);

    $ret->set(self::BASKETS, $baskets);
    $ret->set(self::VALIDATIONS, $validations);
    $ret->set(self::STORIES, $repo_stories->findByUser($current_user));
    
    return $ret;
  }

}

