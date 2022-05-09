<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Helper;

use Alchemy\Phrasea\Model\Entities\Basket as BasketEntity;
use Alchemy\Phrasea\Model\Repositories\BasketRepository;
use Alchemy\Phrasea\Model\Repositories\StoryWZRepository;
use Doctrine\Common\Collections\ArrayCollection;

class WorkZone extends Helper
{
    const BASKETS     = 'baskets';
    const STORIES     = 'stories';
    const VALIDATIONS = 'validations';

    /**
     * Returns an ArrayCollection containing three keys :
     *    - self::BASKETS : an ArrayCollection of the actives baskets (Non Archived)
     *    - self::STORIES : an ArrayCollection of working stories
     *    - self::VALIDATIONS : the validation people are waiting from me
     *
     * @param  null|string $sort "date"|"name"
     * @param  null|int $onlyId  return infos only for this basket
     * @return ArrayCollection
     */
    public function getContent($sort = null, $onlyId = null)
    {
        /* @var $repo_baskets BasketRepository */
        $repo_baskets = $this->app['repo.baskets'];

        $sort = in_array($sort, ['date', 'name']) ? $sort : 'name';

        $ret = new ArrayCollection();

        $allClasses = [
            "push-block",
            "push_rec",
            "feedback-block",
            "vote_sent",
            "share-block",
            "share_sent",
            "basket-block",
            "basket",
            "vote_rec",
            "share_rec",
            "basket_wip"
        ];

        /**
         * first block : my baskets
         * @see templates/web/prod/WorkZone/Macros.html.twig "list baskets"
         */
        $baskets = $repo_baskets->findActiveByUser($this->app->getAuthenticatedUser(), $sort);

        // force creation of a default basket
        if (count($baskets) === 0) {
            $basket = new BasketEntity();

            $basket->setName($this->app->trans('Default basket'));
            $basket->setUser($this->app->getAuthenticatedUser());

            $this->app['orm.em']->persist($basket);
            $this->app['orm.em']->flush();
            $baskets = [$basket];
        }
        $_baskets = []; // new array, filtered
        foreach ($baskets as $k=>$basket) {
            if(!is_null($onlyId) && $basket->getId() != $onlyId) {
                continue;
            }

            // ! do not invert formula because isAwareByUserParticipant(...) can return null
            $isRead = !$basket->isRead() == false || ($basket->isAwareByUserParticipant($this->app->getAuthenticatedUser()) === false);
            $classes = [];

            // I own this basket
            if($basket->getPusher()) {
                // i own this basket, but it was pushed to me
                $classes[] = "push-block";
                $classes[] = is_null($basket->getWip()) ? "push_rec" : "basket_wip";
            }
            $nparticipants = $basket->getParticipants()->count();
            if($nparticipants > 0) {
                // i own this basket and there are participants (maybe me)
                if($basket->isVoteBasket()) {
                    // i am the owner / sender(?) of this "vote" (=feedback) basket
                    $classes[] = "feedback-block";
                    $classes[] = is_null($basket->getWip()) ? "vote_sent" : "basket_wip";
                }
                else {
                    // simple share
                    $classes[] = "share-block";
                    $classes[] = is_null($basket->getWip()) ? "share_sent" : "basket_wip";
                }
            }
            if(empty($classes)) {
                // simple basket
                $classes[] = "basket-block";
                $classes[] = is_null($basket->getWip()) ? "basket" : "basket_wip";
            }

            $classes = array_unique($classes);

            $_baskets[$k] = [
                'object' => $basket,
                'data' => [
                    'isRead' => $isRead,
                    'classes' => $classes,
                    'removeClasses' => array_diff($allClasses, $classes)
                ]
            ];
        }

        /**
         * second block : baskets i'm only participant
         * @see templates/web/prod/WorkZone/Macros.html.twig "list feedbacks (validations) AND SHARES"
         */
        $validations = $repo_baskets->findActiveValidationByUser($this->app->getAuthenticatedUser(), $sort);
        $_validations = []; // new array, filtered
        foreach ($validations as $k => $basket) {
            if(!is_null($onlyId) && $basket->getId() != $onlyId) {
                continue;
            }

            // ! do not invert formula because isAwareByUserParticipant(...) can return null
            $isRead = !$basket->isRead() == false || ($basket->isAwareByUserParticipant($this->app->getAuthenticatedUser()) === false);
            $classes = [];

            if($basket->isVoteBasket()) {
                $classes[] = "feedback-block";
                $classes[] = is_null($basket->getWip()) ? "vote_rec" : "basket_wip";
            }
            else {
                $classes[] = "share-block";
                $classes[] = is_null($basket->getWip()) ? "share_rec" : "basket_wip";
            }

            $classes = array_unique($classes);

            $_validations[$k] = [
                'object' => $basket,
                'data' => [
                    'isRead' => $isRead,
                    'classes' => $classes,
                    'removeClasses' => array_diff($allClasses, $classes)
                ]
            ];
        }

        /**
         * third block : stories
         * @var $repo_stories StoryWZRepository
         */
        $repo_stories = $this->app['repo.story-wz'];
        $stories = $repo_stories->findByUser($this->app, $this->app->getAuthenticatedUser(), $sort);
        $_stories = []; // new array, filtered
        // be consistant
        foreach ($stories as $k => $story) {
            if(!is_null($onlyId) && $story->getId() != $onlyId) {
                continue;
            }

            $_stories[$k] = [
                'object' => $story,
                'data' => [
                    // no special data for stories now
                    'classes' => [],
                    'removeClasses' => []
                ]
            ];
        }


        $ret->set(self::BASKETS, $_baskets);
        $ret->set(self::VALIDATIONS, $_validations);
        $ret->set(self::STORIES, $_stories);

        return $ret;
    }

    protected function sortBaskets(array $baskets)
    {
    }
}
