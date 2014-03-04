<?php

namespace Alchemy\Tests\Phrasea\Core\Provider;

class RepositoriesServiceProviderTest extends ServiceProviderTestCase
{
    public function provideServiceDescription()
    {
        return [
            ['Alchemy\Phrasea\Core\Provider\RepositoriesServiceProvider', 'repo.users', 'Alchemy\Phrasea\Model\Repositories\UserRepository'],
            ['Alchemy\Phrasea\Core\Provider\RepositoriesServiceProvider', 'repo.auth-failures', 'Alchemy\Phrasea\Model\Repositories\AuthFailureRepository'],
            ['Alchemy\Phrasea\Core\Provider\RepositoriesServiceProvider', 'repo.sessions', 'Alchemy\Phrasea\Model\Repositories\SessionRepository'],
            ['Alchemy\Phrasea\Core\Provider\RepositoriesServiceProvider', 'repo.tasks', 'Alchemy\Phrasea\Model\Repositories\TaskRepository'],
            ['Alchemy\Phrasea\Core\Provider\RepositoriesServiceProvider', 'repo.registrations', 'Alchemy\Phrasea\Model\Repositories\RegistrationRepository'],
            ['Alchemy\Phrasea\Core\Provider\RepositoriesServiceProvider', 'repo.baskets', 'Alchemy\Phrasea\Model\Repositories\BasketRepository'],
            ['Alchemy\Phrasea\Core\Provider\RepositoriesServiceProvider', 'repo.basket-elements', 'Alchemy\Phrasea\Model\Repositories\BasketElementRepository'],
            ['Alchemy\Phrasea\Core\Provider\RepositoriesServiceProvider', 'repo.validation-participants', 'Alchemy\Phrasea\Model\Repositories\ValidationParticipantRepository'],
            ['Alchemy\Phrasea\Core\Provider\RepositoriesServiceProvider', 'repo.story-wz', 'Alchemy\Phrasea\Model\Repositories\StoryWZRepository'],
            ['Alchemy\Phrasea\Core\Provider\RepositoriesServiceProvider', 'repo.orders', 'Alchemy\Phrasea\Model\Repositories\OrderRepository'],
            ['Alchemy\Phrasea\Core\Provider\RepositoriesServiceProvider', 'repo.order-elements', 'Alchemy\Phrasea\Model\Repositories\OrderElementRepository'],
            ['Alchemy\Phrasea\Core\Provider\RepositoriesServiceProvider', 'repo.feeds', 'Alchemy\Phrasea\Model\Repositories\FeedRepository'],
            ['Alchemy\Phrasea\Core\Provider\RepositoriesServiceProvider', 'repo.feed-entries', 'Alchemy\Phrasea\Model\Repositories\FeedEntryRepository'],
            ['Alchemy\Phrasea\Core\Provider\RepositoriesServiceProvider', 'repo.feed-items', 'Alchemy\Phrasea\Model\Repositories\FeedItemRepository'],
            ['Alchemy\Phrasea\Core\Provider\RepositoriesServiceProvider', 'repo.feed-publishers', 'Alchemy\Phrasea\Model\Repositories\FeedPublisherRepository'],
            ['Alchemy\Phrasea\Core\Provider\RepositoriesServiceProvider', 'repo.feed-tokens', 'Alchemy\Phrasea\Model\Repositories\FeedTokenRepository'],
            ['Alchemy\Phrasea\Core\Provider\RepositoriesServiceProvider', 'repo.aggregate-tokens', 'Alchemy\Phrasea\Model\Repositories\AggregateTokenRepository'],
            ['Alchemy\Phrasea\Core\Provider\RepositoriesServiceProvider', 'repo.usr-lists', 'Alchemy\Phrasea\Model\Repositories\UsrListRepository'],
            ['Alchemy\Phrasea\Core\Provider\RepositoriesServiceProvider', 'repo.usr-list-owners', 'Alchemy\Phrasea\Model\Repositories\UsrListOwnerRepository'],
            ['Alchemy\Phrasea\Core\Provider\RepositoriesServiceProvider', 'repo.usr-list-entries', 'Alchemy\Phrasea\Model\Repositories\UsrListEntryRepository'],
            ['Alchemy\Phrasea\Core\Provider\RepositoriesServiceProvider', 'repo.lazaret-files', 'Alchemy\Phrasea\Model\Repositories\LazaretFileRepository'],
            ['Alchemy\Phrasea\Core\Provider\RepositoriesServiceProvider', 'repo.usr-auth-providers', 'Alchemy\Phrasea\Model\Repositories\UsrAuthProviderRepository'],
            ['Alchemy\Phrasea\Core\Provider\RepositoriesServiceProvider', 'repo.ftp-exports', 'Alchemy\Phrasea\Model\Repositories\FtpExportRepository'],
            ['Alchemy\Phrasea\Core\Provider\RepositoriesServiceProvider', 'repo.user-queries', 'Alchemy\Phrasea\Model\Repositories\UserQueryRepository'],
            ['Alchemy\Phrasea\Core\Provider\RepositoriesServiceProvider', 'repo.tokens', 'Alchemy\Phrasea\Model\Repositories\TokenRepository'],
            ['Alchemy\Phrasea\Core\Provider\RepositoriesServiceProvider', 'repo.presets', 'Alchemy\Phrasea\Model\Repositories\PresetRepository'],
        ];
    }
}
