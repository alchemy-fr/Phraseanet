<?php

use Alchemy\Phrasea\Core\Configuration;

require_once __DIR__ . '/../../PhraseanetPHPUnitAbstract.class.inc';

class API_OAuth2_TokenTest extends PhraseanetPHPUnitAbstract
{

    /**
     * @var API_OAuth2_Token
     */
    protected $object;

    public function setUp()
    {
        parent::setUp();
        $appbox = self::$application['phraseanet.appbox'];
        $this->application = API_OAuth2_Application::create(self::$application, self::$user, 'test app');
        $account = API_OAuth2_Account::load_with_user(self::$application, $this->application, self::$user);

        try {
            new API_OAuth2_Token($appbox, $account);
            $this->fail();
        } catch (Exception $e) {

        }

        $this->object = API_OAuth2_Token::create($appbox, $account);
    }

    public function tearDown()
    {
        $this->application->delete();
        parent::tearDown();
    }

    protected function assertmd5($md5)
    {
        $this->assertTrue((count(preg_match('/[a-z0-9]{32}/', $md5)) === 1));
    }

    public function testGet_value()
    {
        $this->assertmd5($this->object->get_value());
    }

    public function testSet_value()
    {
        $value = md5('prout');
        $this->object->set_value($value);
        $this->assertEquals($value, $this->object->get_value());
    }

    public function testGet_session_id()
    {
        $this->assertNull($this->object->get_session_id());
    }

    public function testSet_session_id()
    {
        $this->object->set_session_id(458);
        $this->assertEquals(458, $this->object->get_session_id());
    }

    public function testGet_expires()
    {
        $diff = (int) $this->object->get_expires() - time();
        $this->assertInternalType('string', $this->object->get_expires(), "expiration timestamp is string : " . $this->object->get_expires());
        $this->assertTrue($diff > 3500, "expire value $diff should be more than 3500 seconds ");
        $this->assertTrue($diff < 3700, "expire value $diff should be less than 3700 seconds ");
    }

    public function testSet_expires()
    {
        $date = time() + 7200;
        $this->object->set_expires($date);
        $this->assertEquals($date, $this->object->get_expires());
    }

    public function testGet_scope()
    {
        $this->assertNull($this->object->get_scope());
    }

    public function testset_scope()
    {
        $this->assertNull($this->object->get_scope());
        $scope = "prout";
        $this->object->set_scope($scope);
        $this->assertEquals($scope, $this->object->get_scope());
    }

    public function testGet_account()
    {
        $this->assertInstanceOf('API_OAuth2_Account', $this->object->get_account());
    }

    public function testRenew()
    {
        $first = $this->object->get_value();
        $this->assertMd5($first);
        $this->object->renew();
        $second = $this->object->get_value();
        $this->assertMd5($second);
        $this->assertNotEquals($second, $first);
    }

    public function testLoad_by_oauth_token()
    {
        $token = $this->object->get_value();
        $loaded = API_OAuth2_Token::load_by_oauth_token(self::$application, $token);
        $this->assertInstanceOf('API_OAuth2_Token', $loaded);
        $this->assertEquals($this->object, $loaded);
    }

    public function testGenerate_token()
    {
        for ($i = 0; $i < 100; $i ++ ) {
            $this->assertMd5(API_OAuth2_Token::generate_token());
        }
    }
}
