<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Helper;

use Doctrine\Common\Collections\ArrayCollection;
use Alchemy\Phrasea\Model\Entities\Basket as BasketEntity;

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
        /* @var $repo_baskets Alchemy\Phrasea\Model\Repositories\BasketRepository */
        $repo_baskets = $this->app['EM']->getRepository('Phraseanet:Basket');

        $sort = in_array($sort, ['date', 'name']) ? $sort : 'name';

        $ret = new ArrayCollection();

        $baskets = $repo_baskets->findActiveByUser($this->app['authentication']->getUser(), $sort);

        // force creation of a default basket
        if (0 === count($baskets)) {
            $basket = new BasketEntity();

            $basket->setName($this->app->trans('Default basket'));
            $basket->setUser($this->app['authentication']->getUser());

            $this->app['EM']->persist($basket);
            $this->app['EM']->flush();
            $baskets = [$basket];
        }

        $validations = $repo_baskets->findActiveValidationByUser($this->app['authentication']->getUser(), $sort);

        /* @var $repo_stories Alchemy\Phrasea\Model\Repositories\StoryWZRepository */
        $repo_stories = $this->app['EM']->getRepository('Phraseanet:StoryWZ');

        $stories = $repo_stories->findByUser($this->app, $this->app['authentication']->getUser(), $sort);

        $ret->set(self::BASKETS, $baskets);
        $ret->set(self::VALIDATIONS, $validations);
        $ret->set(self::STORIES, $stories);

        return $ret;
    }

    protected function sortBaskets(array $baskets)
    {
    }
}
