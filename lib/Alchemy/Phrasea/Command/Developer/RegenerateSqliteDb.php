<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Command\Developer;

use Alchemy\Phrasea\Border\File;
use Alchemy\Phrasea\Border\Manager;
use Alchemy\Phrasea\Command\Command;
use Alchemy\Phrasea\ControllerProvider\Api\V2;
use Alchemy\Phrasea\Filesystem\PhraseanetFilesystem as Filesystem;
use Alchemy\Phrasea\Media\SubdefSubstituer;
use Alchemy\Phrasea\Model\Entities\AggregateToken;
use Alchemy\Phrasea\Model\Entities\ApiApplication;
use Alchemy\Phrasea\Model\Entities\AuthFailure;
use Alchemy\Phrasea\Model\Entities\Basket;
use Alchemy\Phrasea\Model\Entities\BasketElement;
use Alchemy\Phrasea\Model\Entities\Feed;
use Alchemy\Phrasea\Model\Entities\FeedEntry;
use Alchemy\Phrasea\Model\Entities\FeedItem;
use Alchemy\Phrasea\Model\Entities\FeedPublisher;
use Alchemy\Phrasea\Model\Entities\FeedToken;
use Alchemy\Phrasea\Model\Entities\LazaretSession;
use Alchemy\Phrasea\Model\Entities\Registration;
use Alchemy\Phrasea\Model\Entities\StoryWZ;
use Alchemy\Phrasea\Model\Entities\Task;
use Alchemy\Phrasea\Model\Entities\Token;
use Alchemy\Phrasea\Model\Entities\User;
use Alchemy\Phrasea\Model\Entities\UsrList;
use Alchemy\Phrasea\Model\Entities\UsrListEntry;
use Alchemy\Phrasea\Model\Entities\UsrListOwner;
use Alchemy\Phrasea\Model\Entities\WebhookEvent;
use Alchemy\Phrasea\Model\Entities\WebhookEventDelivery;
use Alchemy\Phrasea\Model\Manipulator\ApiAccountManipulator;
use Alchemy\Phrasea\Model\Manipulator\ApiOauthTokenManipulator;
use Alchemy\Phrasea\Model\Manipulator\TokenManipulator;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
use Gedmo\Timestampable\TimestampableListener;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

// use Symfony\Component\Filesystem\Filesystem;


class RegenerateSqliteDb extends Command
{
    public function __construct()
    {
        parent::__construct('phraseanet:regenerate-sqlite');

        $this->setDescription("Updates the sqlite 'db-ref.sqlite' database with current database definition.");
    }

