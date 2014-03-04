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

use Alchemy\Phrasea\Application as PhraseaApplication;
use Silex\Application;
use Silex\ServiceProviderInterface;

class RepositoriesServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['repo.users'] = $app->share(function (PhraseaApplication $app) {
            return $app['EM']->getRepository('Phraseanet:User');
        });
        $app['repo.auth-failures'] = $app->share(function (PhraseaApplication $app) {
            return $app['EM']->getRepository('Phraseanet:AuthFailure');
        });
        $app['repo.sessions'] = $app->share(function (PhraseaApplication $app) {
            return $app['EM']->getRepository('Phraseanet:Session');
        });
        $app['repo.tasks'] = $app->share(function (PhraseaApplication $app) {
            return $app['EM']->getRepository('Phraseanet:Task');
        });
        $app['repo.registrations'] = $app->share(function (PhraseaApplication $app) {
            return $app['EM']->getRepository('Phraseanet:Registration');
        });
        $app['repo.baskets'] = $app->share(function (PhraseaApplication $app) {
            return $app['EM']->getRepository('Phraseanet:Basket');
        });
        $app['repo.basket-elements'] = $app->share(function (PhraseaApplication $app) {
            return $app['EM']->getRepository('Phraseanet:BasketElement');
        });
        $app['repo.validation-participants'] = $app->share(function (PhraseaApplication $app) {
            return $app['EM']->getRepository('Phraseanet:ValidationParticipant');
        });
        $app['repo.story-wz'] = $app->share(function (PhraseaApplication $app) {
            return $app['EM']->getRepository('Phraseanet:StoryWZ');
        });
        $app['repo.orders'] = $app->share(function (PhraseaApplication $app) {
            return $app['EM']->getRepository('Phraseanet:Order');
        });
        $app['repo.order-elements'] = $app->share(function (PhraseaApplication $app) {
            return $app['EM']->getRepository('Phraseanet:OrderElement');
        });
        $app['repo.feeds'] = $app->share(function (PhraseaApplication $app) {
            return $app['EM']->getRepository('Phraseanet:Feed');
        });
        $app['repo.feed-entries'] = $app->share(function (PhraseaApplication $app) {
            return $app['EM']->getRepository('Phraseanet:FeedEntry');
        });
        $app['repo.feed-items'] = $app->share(function (PhraseaApplication $app) {
            return $app['EM']->getRepository('Phraseanet:FeedItem');
        });
        $app['repo.feed-publishers'] = $app->share(function (PhraseaApplication $app) {
            return $app['EM']->getRepository('Phraseanet:FeedPublisher');
        });
        $app['repo.feed-tokens'] = $app->share(function (PhraseaApplication $app) {
            return $app['EM']->getRepository('Phraseanet:FeedToken');
        });
        $app['repo.aggregate-tokens'] = $app->share(function (PhraseaApplication $app) {
            return $app['EM']->getRepository('Phraseanet:AggregateToken');
        });
        $app['repo.usr-lists'] = $app->share(function (PhraseaApplication $app) {
            return $app['EM']->getRepository('Phraseanet:UsrList');
        });
        $app['repo.usr-list-owners'] = $app->share(function (PhraseaApplication $app) {
            return $app['EM']->getRepository('Phraseanet:UsrListOwner');
        });
        $app['repo.usr-list-entries'] = $app->share(function (PhraseaApplication $app) {
            return $app['EM']->getRepository('Phraseanet:UsrListEntry');
        });
        $app['repo.lazaret-files'] = $app->share(function (PhraseaApplication $app) {
            return $app['EM']->getRepository('Phraseanet:LazaretFile');
        });
        $app['repo.usr-auth-providers'] = $app->share(function (PhraseaApplication $app) {
            return $app['EM']->getRepository('Phraseanet:UsrAuthProvider');
        });
        $app['repo.ftp-exports'] = $app->share(function (PhraseaApplication $app) {
            return $app['EM']->getRepository('Phraseanet:FtpExport');
        });
        $app['repo.user-queries'] = $app->share(function (PhraseaApplication $app) {
            return $app['EM']->getRepository('Phraseanet:UserQuery');
        });
        $app['repo.tokens'] = $app->share(function ($app) {
            return $app['EM']->getRepository('Phraseanet:Token');
        });
        $app['repo.presets'] = $app->share(function (PhraseaApplication $app) {
            return $app['EM']->getRepository('Phraseanet:Preset');
        });
    }

    public function boot(Application $app)
    {
    }
}
