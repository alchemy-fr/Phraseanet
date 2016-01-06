<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Phrasea\Controller\Root;

use Alchemy\Phrasea\Application\Helper\EntityManagerAware;
use Alchemy\Phrasea\Controller\Controller;
use Alchemy\Phrasea\Feed\Aggregate;
use Alchemy\Phrasea\Feed\FeedInterface;
use Alchemy\Phrasea\Feed\Formatter\FeedFormatterInterface;
use Alchemy\Phrasea\Model\Entities\Feed;
use Alchemy\Phrasea\Model\Entities\User;
use Alchemy\Phrasea\Model\Repositories\AggregateTokenRepository;
use Alchemy\Phrasea\Model\Repositories\FeedRepository;
use Alchemy\Phrasea\Model\Repositories\FeedTokenRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RSSFeedController extends Controller
{
    use EntityManagerAware;
    
    public function showPublicFeedAction(Request $request, $id, $format)
    {
        $feed = $this->getFeedRepository()->find($id);

        if (! $feed instanceof Feed) {
            $this->app->abort(404, 'Feed not found');
        }

        if (!$feed->isPublic()) {
            $this->app->abort(403, 'Forbidden');
        }

        return $this->createFormattedFeedResponse($format, $feed, (int) $request->query->get('page'));
    }

    public function showUserFeedAction(Request $request, $id, $format)
    {
        $token = $this->getFeedTokenRepository()->find($id);

        $page = (int)$request->query->get('page');

        return $this->createFormattedFeedResponse($format, $token->getFeed(), $page, $token->getUser());
    }

    public function showAggregatedUserFeedAction(Request $request, $token, $format)
    {
        $token = $this->getAggregateTokenRepository()->findOneBy(["value" => $token]);
        $user = $token->getUser();

        $feeds = $this->getFeedRepository()->getAllForUser($this->getAclForUser($user));

        $aggregate = new Aggregate($this->getEntityManager(), $feeds, $token);

        $page = (int) $request->query->get('page');

        return $this->createFormattedFeedResponse($format, $aggregate, $page, $user);
    }

    public function showCoolirisPublicFeedAction(Request $request)
    {
        $feed = Aggregate::getPublic($this->app);

        $page = (int) $request->query->get('page');

        return $this->createFormattedFeedResponse('cooliris', $feed, $page);
    }

    public function showAggregatedPublicFeedAction(Request $request, $format) {
        $feed = Aggregate::getPublic($this->app);

        $page = (int) $request->query->get('page');

        return $this->createFormattedFeedResponse($format, $feed, $page);
    }

    /**
     * @return FeedRepository
     */
    private function getFeedRepository()
    {
        return $this->app['repo.feeds'];
    }

    /**
     * @param string $format
     * @param FeedInterface $feed
     * @param int $page
     * @return Response
     */
    private function createFormattedFeedResponse($format, $feed, $page, User $user = null, $generator = 'Phraseanet')
    {
        /** @var FeedFormatterInterface $formatter */
        $formatter = $this->app['feed.formatter-strategy']($format);

        return $formatter->createResponse($this->app, $feed, $page < 1 ? 1 : $page, $user, $generator);
    }

    /**
     * @return FeedTokenRepository
     */
    private function getFeedTokenRepository()
    {
        return $this->app["repo.feed-tokens"];
    }

    /**
     * @return AggregateTokenRepository
     */
    private function getAggregateTokenRepository()
    {
        return $this->app['repo.aggregate-tokens'];
    }
}