    public function doExecute(InputInterface $input, OutputInterface $output)
    {
        $fs = new Filesystem();

        $json = sprintf('%s/fixtures.json', sys_get_temp_dir());

        if ($fs->exists($json)) {
            $fs->remove($json);
        }

        $this->container['orm.em'] = $this->container->extend('orm.em', function($em, $app) {
            return $app['orm.ems'][$app['db.fixture.hash.key']];
        });

        $em = $this->container['orm.em'];

        if ($fs->exists($em->getConnection()->getParams()['path'])) {
            $fs->remove($em->getConnection()->getParams()['path']);
        }

        $schemaTool = new SchemaTool($em);
        $schemaTool->createSchema($em->getMetadataFactory()->getAllMetadata());

        $fixtures = [];

        $DI = new \Pimple();

        $this->generateUsers($em, $DI);
        $this->insertOauthApps($DI);
        $this->insertOauthAccounts($DI);
        $this->insertNativeApps();
        $this->generateCollection($DI);
        $this->generateRecord($DI);
        $this->insertTwoTasks($em);
        $this->insertTwoBasket($em, $DI);
        $this->insertOneStoryInWz($em, $DI);
        $this->insertUsrLists($em, $DI);
        $this->insertOnePrivateFeed($em, $DI);
        $this->insertOnePublicFeed($em, $DI);
        $this->insertOneExtraFeed($em, $DI);
        $this->insertOneAggregateToken($em, $DI);
        $this->insertLazaretFiles($em, $DI);
        $this->insertAuthFailures($em, $DI);
        $this->insertOneRegistration($DI, $em, $DI['user_alt1'], $DI['coll'], 'now', 'registration_1');
        $this->insertOneRegistration($DI, $em, $DI['user_alt2'], $DI['coll'], '-3 months', 'registration_2');
        $this->insertOneRegistration($DI, $em, $DI['user_notAdmin'], $DI['coll'], 'now', 'registration_3');
        $this->insertTwoTokens($em, $DI);
        $this->insertOneInvalidToken($em, $DI);
        $this->insertOneValidationToken($em, $DI);
        $this->insertWebhookEvent($em, $DI);
        $this->insertWebhookEventDelivery($em, $DI);

        $em->flush();
        $this->container['elasticsearch.indexer']->flushQueue();

        $fixtures['basket']['basket_1'] = $DI['basket_1']->getId();
        $fixtures['basket']['basket_2'] = $DI['basket_2']->getId();
        $fixtures['basket']['basket_3'] = $DI['basket_3']->getId();
        $fixtures['basket']['basket_4'] = $DI['basket_4']->getId();

        $fixtures['token']['token_1'] = $DI['token_1']->getValue();
        $fixtures['token']['token_2'] = $DI['token_2']->getValue();
        $fixtures['token']['token_invalid'] = $DI['token_invalid']->getValue();
        $fixtures['token']['token_validation'] = $DI['token_validation']->getValue();

        $fixtures['user']['test_phpunit'] = $DI['user']->getId();
        $fixtures['user']['test_phpunit_not_admin'] = $DI['user_notAdmin']->getId();
        $fixtures['user']['test_phpunit_alt1'] = $DI['user_alt1']->getId();
        $fixtures['user']['test_phpunit_alt2'] = $DI['user_alt2']->getId();
        $fixtures['user']['user_guest'] = $DI['user_guest']->getId();

        $fixtures['oauth']['user'] = $DI['api-app-user']->getId();
        $fixtures['oauth']['user1'] = $DI['api-app-user1']->getId();
        $fixtures['oauth']['acc-user'] = $DI['api-app-acc-user']->getId();
        $fixtures['oauth']['user-not-admin'] = $DI['api-app-user-not-admin']->getId();
        $fixtures['oauth']['acc-user-not-admin'] = $DI['api-app-acc-user-not-admin']->getId();

        $fixtures['databox']['records'] = $DI['databox']->get_sbas_id();

        $fixtures['collection']['coll'] = $DI['coll']->get_base_id();
        $fixtures['collection']['coll_no_access'] = $DI['coll_no_access']->get_base_id();
        $fixtures['collection']['coll_no_status'] = $DI['coll_no_status']->get_base_id();

        $fixtures['record']['record_story_1'] = $DI['record_story_1']->get_record_id();
        $fixtures['record']['record_story_2'] = $DI['record_story_2']->get_record_id();
        $fixtures['record']['record_story_3'] = $DI['record_story_3']->get_record_id();

        $fixtures['record']['record_1'] = $DI['record_1']->get_record_id();
        $fixtures['record']['record_2'] = $DI['record_2']->get_record_id();
        $fixtures['record']['record_3'] = $DI['record_3']->get_record_id();
        $fixtures['record']['record_4'] = $DI['record_4']->get_record_id();
        $fixtures['record']['record_5'] = $DI['record_5']->get_record_id();
        $fixtures['record']['record_6'] = $DI['record_6']->get_record_id();
        $fixtures['record']['record_7'] = $DI['record_7']->get_record_id();

        $fixtures['registrations']['registration_1'] = $DI['registration_1']->getId();
        $fixtures['registrations']['registration_2'] = $DI['registration_2']->getId();
        $fixtures['registrations']['registration_3'] = $DI['registration_3']->getId();

        $fixtures['lazaret']['lazaret_1'] = $DI['lazaret_1']->getId();

        $fixtures['user']['user_1'] = $DI['user_1']->getId();
        $fixtures['user']['user_2'] = $DI['user_2']->getId();
        $fixtures['user']['user_3'] = $DI['user_3']->getId();
        $fixtures['user']['user_1_deleted'] = $DI['user_1_deleted']->getId();
        $fixtures['user']['user_2_deleted'] = $DI['user_2_deleted']->getId();
        $fixtures['user']['user_3_deleted'] = $DI['user_3_deleted']->getId();
        $fixtures['user']['user_template'] = $DI['user_template']->getId();

        $fixtures['feed']['public']['feed'] = $DI['feed_public']->getId();
        $fixtures['feed']['public']['entry'] = $DI['feed_public_entry']->getId();
        $fixtures['feed']['public']['token'] = $DI['feed_public_token']->getId();

        $fixtures['feed']['private']['feed'] = $DI['feed_private']->getId();
        $fixtures['feed']['private']['entry'] = $DI['feed_private_entry']->getId();
        $fixtures['feed']['private']['token'] = $DI['feed_private_token']->getId();

        $fixtures['webhook']['event'] = $DI['event_webhook_1']->getId();

        $fs->dumpFile($json, json_encode($fixtures, defined('JSON_PRETTY_PRINT') ? JSON_PRETTY_PRINT : 0));

        return 0;
    }

