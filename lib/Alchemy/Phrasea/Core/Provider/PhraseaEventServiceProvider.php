<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Provider;

use Alchemy\Phrasea\Core\Event\Subscriber\CollectionSubscriber;
use Alchemy\Phrasea\Core\Event\Subscriber\ContentNegotiationSubscriber;
use Alchemy\Phrasea\Core\Event\Subscriber\CookiesDisablerSubscriber;
use Alchemy\Phrasea\Core\Event\Subscriber\DataboxSubscriber;
use Alchemy\Phrasea\Core\Event\Subscriber\ElasticSearchSubscriber;
use Alchemy\Phrasea\Core\Event\Subscriber\LogoutSubscriber;
use Alchemy\Phrasea\Core\Event\Subscriber\MaintenanceSubscriber;
use Alchemy\Phrasea\Core\Event\Subscriber\PhraseaLocaleSubscriber;
use Alchemy\Phrasea\Core\Event\Subscriber\RecordSubscriber;
use Alchemy\Phrasea\Core\Event\Subscriber\StorySubscriber;
use Alchemy\Phrasea\Core\Event\Subscriber\SessionManagerSubscriber;
use Silex\Application;
use Silex\ServiceProviderInterface;

class PhraseaEventServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['phraseanet.logout-subscriber'] = $app->share(function (Application $app) {
            return new LogoutSubscriber();
        });
        $app['phraseanet.locale-subscriber'] = $app->share(function (Application $app) {
            return new PhraseaLocaleSubscriber($app);
        });
        $app['phraseanet.maintenance-subscriber'] = $app->share(function (Application $app) {
            return new MaintenanceSubscriber($app);
        });
        $app['phraseanet.cookie-disabler-subscriber'] = $app->share(function (Application $app) {
            return new CookiesDisablerSubscriber($app);
        });
        $app['phraseanet.session-manager-subscriber'] = $app->share(function (Application $app) {
            return new SessionManagerSubscriber($app);
        });
        $app['phraseanet.content-negotiation-subscriber'] = $app->share(function (Application $app) {
            return new ContentNegotiationSubscriber($app);
        });
        $app['phraseanet.record-subscriber'] = $app->share(function (Application $app) {
            return new RecordSubscriber($app['elasticsearch.indexer.record_indexer']);
        });
        $app['phraseanet.story-subscriber'] = $app->share(function (Application $app) {
            return new StorySubscriber($app['elasticsearch.indexer.record_indexer']);
        });
        $app['phraseanet.elasticsearch-subscriber'] = $app->share(function (Application $app) {
            return new ElasticSearchSubscriber(
                $app['elasticsearch.indexer.record_indexer'],
                $app['elasticsearch.indexer.term_indexer'],
                $app['elasticsearch.client'],
                $app['phraseanet.appbox'],
                $app['elasticsearch.options']['index']
            );
        });
        $app['phraseanet.databox-subscriber'] = $app->share(function (Application $app) {
            return new DataboxSubscriber();
        });
        $app['phraseanet.collection-subscriber'] = $app->share(function (Application $app) {
            return new CollectionSubscriber();
        });
    }

    public function boot(Application $app)
    {
    }
}
