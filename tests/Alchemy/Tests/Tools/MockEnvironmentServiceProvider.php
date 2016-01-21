<?php

namespace Alchemy\Tests\Tools;

use Guzzle\Http\Client as Guzzle;
use Monolog\Handler\NullHandler;
use Monolog\Logger;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpKernel\Client;

class MockEnvironmentServiceProvider
{

    public function loadMocks(\Pimple $container)
    {
        $container['app'] = $container->share(function ($container) {
            return $this->loadApp($this->getApplicationPath());
        });

        $container['cli'] = $container->share(function ($container) {
            return $this->loadCLI();
        });

        $container['local-guzzle'] = $container->share(function ($container) {
            return new Guzzle($container['app']['conf']->get('servername'));
        });

        $container['client'] = $container->share(function ($container) {
            return new Client($container['app'], []);
        });

        $container['feed_public'] = $container->share(function ($container) {
            return $container['app']['repo.feeds']->find($container['fixtures']['feed']['public']['feed']);
        });
        $container['feed_public_entry'] = $container->share(function ($container) {
            return $container['app']['repo.feed-entries']->find($container['fixtures']['feed']['public']['entry']);
        });
        $container['feed_public_token'] = $container->share(function ($container) {
            return $container['app']['repo.feed-tokens']->find($container['fixtures']['feed']['public']['token']);
        });

        $container['feed_private'] = $container->share(function ($container) {
            return $container['app']['repo.feeds']->find($container['fixtures']['feed']['private']['feed']);
        });
        $container['feed_private_entry'] = $container->share(function ($container) {
            return $container['app']['repo.feed-entries']->find($container['fixtures']['feed']['private']['entry']);
        });
        $container['feed_private_token'] = $container->share(function ($container) {
            return $container['app']['repo.feed-tokens']->find($container['fixtures']['feed']['private']['token']);
        });

        $container['basket_1'] = $container->share(function ($container) {
            return $container['app']['repo.baskets']->find($container['fixtures']['basket']['basket_1']);
        });

        $container['basket_2'] = $container->share(function ($container) {
            return $container['app']['repo.baskets']->find($container['fixtures']['basket']['basket_2']);
        });

        $container['basket_3'] = $container->share(function ($container) {
            return $container['app']['repo.baskets']->find($container['fixtures']['basket']['basket_3']);
        });

        $container['basket_4'] = $container->share(function ($container) {
            return $container['app']['repo.baskets']->find($container['fixtures']['basket']['basket_4']);
        });

        $container['token_1'] = $container->share(function ($container) {
            return $container['app']['repo.tokens']->find($container['fixtures']['token']['token_1']);
        });

        $container['token_2'] = $container->share(function ($container) {
            return $container['app']['repo.tokens']->find($container['fixtures']['token']['token_2']);
        });

        $container['token_invalid'] = $container->share(function ($container) {
            return $container['app']['repo.tokens']->find($container['fixtures']['token']['token_invalid']);
        });

        $container['token_validation'] = $container->share(function ($container) {
            return $container['app']['repo.tokens']->find($container['fixtures']['token']['token_validation']);
        });

        $users = [
            'user' => 'test_phpunit',
            'user_1' => 'user_1',
            'user_2' => 'user_2',
            'user_3' => 'user_3',
            'user_guest' => 'user_guest',
            'user_notAdmin' => 'test_phpunit_not_admin',
            'user_alt1' => 'test_phpunit_alt1',
            'user_alt2' => 'test_phpunit_alt2',
            'user_template' => 'user_template',
        ];

        $userFactory = function ($fixtureName) {
            if ('user_guest' === $fixtureName) {
                return function ($container) use ($fixtureName) {
                    return $container['app']['repo.users']->find($container['fixtures']['user'][$fixtureName]);
                };
            }

            return function ($container) use ($fixtureName) {
                $user = $container['app']['repo.users']->find($container['fixtures']['user'][$fixtureName]);

                self::resetUsersRights($container['app'], $user);

                return $user;
            };
        };

        foreach ($users as $name => $fixtureName) {
            $container[$name] = $container->share($userFactory($fixtureName));
        }

        $container['registration_1'] = $container->share(function ($container) {
            return $container['app']['repo.registrations']->find($container['fixtures']['registrations']['registration_1']);
        });
        $container['registration_2'] = $container->share(function ($container) {
            return $container['app']['repo.registrations']->find($container['fixtures']['registrations']['registration_2']);
        });
        $container['registration_3'] = $container->share(function ($container) {
            return $container['app']['repo.registrations']->find($container['fixtures']['registrations']['registration_3']);
        });

        $container['oauth2-app-user'] = $container->share(function ($container) {
            return $container['app']['repo.api-applications']->find($container['fixtures']['oauth']['user']);
        });

        $container['webhook-event'] = $container->share(function ($container) {
            return $container['app']['repo.webhook-event']->find($container['fixtures']['webhook']['event']);
        });

        $container['oauth2-app-user-not-admin'] = $container->share(function ($container) {
            return $container['app']['repo.api-applications']->find($container['fixtures']['oauth']['user-not-admin']);
        });

        $container['oauth2-app-acc-user'] = $container->share(function ($container) {
            return $container['app']['repo.api-accounts']->find($container['fixtures']['oauth']['acc-user']);
        });

        $container['oauth2-app-acc-user-not-admin'] = $container->share(function ($container) {
            return $container['app']['repo.api-accounts']->find($container['fixtures']['oauth']['acc-user-not-admin']);
        });

        $container['logger'] = $container->share(function () {
            $logger = new Logger('tests');

            $logger->pushHandler(new NullHandler());

            return $logger;
        });

        $container['collection'] = $container->share(function ($container) {
            return \collection::getByBaseId($container['app'], $container['fixtures']['collection']['coll']);
        });

        $container['collection_no_access'] = $container->share(function ($container) {
            return \collection::getByBaseId($container['app'], $container['fixtures']['collection']['coll_no_access']);
        });

        $container['collection_no_access_by_status'] = $container->share(function ($container) {
            return \collection::getByBaseId($container['app'], $container['fixtures']['collection']['coll_no_status']);
        });

        $container['lazaret_1'] = $container->share(function ($container) {
            return $container['app']['orm.em']->find('Phraseanet:LazaretFile', $container['fixtures']['lazaret']['lazaret_1']);
        });

        foreach (range(1, 7) as $i) {
            $container['record_' . $i] = $container->share(function ($container) use ($i) {
                return new \record_adapter($container['app'], $container['fixtures']['databox']['records'], $container['fixtures']['record']['record_'.$i]);
            });
        }

        foreach (range(1, 3) as $i) {
            $container['record_story_' . $i] = $container->share(function ($container) use ($i) {
                return new \record_adapter($container['app'], $container['fixtures']['databox']['records'], $container['fixtures']['record']['record_story_'.$i]);
            });
        }

        $container['record_no_access_resolver'] = $container->protect(function () {
            $id = 'no_access';

            if (isset($container['fixtures']['records'][$id])) {
                return $container['fixtures']['records'][$id];
            }

            self::$recordsInitialized[] = $id;
            $file = new File($container['app'], $container['app']['mediavorus']->guess(__DIR__ . '/../files/cestlafete.jpg'), $container['collection_no_access']);
            $record = \record_adapter::createFromFile($file, $container['app']);
            $container['app']['subdef.generator']->generateSubdefs($record);
            $container['fixtures']['records'][$id] = $record->get_record_id();

            return $container['fixtures']['records'][$id];
        });

        $container['record_no_access_by_status_resolver'] = $container->protect(function () {
            $id = 'no_access_by_status';

            if (isset($container['fixtures']['records'][$id])) {
                return $container['fixtures']['records'][$id];
            }

            self::$recordsInitialized[] = $id;

            $file = new File(
                $container['app'],
                $container['app']['mediavorus']->guess(
                    __DIR__ . '/../files/cestlafete.jpg'),
                $container['collection_no_access_by_status']
            );

            $record = \record_adapter::createFromFile($file, $container['app']);

            $container['app']['subdef.generator']->generateSubdefs($record);
            $container['fixtures']['records'][$id] = $record->get_record_id();

            return $container['fixtures']['records'][$id];
        });

        $container['record_no_access'] = $container->share(function ($container) {
            return new \record_adapter($container['app'], $container['fixtures']['databox']['records'], $container['record_no_access_resolver']());
        });

        $container['record_no_access_by_status'] = $container->share(function ($container) {
            return new \record_adapter($container['app'], $container['fixtures']['databox']['records'], $container['record_no_access_by_status_resolver']());
        });

        static $decodedFixtureIds;

        if (is_null($decodedFixtureIds)) {
            $decodedFixtureIds = json_decode(file_get_contents(sys_get_temp_dir() . '/fixtures.json'), true);
        }

        $container['fixtures'] = $decodedFixtureIds;
    }
}