    private function insertOauthApps(\Pimple $DI)
    {
        if (null === $DI['api-app-user'] = $this->container['repo.api-applications']->findOneByName('api-app-user')) {
            $DI['api-app-user'] = $this->container['manipulator.api-application']->create(
                'test-web',
                ApiApplication::WEB_TYPE,
                '',
                'http://website.com/',
                $DI['user'],
                'http://callback.com/callback/'
            );
        }

        if (null === $DI['api-app-user-not-admin'] = $this->container['repo.api-applications']->findOneByName('test-desktop')) {
            $DI['api-app-user-not-admin'] = $this->container['manipulator.api-application']->create(
                'test-desktop',
                ApiApplication::WEB_TYPE,
                '',
                'http://website.com/',
                $DI['user_notAdmin'],
                'http://callback.com/callback/'
            );
        }

        if (null === $DI['api-app-user1'] = $this->container['repo.api-applications']->findOneByName('test-web-user1')) {
            $DI['api-app-user1'] = $this->container['manipulator.api-application']->create(
                'test-web-user1',
                ApiApplication::WEB_TYPE,
                '',
                'http://website.com/',
                $DI['user_1'],
                'http://callback.com/callback/'
            );
        }

    }

    public function insertOauthAccounts(\Pimple $DI)
    {
        /** @var ApiAccountManipulator $apiAccountManipulator */
        $apiAccountManipulator = $this->container['manipulator.api-account'];
        /** @var ApiOauthTokenManipulator $apiOAuthTokenManipulator */
        $apiOAuthTokenManipulator = $this->container['manipulator.api-oauth-token'];
        $DI['api-app-acc-user'] = $apiAccountManipulator->create($DI['api-app-user'], $DI['user'], V2::VERSION);
        $apiOAuthTokenManipulator->create($DI['api-app-acc-user']);
        $DI['api-app-acc-user-not-admin'] = $apiAccountManipulator->create($DI['api-app-user-not-admin'], $DI['user_notAdmin'], V2::VERSION);
        $apiOAuthTokenManipulator->create($DI['api-app-acc-user-not-admin']);
        $DI['api-app-acc-user1'] = $apiAccountManipulator->create($DI['api-app-user1'], $DI['user_1'], V2::VERSION);
        $apiOAuthTokenManipulator->create($DI['api-app-acc-user1']);
    }

