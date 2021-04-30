<?php

namespace Alchemy\Phrasea\Application;

use Alchemy\EmbedProvider\EmbedServiceProvider;
use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\ControllerProvider as Providers;
use Alchemy\Phrasea\PhraseanetService\Provider\PSAdminServiceProvider;
use Alchemy\Phrasea\PhraseanetService\Provider\PSExposeServiceProvider;
use Alchemy\Phrasea\Report\ControllerProvider\ProdReportControllerProvider;
use Alchemy\Phrasea\WorkerManager\Provider\ControllerServiceProvider as WorkerManagerProvider;
use Assert\Assertion;
use Silex\ControllerProviderInterface;


class RouteLoader
{

    public static $defaultProviders = [
        '/account/'                    => Providers\Root\Account::class,
        '/admin/'                      => Providers\Admin\Root::class,
        '/admin/collection'            => Providers\Admin\Collection::class,
        '/admin/connected-users'       => Providers\Admin\ConnectedUsers::class,
        '/admin/dashboard'             => Providers\Admin\Dashboard::class,
        '/admin/databox'               => Providers\Admin\Databox::class,
        '/admin/databoxes'             => Providers\Admin\Databoxes::class,
        '/admin/fields'                => Providers\Admin\Fields::class,
        '/admin/publications'          => Providers\Admin\Feeds::class,
        '/admin/plugins'               => Providers\Admin\Plugins::class,
        '/admin/search-engine'         => Providers\Admin\SearchEngine::class,
        '/admin/setup'                 => Providers\Admin\Setup::class,
        '/admin/subdefs'               => Providers\Admin\Subdefs::class,
        '/admin/task-manager'          => Providers\Admin\TaskManager::class,
        '/admin/worker-manager'        => WorkerManagerProvider::class,
        '/admin/phraseanet-service'    => PSAdminServiceProvider::class,
        '/admin/users'                 => Providers\Admin\Users::class,
        '/client/'                     => Providers\Client\Root::class,
        '/datafiles'                   => Providers\Datafiles::class,
        '/developers/'                 => Providers\Root\Developers::class,
        '/download/'                   => Providers\Prod\DoDownload::class,
        '/embed/'                      => EmbedServiceProvider::class,
        '/feeds/'                      => Providers\Root\RSSFeeds::class,
        '/include/minify'              => Providers\Minifier::class,
        '/login/'                      => Providers\Root\Login::class,
        '/lightbox'                    => Providers\Lightbox::class,
        '/permalink'                   => Providers\Permalink::class,
        '/prod/baskets'                => Providers\Prod\BasketProvider::class,
        '/prod/bridge/'                => Providers\Prod\Bridge::class,
        '/prod/download'               => Providers\Prod\Download::class,
        '/prod/export/'                => Providers\Prod\Export::class,
        '/prod/expose/'                => PSExposeServiceProvider::class,
        '/prod/feeds'                  => Providers\Prod\Feed::class,
        '/prod/language'               => Providers\Prod\Language::class,
        '/prod/lazaret/'               => Providers\Prod\Lazaret::class,
        '/prod/lists'                  => Providers\Prod\UsrLists::class,
        '/prod/order/'                 => Providers\Prod\Order::class,
        '/prod/printer/'               => Providers\Prod\Printer::class,
        '/prod/push/'                  => Providers\Prod\Push::class,
        '/prod/query/'                 => Providers\Prod\Query::class,
        '/prod/records/'               => Providers\Prod\Record::class,
        '/prod/records/edit'           => Providers\Prod\Edit::class,
        '/prod/records/movecollection' => Providers\Prod\MoveCollection::class,
        '/prod/records/property'       => Providers\Prod\Property::class,
        '/prod/report/'                => ProdReportControllerProvider::class,
        '/prod/share/'                 => Providers\Prod\Share::class,
        '/prod/story'                  => Providers\Prod\Story::class,
        '/prod/subdefs'                => Providers\Prod\Subdefs::class,
        '/prod/thesaurus/'             => Providers\Prod\Thesaurus::class,
        '/prod/tools/'                 => Providers\Prod\Tools::class,
        '/prod/tooltip'                => Providers\Prod\Tooltip::class,
        '/prod/TOU/'                   => Providers\Prod\TOU::class,
        '/prod/upload/'                => Providers\Prod\Upload::class,
        '/prod/WorkZone'               => Providers\Prod\WorkZone::class,
        '/prod/'                       => Providers\Prod\Root::class,
        '/report/'                     => Providers\Report\Root::class,
        '/session/'                    => Providers\Root\Session::class,
        '/setup'                       => Providers\Setup::class,
        '/thesaurus'                   => Providers\Thesaurus\Thesaurus::class,
        '/user/notifications/'         => Providers\User\Notifications::class,
        '/user/preferences/'           => Providers\User\Preferences::class,
        '/xmlhttp'                     => Providers\Thesaurus\Xmlhttp::class,
        '/'                            => Providers\Root\Root::class,
    ];

    /**
     * @var string[]
     */
    private $controllerProviders = [];

    /**
     * @param string $prefix
     * @param string $providerClass
     * @throws \InvalidArgumentException
     */
    public function registerProvider($prefix, $providerClass)
    {
        Assertion::classExists($providerClass);

        $this->controllerProviders[$prefix] = $providerClass;
    }

    public function registerProviders(array $providers)
    {
        foreach ($providers as $prefix => $providerClass) {
            $this->registerProvider($prefix, $providerClass);
        }
    }

    /**
     * @param Application $app
     */
    public function bindRoutes(Application $app)
    {
        // @todo Move me out of here !
        // Controllers with routes referenced by api
        $this->controllerProviders[$app['controller.media_accessor.route_prefix']] = Providers\MediaAccessor::class;

        foreach ($this->controllerProviders as $prefix => $class) {
            $app->mount($prefix, new $class);
        }
    }

    /**
     * @param Application $app
     * @param $routeParameter
     */
    public function bindPluginRoutes(Application $app, $routeParameter)
    {
        foreach ($app[$routeParameter] as $providerDefinition) {
            $prefix = '';
            $providerKey = $providerDefinition;

            if (is_array($providerDefinition)) {
                list($prefix, $providerKey) = $providerDefinition;
            }

            if (!$this->isValidProviderDefinition($app, $prefix, $providerKey)) {
                continue;
            }

            $prefix = '/' . ltrim($prefix, '/');
            $provider = $app[$providerKey];

            if (!$provider instanceof ControllerProviderInterface) {
                continue;
            }

            $app->mount($prefix, $provider);
        }
    }

    private function isValidProviderDefinition(Application $app, $prefix, $provider)
    {
        if (!is_string($prefix) || !is_string($provider)) {
            return false;
        }

        if (!isset($app[$provider])) {
            return false;
        }

        return true;
    }
}
