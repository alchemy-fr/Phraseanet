<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Helper;

/**
 *
 * WorkZone provides methods for working with the working zone of Phraseanet
 * Production. This zones handles Non-Archived baskets, stories and Validation
 * people are waiting from me.
 *
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
    public function getContent($sort)
    {
        /* @var $repo_baskets \Doctrine\Repositories\BasketRepository */
        $repo_baskets = $this->app['EM']->getRepository('Entities\Basket');

        $sort = in_array($sort, array('date', 'name')) ? $sort : 'name';

        $ret = new \Doctrine\Common\Collections\ArrayCollection();

        $baskets = $repo_baskets->findActiveByUser($this->app['phraseanet.user'], $sort);
        $validations = $repo_baskets->findActiveValidationByUser($this->app['phraseanet.user'], $sort);

        /* @var $repo_stories \Doctrine\Repositories\StoryWZRepository */
        $repo_stories = $this->app['EM']->getRepository('Entities\StoryWZ');

        $stories = $repo_stories->findByUser($this->app, $this->app['phraseanet.user'], $sort);

        $ret->set(self::BASKETS, $baskets);
        $ret->set(self::VALIDATIONS, $validations);
        $ret->set(self::STORIES, $stories);

        return $ret;
    }

    protected function sortBaskets(array $baskets)
    {
        $tmp_baskets = array();
    }
}