    public function insertNativeApps()
    {
        $application = $this->container['manipulator.api-application']->create(
            \API_OAuth2_Application_Navigator::CLIENT_NAME,
            ApiApplication::DESKTOP_TYPE,
            '',
            'http://www.phraseanet.com',
            null,
            ApiApplication::NATIVE_APP_REDIRECT_URI
        );

        $application->setGrantPassword(true);
        $application->setClientId(\API_OAuth2_Application_Navigator::CLIENT_ID);
        $application->setClientSecret(\API_OAuth2_Application_Navigator::CLIENT_SECRET);

        $this->container['manipulator.api-application']->update($application);

        $application = $this->container['manipulator.api-application']->create(
            \API_OAuth2_Application_OfficePlugin::CLIENT_NAME,
            ApiApplication::DESKTOP_TYPE,
            '',
            'http://www.phraseanet.com',
            null,
            ApiApplication::NATIVE_APP_REDIRECT_URI
        );

        $application->setGrantPassword(true);
        $application->setClientId(\API_OAuth2_Application_OfficePlugin::CLIENT_ID);
        $application->setClientSecret(\API_OAuth2_Application_OfficePlugin::CLIENT_SECRET);

        $this->container['manipulator.api-application']->update($application);

        $application = $this->container['manipulator.api-application']->create(
            \API_OAuth2_Application_AdobeCCPlugin::CLIENT_NAME,
            ApiApplication::DESKTOP_TYPE,
            '',
            'http://www.phraseanet.com',
            null,
            ApiApplication::NATIVE_APP_REDIRECT_URI
        );

        $application->setGrantPassword(true);
        $application->setClientId(\API_OAuth2_Application_AdobeCCPlugin::CLIENT_ID);
        $application->setClientSecret(\API_OAuth2_Application_AdobeCCPlugin::CLIENT_SECRET);

        $this->container['manipulator.api-application']->update($application);
    }

    private function insertAuthFailures(EntityManager $em, \Pimple $DI)
    {
        $ip = '192.168.16.178';
        $username = 'romainneutron';

        for ($i = 0; $i < 10; $i++) {
            $failure = new AuthFailure();
            $failure->setIp($ip);
            $failure->setUsername($username);
            $failure->setLocked(false);
            $failure->setCreated(new \DateTime('-3 months'));
            $em->persist($failure);
        }
        for ($i = 0; $i < 2; $i++) {
            $failure = new AuthFailure();
            $failure->setIp($ip);
            $failure->setUsername($username);
            $failure->setLocked(false);
            $failure->setCreated(new \DateTime('-1 months'));
            $em->persist($failure);
        }
    }

    private function insertLazaretFiles(EntityManager $em, \Pimple $DI)
    {
        $session = new LazaretSession();
        $session->setUser($DI['user']);
        $em->persist($session);

        $file = File::buildFromPathfile($this->container['root.path'] . '/tests/files/cestlafete.jpg', $DI['coll'], $this->container);

        $callback = function ($element) use ($DI) {
            $DI['lazaret_1'] = $element;
        };

        /** @var Manager $borderManager */
        $borderManager = $this->container['border-manager'];
        $borderManager->process($session, $file, $callback, Manager::FORCE_LAZARET);
    }

    private function generateUsers(EntityManager $em, \Pimple $DI)
    {
        $DI['user'] = $this->getUser();
        $DI['user_alt1'] = $this->getUserAlt1();
        $DI['user_alt2'] = $this->getUserAlt2();
        $DI['user_notAdmin'] = $this->getUserNotAdmin();
        $DI['user_guest'] = $this->getUserGuest();

        $user1 = $this->insertOneUser('user1');
        $user2 = $this->insertOneUser('user2', 'user2@phraseanet.com');
        $user3 = $this->insertOneUser('user3', null, true);

        $user1Deleted = $this->insertOneUser('user1-deleted');
        $user1Deleted->setDeleted(true);
        $user2Deleted = $this->insertOneUser('user2-deleted', 'user2-deleted@phraseanet.com');
        $user2Deleted->setDeleted(true);
        $user3Deleted = $this->insertOneUser('user3-deleted', null, true);
        $user3Deleted->setDeleted(true);

        $template = $this->insertOneUser('template', null, true);
        $template->setTemplateOwner($user1);

        $DI['user_1'] = $user1;
        $DI['user_2'] = $user2;
        $DI['user_3'] = $user3;
        $DI['user_1_deleted'] = $user1Deleted;
        $DI['user_2_deleted'] = $user2Deleted;
        $DI['user_3_deleted'] = $user3Deleted;
        $DI['user_template'] = $template;

        $em->persist($user1);
        $em->persist($user2);
        $em->persist($user3);
        $em->persist($user1Deleted);
        $em->persist($user2Deleted);
        $em->persist($user3Deleted);
        $em->persist($template);
    }

    protected function insertOneUser($login, $email = null, $admin = false)
    {
        return $this->container['manipulator.user']->createUser($login, uniqid('pass'), $email, $admin);
    }

