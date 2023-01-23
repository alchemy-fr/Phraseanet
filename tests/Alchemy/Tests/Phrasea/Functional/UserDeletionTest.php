<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Tests\Phrasea\Functional;
use Alchemy\Phrasea\ControllerProvider\Api\V2;
use Alchemy\Phrasea\Model\Entities\ApiAccount;
use Alchemy\Phrasea\Model\Entities\ApiApplication;
use Alchemy\Phrasea\Model\Entities\User;
use Alchemy\Phrasea\Model\Manipulator\ApiAccountManipulator;
use Alchemy\Phrasea\Model\Manipulator\ApiApplicationManipulator;
use Alchemy\Phrasea\Model\Manipulator\ApiLogManipulator;
use Alchemy\Phrasea\Model\Manipulator\UserManipulator;
use Alchemy\Phrasea\Model\Repositories\ApiLogRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @group functional
 * @group authenticated
 * @group web
 */
class UserDeletionTest extends \PhraseanetAuthenticatedWebTestCase
{
    /** @var UserManipulator */
    private $userManipulator;
    /** @var User */
    private $user;
    /** @var ApiApplicationManipulator */
    private $apiApplicationManipulator;
    /** @var ApiApplication */
    private $apiApplication;

    public function setUp()
    {
        parent::setUp();

        $app = $this->getApplication();

        $this->userManipulator = $app['manipulator.user'];

        $this->user = $this->userManipulator->createUser('login', "test", 'test@example.com');

        $this->apiApplicationManipulator = $app['manipulator.api-application'];

        $this->apiApplication = $this->apiApplicationManipulator->create(
            'test-desktop',
            ApiApplication::WEB_TYPE,
            '',
            'http://website.com/',
            $this->user,
            'http://callback.com/callback/'
        );
        $this->apiApplication->setGrantPassword(true);
        $this->apiApplicationManipulator->update($this->apiApplication);
    }

    /**
     * @see https://phraseanet.atlassian.net/browse/PHRAS-811
     */
    public function testRemoveUserWhichLoggedViaOAuthDoesNotThrowException()
    {
        $app = $this->getApplication();
        /** @var ApiLogManipulator $apiLogManipulator */
        $apiLogManipulator = $app['manipulator.api-log'];
        /** @var ApiLogRepository $apiLogRepository */
        $apiLogRepository = $app['repo.api-logs'];
        /** @var ApiAccountManipulator $apiAccountManipulator */
        $apiAccountManipulator = $app['manipulator.api-account'];

        $account = $apiAccountManipulator->create($this->apiApplication, $this->user, V2::VERSION);
        $this->assertInstanceOf(ApiAccount::class, $account);

        $apiLog = $apiLogManipulator->create($account, new Request(), new Response());
        $apiLogId = $apiLog->getId();

        $this->userManipulator->delete($this->user);
        $this->assertTrue($this->user->isDeleted(), 'User was not properly deleted');

        $apiLogRepository->clear();
        $this->assertNull($apiLogRepository->find($apiLogId), 'Created api log should have been deleted');
        $this->user = null;
        $this->apiApplication = null;
    }

    /**
     * @see https://phraseanet.atlassian.net/browse/PHRAS-812
     */
    public function testRemoveUserShouldChangeLogin()
    {
        $this->userManipulator->delete($this->user);

        $this->assertNotEquals('login', $this->user->getLogin());
    }

    public function tearDown()
    {
        if ($this->apiApplication) {
            $this->apiApplicationManipulator->delete($this->apiApplication);
        }
        if ($this->user) {
            $this->userManipulator->delete($this->user);
        }

        parent::tearDown();
    }
}
