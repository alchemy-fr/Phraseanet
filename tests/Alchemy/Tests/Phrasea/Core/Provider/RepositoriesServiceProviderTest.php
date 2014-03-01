<?php

namespace Alchemy\Tests\Phrasea\Core\Provider;

class RepositoriesServiceProviderTest extends ServiceProviderTestCase
{
    public function provideServiceDescription()
    {
        return [
            ['Alchemy\Phrasea\Core\Provider\RepositoriesServiceProvider', 'repo.users', 'Doctrine\\ORM\\EntityRepository'],
            ['Alchemy\Phrasea\Core\Provider\RepositoriesServiceProvider', 'repo.tasks', 'Doctrine\\ORM\\EntityRepository'],
            ['Alchemy\Phrasea\Core\Provider\RepositoriesServiceProvider', 'repo.registrations', 'Doctrine\\ORM\\EntityRepository'],
            ['Alchemy\Phrasea\Core\Provider\RepositoriesServiceProvider', 'repo.baskets', 'Doctrine\\ORM\\EntityRepository'],
            ['Alchemy\Phrasea\Core\Provider\RepositoriesServiceProvider', 'repo.basket-elements', 'Doctrine\\ORM\\EntityRepository'],
            ['Alchemy\Phrasea\Core\Provider\RepositoriesServiceProvider', 'repo.validation-participants', 'Doctrine\\ORM\\EntityRepository'],
            ['Alchemy\Phrasea\Core\Provider\RepositoriesServiceProvider', 'repo.story-wz', 'Doctrine\\ORM\\EntityRepository'],
            ['Alchemy\Phrasea\Core\Provider\RepositoriesServiceProvider', 'repo.orders', 'Doctrine\\ORM\\EntityRepository'],
            ['Alchemy\Phrasea\Core\Provider\RepositoriesServiceProvider', 'repo.order-elements', 'Doctrine\\ORM\\EntityRepository'],
            ['Alchemy\Phrasea\Core\Provider\RepositoriesServiceProvider', 'repo.feeds', 'Doctrine\\ORM\\EntityRepository'],
            ['Alchemy\Phrasea\Core\Provider\RepositoriesServiceProvider', 'repo.feed-entries', 'Doctrine\\ORM\\EntityRepository'],
            ['Alchemy\Phrasea\Core\Provider\RepositoriesServiceProvider', 'repo.feed-items', 'Doctrine\\ORM\\EntityRepository'],
            ['Alchemy\Phrasea\Core\Provider\RepositoriesServiceProvider', 'repo.feed-publishers', 'Doctrine\\ORM\\EntityRepository'],
            ['Alchemy\Phrasea\Core\Provider\RepositoriesServiceProvider', 'repo.aggregate-tokens', 'Doctrine\\ORM\\EntityRepository'],
            ['Alchemy\Phrasea\Core\Provider\RepositoriesServiceProvider', 'repo.usr-lists', 'Doctrine\\ORM\\EntityRepository'],
            ['Alchemy\Phrasea\Core\Provider\RepositoriesServiceProvider', 'repo.usr-list-owners', 'Doctrine\\ORM\\EntityRepository'],
            ['Alchemy\Phrasea\Core\Provider\RepositoriesServiceProvider', 'repo.usr-list-entries', 'Doctrine\\ORM\\EntityRepository'],
            ['Alchemy\Phrasea\Core\Provider\RepositoriesServiceProvider', 'repo.lazaret-files', 'Doctrine\\ORM\\EntityRepository'],
            ['Alchemy\Phrasea\Core\Provider\RepositoriesServiceProvider', 'repo.usr-auth-providers', 'Doctrine\\ORM\\EntityRepository'],
            ['Alchemy\Phrasea\Core\Provider\RepositoriesServiceProvider', 'repo.ftp-exports', 'Doctrine\\ORM\\EntityRepository'],
            ['Alchemy\Phrasea\Core\Provider\RepositoriesServiceProvider', 'repo.user-queries', 'Doctrine\\ORM\\EntityRepository'],
            ['Alchemy\Phrasea\Core\Provider\RepositoriesServiceProvider', 'repo.feed-tokens', 'Doctrine\\ORM\\EntityRepository'],
            ['Alchemy\Phrasea\Core\Provider\RepositoriesServiceProvider', 'repo.sessions', 'Doctrine\\ORM\\EntityRepository'],
        ];
    }
}