    protected function insertWebhookEvent(EntityManager $em, \Pimple $DI)
    {
        $event = new WebhookEvent();
        $event->setName(WebhookEvent::NEW_FEED_ENTRY);
        $event->setType(WebhookEvent::FEED_ENTRY_TYPE);
        $event->setData([
            'feed_id' => $DI['feed_public_entry']->getFeed()->getId(),
            'entry_id' => $DI['feed_public_entry']->getId()
        ]);
        $em->persist($event);

        $DI['event_webhook_1'] = $event;

        $event2 = new WebhookEvent();
        $event2->setName(WebhookEvent::NEW_FEED_ENTRY);
        $event2->setType(WebhookEvent::FEED_ENTRY_TYPE);
        $event2->setData([
            'feed_id' => $DI['feed_public_entry']->getFeed()->getId(),
            'entry_id' => $DI['feed_public_entry']->getId()
        ]);
        $event2->setProcessed(true);
        $em->persist($event2);
    }

    protected function insertWebhookEventDelivery(EntityManager $em, \Pimple $DI)
    {
        $delivery = new WebhookEventDelivery();
        $delivery->setThirdPartyApplication($DI['api-app-user']);
        $delivery->setWebhookEvent($DI['event_webhook_1']);
        $delivery->setDelivered(true);
        $em->persist($delivery);

        $delivery2 = new WebhookEventDelivery();
        $delivery2->setThirdPartyApplication($DI['api-app-user-not-admin']);
        $delivery2->setWebhookEvent($DI['event_webhook_1']);
        $delivery2->setDeliverTries(1);
        $em->persist($delivery2);
    }

    private function generateCollection(\Pimple $DI)
    {
        $coll = $collection_no_acces = $collection_no_acces_by_status = null;
        /** @var \databox[] $databoxes */
        $databoxes = $this->container->getDataboxes();

        foreach ($databoxes as $databox) {
            foreach ($databox->get_collections() as $collection) {
                if ($collection_no_acces instanceof \collection && !$collection_no_acces_by_status) {
                    $collection_no_acces_by_status = $collection;
                }
                if ($coll instanceof \collection && !$collection_no_acces) {
                    $collection_no_acces = $collection;
                }
                if (!$coll) {
                    $coll = $collection;
                }
                if ($coll instanceof \collection
                    && $collection_no_acces instanceof \collection
                    && $collection_no_acces_by_status instanceof \collection) {
                    break 2;
                }
            }
        }

        $DI['databox'] = $databox = $coll->get_databox();
        $DI['coll'] = $coll;
        if (!$collection_no_acces instanceof \collection) {
            $collection_no_acces = \collection::create($this->container, $databox, $this->container->getApplicationBox(), 'COLL_TEST_NO_ACCESS', $DI['user']);
        }

        $DI['coll_no_access'] = $collection_no_acces;

        if (!$collection_no_acces_by_status instanceof \collection) {
            $collection_no_acces_by_status = \collection::create($this->container, $databox, $this->container->getApplicationBox(), 'COLL_TEST_NO_ACCESS_BY_STATUS', $DI['user']);
        }

        $DI['coll_no_status'] = $collection_no_acces_by_status;
    }

    private function generateRecord(\Pimple $DI)
    {
        foreach (range(1, 7) as $i) {
            $file = new File($this->container, $this->container['mediavorus']->guess(__DIR__ . '/../../../../../tests/files/test001.jpg'), $DI['coll']);
            $record = \record_adapter::createFromFile($file, $this->container);
            $this->container['subdef.generator']->generateSubdefs($record);
            $DI['record_' . $i] = $record;
        }

        $media = $this->container['mediavorus']->guess($this->container['root.path'] . '/tests/files/cestlafete.jpg');

        foreach (range(1, 3) as $i) {
            $story = \record_adapter::createStory($this->container, $DI['coll']);
            if ($i < 3) {
                /** @var SubdefSubstituer $substituer */
                $substituer = $this->container['subdef.substituer'];
                $substituer->substituteSubdef($story, 'preview', $media);
                $substituer->substituteSubdef($story, 'thumbnail', $media);
            }
            $DI['record_story_' . $i] = $story;
        }
    }

