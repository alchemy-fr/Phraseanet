<?php

namespace Alchemy\Phrasea\ControllerProvider;

use Alchemy\EmbedProvider\EmbedServiceProvider;
use Alchemy\Phrasea\PhraseanetService\Provider\PSAdminServiceProvider;
use Alchemy\Phrasea\PhraseanetService\Provider\PSExposeServiceProvider;
use Silex\Application;
use Silex\ServiceProviderInterface;

class ControllerProviderServiceProvider implements ServiceProviderInterface
{

    private $controllerProviders = [];

    /**
     * Registers services on the given app.
     *
     * This method should only be used to configure services and parameters.
     * It should not get services.
     */
    public function register(Application $app)
    {
        $this->loadProviders();

        foreach ($this->controllerProviders as $class => $values) {
            $app->register(new $class, $values);
        }
    }

    /**
     * Bootstraps the application.
     *
     * This method is called after all services are registered
     * and should be used for "dynamic" configuration (whenever
     * a service must be requested).
     */
    public function boot(Application $app)
    {
        // Nothing to do here
    }

    public function loadProviders()
    {
        $this->controllerProviders = [
            Admin\Collection::class => [],
            Admin\ConnectedUsers::class => [],
            Admin\Dashboard::class => [],
            Admin\Databox::class => [],
            Admin\Databoxes::class => [],
            Admin\Feeds::class => [],
            Admin\Fields::class => [],
            Admin\Plugins::class => [],
            Admin\Root::class => [],
            Admin\SearchEngine::class => [],
            Admin\Setup::class => [],
            Admin\Subdefs::class => [],
            Admin\TaskManager::class => [],
            \Alchemy\Phrasea\WorkerManager\Provider\ControllerServiceProvider::class => [],
            PSAdminServiceProvider::class => [],
            PSExposeServiceProvider::class => [],
            Admin\Users::class => [],
            Client\Root::class => [],
            Datafiles::class => [],
            Lightbox::class => [],
            MediaAccessor::class => [],
            Minifier::class => [],
            Permalink::class => [],
            Prod\BasketProvider::class => [],
            Prod\Bridge::class => [],
            Prod\DoDownload::class => [],
            Prod\Download::class => [],
            Prod\Edit::class => [],
            Prod\Export::class => [],
            Prod\Feed::class => [],
            Prod\Language::class => [],
            Prod\Lazaret::class => [],
            Prod\MoveCollection::class => [],
            Prod\Order::class => [],
            Prod\Printer::class => [],
            Prod\Property::class => [],
            Prod\Push::class => [],
            Prod\Query::class => [],
            Prod\Record::class => [],
            \Alchemy\Phrasea\Report\ControllerProvider\ProdReportControllerProvider::class => [],
            Prod\Root::class => [],
            Prod\Share::class => [],
            Prod\Story::class => [],
            Prod\Subdefs::class => [],
            Prod\Thesaurus::class => [],
            Prod\Tools::class => [],
            Prod\Tooltip::class => [],
            Prod\TOU::class => [],
            Prod\Upload::class => [],
            Prod\UsrLists::class => [],
            Prod\WorkZone::class => [],
            Report\Root::class => [],
            Root\Account::class => [],
            Root\Developers::class => [],
            Root\Login::class => [],
            Root\Root::class => [],
            Root\RSSFeeds::class => [],
            Root\Session::class => [],
            Setup::class => [],
            Thesaurus\Thesaurus::class => [],
            Thesaurus\Xmlhttp::class => [],
            User\Notifications::class => [],
            User\Preferences::class => [],
            EmbedServiceProvider::class => [],
        ];
    }
}
