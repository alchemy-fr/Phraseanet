<?php

use Alchemy\Phrasea\Core\Configuration;

require_once __DIR__ . '/../../PhraseanetPHPUnitAbstract.class.inc';

class API_OAuth2_RefreshTokenTest extends PhraseanetPHPUnitAbstract
{
    /**
     * @var API_OAuth2_RefreshToken
     */
    protected $object;
    protected $token;
    protected $scope;

    protected $account;

    public function setUp()
    {
        parent::setUp();
        $appbox = self::$application['phraseanet.appbox'];
        $this->application = API_OAuth2_Application::create(self::$application, self::$DI['user'], 'test app');
        $this->account = API_OAuth2_Account::load_with_user(self::$application, $this->application, self::$DI['user']);

        $expires = time() + 100;
        $this->token = random::generatePassword(8);
        $this->scope = 'scopidou';

        $this->object = API_OAuth2_RefreshToken::create(self::$application, $this->account, $expires, $this->token, $this->scope);
    }

    public function tearDown()
    {
        $this->application->delete();
        parent::tearDown();
    }

    public function testGet_value()
    {
        $this->assertEquals($this->token, $this->object->get_value());
    }

    /**
     * @todo Implement testGet_account().
     */
    public function testGet_account()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @todo Implement testGet_expires().
     */
    public function testGet_expires()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @todo Implement testGet_scope().
     */
    public function testGet_scope()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @todo Implement testDelete().
     */
    public function testDelete()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @todo Implement testLoad_by_account().
     */
    public function testLoad_by_account()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @todo Implement testCreate().
     */
    public function testCreate()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }
}