    private function insertTwoTasks(EntityManager $em)
    {
        $task1 = new Task();
        $task1
            ->setName('task 1')
            ->setJobId('Null');

        $task2 = new Task();
        $task2
            ->setName('task 2')
            ->setJobId('Null');

        $em->persist($task1);
        $em->persist($task2);
    }

    private function getUser()
    {
        if (null === $user = $this->container['repo.users']->findByLogin('test_phpunit')) {
            $user = $this->container['manipulator.user']->createUser('test_phpunit', $this->container['random.low']->generateString(12), 'noone@example.com', true);
        }

        return $user;
    }

    private function getUserAlt1()
    {
        if (null === $user = $this->container['repo.users']->findByLogin('test_phpunit_alt1')) {
            $user = $this->container['manipulator.user']->createUser('test_phpunit_alt1', $this->container['random.low']->generateString(12), 'noonealt1@example.com', false);
        }

        return $user;
    }

    private function getUserAlt2()
    {
        if (null === $user = $this->container['repo.users']->findByLogin('test_phpunit_alt2')) {
            $user = $this->container['manipulator.user']->createUser('test_phpunit_alt2', $this->container['random.low']->generateString(12), 'noonealt2@example.com', false);
        }

        return $user;
    }

    public function getUserNotAdmin()
    {
        if (null === $user = $this->container['repo.users']->findByLogin('test_phpunit_not_admin')) {
            $user = $this->container['manipulator.user']->createUser('test_phpunit_not_admin', $this->container['random.low']->generateString(12), 'noone_not_admin@example.com', false);
        }

        return $user;
    }

    public function getUserGuest()
    {
        if (null === $user = $this->container['repo.users']->findByLogin(User::USER_GUEST)) {
            $user = $this->container['manipulator.user']->createUser(User::USER_GUEST, User::USER_GUEST);
        }

        return $user;
    }

    private function insertTwoBasket(EntityManager $em, \Pimple $DI)
    {
        $basket1 = new Basket();
        $basket1->setUser($this->getUser());
        $basket1->setName('test');
        $basket1->setDescription('description test');

        $DI['basket_1'] = $basket1;

        $element = new BasketElement();
        $element->setRecord($DI['record_1']);
        $basket1->addElement($element);
        $element->setBasket($basket1);

        $basket2 = new Basket();
        $basket2->setUser($this->getUser());
        $basket2->setName('test');
        $basket2->setDescription('description test');

        $DI['basket_2'] = $basket2;

        $basket3 = new Basket();
        $basket3->setUser($this->getUserAlt1());
        $basket3->setName('test');
        $basket3->setDescription('description test');

        $DI['basket_3'] = $basket3;

        $em->persist($basket1);
        $em->persist($element);
        $em->persist($basket2);
        $em->persist($basket3);

        $basket4 = new Basket();
        $basket4->setName('test');
        $basket4->setDescription('description');
        $basket4->setUser($this->getUser());

        foreach ([$DI['record_1'], $DI['record_2']] as $record) {
            $basketElement = new BasketElement();
            $basketElement->setRecord($record);
            $basketElement->setBasket($basket4);
            $basket4->addElement($basketElement);
            $em->persist($basketElement);
        }

        $basket4->startVoteSession($this->getUser());
        $expires = new \DateTime();
        $expires->modify('+1 week');
        $basket4->setVoteExpires($expires);

        foreach ([$this->getUser(), $DI['user_alt1'], $DI['user_alt2']] as $user) {
            $basketParticipant = $basket4->addParticipant($user);
            $basketParticipant->setCanAgree(true);

            foreach ($basket4->getElements() as $basketElement) {
                $basketElementVote = $basketElement->createVote($basketParticipant);

                $em->persist($basketElementVote);
            }
            $em->persist($basketParticipant);
        }

        $DI['basket_4'] = $basket4;

        $em->persist($basket4);
    }

    private function insertOneStoryInWz(EntityManager $em, \Pimple $DI)
    {
        $story = new StoryWZ();

        $story->setRecord($DI['record_story_1']);
        $story->setUser($DI['user']);

        $em->persist($story);
    }

