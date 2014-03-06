<?php

class api_oauthv2_TokenTest extends \PhraseanetTestCase
{
    /**
     * @var API_OAuth2_Token
     */
    protected $object;

    public function setUp()
    {
        parent::setUp();
        $account = self::$DI['app']['repo.api-accounts']->findByUserAndApplication(self::$DI['user'], self::$DI['oauth2-app-user']);

        try {
            new API_OAuth2_Token(self::$DI['app']['phraseanet.appbox'], $account, self::$DI['app']['random.medium']);
            $this->fail();
        } catch (Exception $e) {

        }

        $this->object = API_OAuth2_Token::create(self::$DI['app']['phraseanet.appbox'], $account, self::$DI['app']['random.medium']);
    }

    public function tearDown()
    {
        $this->object->delete();
        parent::tearDown();
    }

    private function assertmd5($md5)
    {
        $this->assertTrue((count(preg_match('/[a-z0-9]{32}/', $md5)) === 1));
    }

    public function testGettersAndSetters()
    {
        $this->assertmd5($this->object->get_value());

        $value = md5('prout');
        $this->object->set_value($value);
        $this->assertEquals($value, $this->object->get_value());

        $this->object->set_session_id(null);
        $this->assertNull($this->object->get_session_id());

        $this->object->set_session_id(458);
        $this->assertEquals(458, $this->object->get_session_id());

        $expire = time() + 3600;
        $this->object->set_expires($expire);
        $diff = (int) $this->object->get_expires() - time();
        $this->assertSame($expire, $this->object->get_expires(), "expiration timestamp is string : " . $this->object->get_expires());
        $this->assertTrue($diff > 3500, "expire value $diff should be more than 3500 seconds ");
        $this->assertTrue($diff < 3700, "expire value $diff should be less than 3700 seconds ");

        $date = time() + 7200;
        $this->object->set_expires($date);
        $this->assertEquals($date, $this->object->get_expires());

        $this->assertNull($this->object->get_scope());

        $this->assertNull($this->object->get_scope());
        $scope = "prout";
        $this->object->set_scope($scope);
        $this->assertEquals($scope, $this->object->get_scope());

        $this->assertInstanceOf('ApiApplication', $this->object->get_account());
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
        $loaded = API_OAuth2_Token::load_by_oauth_token(self::$DI['app'], $token);
        $this->assertInstanceOf('API_OAuth2_Token', $loaded);
        $this->assertEquals($this->object, $loaded);
    }
}