    private function insertUsrLists(EntityManager $em, \Pimple $DI)
    {
        $owner1 = new UsrListOwner();
        $owner1->setRole(UsrListOwner::ROLE_ADMIN);
        $owner1->setUser($DI['user']);

        $owner2 = new UsrListOwner();
        $owner2->setRole(UsrListOwner::ROLE_ADMIN);
        $owner2->setUser($DI['user_alt1']);

        $list1 = new UsrList();
        $list1->setName('new list');
        $list1->addOwner($owner1);
        $owner1->setList($list1);

        $entry1 = new UsrListEntry();
        $entry1->setUser($DI['user']);
        $entry1->setList($list1);
        $list1->addEntrie($entry1);

        $entry2 = new UsrListEntry();
        $entry2->setUser($DI['user_alt1']);
        $entry2->setList($list1);
        $list1->addEntrie($entry2);

        $list2 = new UsrList();
        $list2->setName('new list');
        $list2->addOwner($owner2);
        $owner2->setList($list2);

        $entry3 = new UsrListEntry();
        $entry3->setUser($DI['user_alt1']);
        $entry3->setList($list2);
        $list2->addEntrie($entry3);

        $entry4 = new UsrListEntry();
        $entry4->setUser($DI['user_alt2']);
        $entry4->setList($list2);
        $list2->addEntrie($entry4);

        $em->persist($owner1);
        $em->persist($owner2);
        $em->persist($list1);
        $em->persist($list2);
        $em->persist($entry1);
        $em->persist($entry2);
        $em->persist($entry3);
        $em->persist($entry4);
    }

    private function insertOnePublicFeed(EntityManager $em, \Pimple $DI)
    {
        $feed = new Feed();
        $publisher = new FeedPublisher();

        $user = $DI['user'];

        $publisher->setUser($user);
        $publisher->setIsOwner(true);
        $publisher->setFeed($feed);

        $feed->addPublisher($publisher);

        $publisher1 = new FeedPublisher();

        $publisher1->setUser($DI['user_1']);
        $publisher1->setIsOwner(false);
        $publisher1->setFeed($feed);

        $feed->setTitle("Feed test, Public!");
        $feed->setIsPublic(true);
        $feed->setSubtitle("description");

        $em->persist($feed);
        $em->persist($publisher);
        $em->persist($publisher1);

        $entry = $this->insertOneFeedEntry($em, $DI, $feed, true);
        $token = $this->insertOneFeedToken($em, $DI, $feed);

        $DI['feed_public'] = $feed;
        $DI['feed_public_entry'] = $entry;
        $DI['feed_public_token'] = $token;
    }

    private function insertOnePrivateFeed(EntityManager $em, \Pimple $DI)
    {
        $feed = new Feed();
        $publisher = new FeedPublisher();

        $user = $DI['user'];

        $publisher->setUser($user);
        $publisher->setIsOwner(true);
        $publisher->setFeed($feed);

        $feed->addPublisher($publisher);
        $feed->setTitle("Feed test, YOLO!");
        $feed->setIsPublic(false);
        $feed->setSubtitle("description");

        $em->persist($feed);
        $em->persist($publisher);

        $entry = $this->insertOneFeedEntry($em, $DI, $feed, false);
        $token = $this->insertOneFeedToken($em, $DI, $feed);

        $DI['feed_private'] = $feed;
        $DI['feed_private_entry'] = $entry;
        $DI['feed_private_token'] = $token;
    }

    private function insertOneExtraFeed(EntityManager $em, \Pimple $DI)
    {
        $feed = new Feed();
        $publisher = new FeedPublisher();

        $user = $DI['user_alt1'];

        $publisher->setUser($user);
        $publisher->setIsOwner(true);
        $publisher->setFeed($feed);

        $feed->addPublisher($publisher);
        $feed->setTitle("Feed test, Private for user_alt1!");
        $feed->setIsPublic(false);
        $feed->setSubtitle("description");

        $em->persist($feed);
        $em->persist($publisher);

        $this->insertOneFeedEntry($em, $DI, $feed, true);
        $this->insertOneFeedToken($em, $DI, $feed);
    }

    private function insertOneFeedEntry(EntityManager $em, \Pimple $DI, Feed $feed, $public)
    {
        $entry = new FeedEntry();
        $entry->setFeed($feed);
        $entry->setTitle("test");
        $entry->setSubtitle("description");
        $entry->setAuthorName('user');
        $entry->setAuthorEmail('user@email.com');

        $publisher = $feed->getPublisher($DI['user']);

        if ($publisher !== null) {
            $entry->setPublisher($publisher);
        }

        $feed->addEntry($entry);

        $em->persist($entry);
        $em->persist($feed);

        $this->insertOneFeedItem($em, $DI, $entry, $public);

        return $entry;
    }

    private function insertOneFeedToken(EntityManager $em, \Pimple $DI, Feed $feed)
    {
        $token = new FeedToken();
        $token->setValue($this->container['random.low']->generateString(64, TokenManipulator::LETTERS_AND_NUMBERS));
        $token->setFeed($feed);
        $token->setUser($DI['user']);

        $feed->addToken($token);

        $em->persist($token);
        $em->persist($feed);

        return $token;
    }

    private function insertOneAggregateToken(EntityManager $em, \Pimple $DI)
    {
        $user = $DI['user'];

        $token = new AggregateToken();
        $token->setValue($this->container['random.low']->generateString(64, TokenManipulator::LETTERS_AND_NUMBERS));
        $token->setUser($user);

        $em->persist($token);
    }

    private function insertTwoTokens(EntityManager $em, \Pimple $DI)
    {
        $user = $DI['user'];

        $token = new Token();
        $token->setValue($this->container['random.low']->generateString(12, TokenManipulator::LETTERS_AND_NUMBERS));
        $token->setUser($user);
        $token->setType(TokenManipulator::TYPE_RSS);
        $token->setData('some data');
        $DI['token_1'] = $token;
        $em->persist($token);

        $token = new Token();
        $token->setValue($this->container['random.low']->generateString(12, TokenManipulator::LETTERS_AND_NUMBERS));
        $token->setUser($user);
        $token->setType(TokenManipulator::TYPE_RSS);
        $token->setData('some data');
        $token->setExpiration(new \DateTime('+1 year'));
        $DI['token_2'] = $token;
        $em->persist($token);
    }

    private function insertOneInvalidToken(EntityManager $em, \Pimple $DI)
    {
        $user = $DI['user'];

        $token = new Token();
        $token->setValue($this->container['random.low']->generateString(12, TokenManipulator::LETTERS_AND_NUMBERS));
        $token->setUser($user);
        $token->setType(TokenManipulator::TYPE_RSS);
        $token->setData('some data');
        $token->setExpiration(new \DateTime('-1 day'));
        $DI['token_invalid'] = $token;
        $em->persist($token);
    }

    private function insertOneValidationToken(EntityManager $em, \Pimple $DI)
    {
        $user = $DI['user'];

        $token = new Token();
        $token->setValue($this->container['random.low']->generateString(12, TokenManipulator::LETTERS_AND_NUMBERS));
        $token->setUser($user);
        $token->setType(TokenManipulator::TYPE_VALIDATE);
        $token->setData($DI['basket_1']->getId());
        $DI['token_validation'] = $token;
        $em->persist($token);
    }

    private function insertOneFeedItem(EntityManager $em, \Pimple $DI, FeedEntry $entry, $public)
    {
        if ($public) {
            $start = 5;
        } else {
            $start = 1;
        }
        $limit = ($start + 3);

        for ($start; $start < $limit; $start++) {
            $item = new FeedItem();
            $item->setEntry($entry);

            $actual = $DI['record_'.($start)];

            $item->setRecordId($actual->get_record_id());
            $item->setSbasId($actual->get_sbas_id());
            $item->setEntry($entry);
            $entry->addItem($item);

            $em->persist($item);
        }

        $em->persist($entry);
    }

    private function insertOneRegistration(\Pimple $DI, EntityManager $em, User $user, \collection $collection, $when, $name)
    {
        $em->getEventManager()->removeEventSubscriber(new TimestampableListener());
        $registration = new Registration();
        $registration->setCollection($collection);
        $registration->setUser($user);
        $registration->setUpdated(new \DateTime($when));
        $registration->setCreated(new \DateTime($when));
        $em->persist($registration);
        $em->getEventManager()->addEventSubscriber(new TimestampableListener());

        $DI[$name] = $registration;
    }
}
