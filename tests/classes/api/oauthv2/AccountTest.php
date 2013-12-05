<?php

class api_oauthv2_AccountTest extends \PhraseanetTestCase
{
    /**
     * @var API_OAuth2_Account
     */
    protected $object;

    public function setUp()
    {
        parent::setUp();
        $this->object = API_OAuth2_Account::load_with_user(self::$DI['app'], self::$DI['oauth2-app-user'], self::$DI['user']);
    }

    public function testGettersAndSetters()
    {
        $this->assertTrue(is_int($this->object->get_id()));
        $this->assertInstanceOf('Alchemy\Phrasea\Model\Entities\User', $this->object->get_user());
        $this->assertEquals(self::$DI['user']->getId(), $this->object->get_user()->getId());

        $this->assertEquals('1.0', $this->object->get_api_version());

        $this->assertTrue(is_bool($this->object->is_revoked()));

        $this->object->set_revoked(true);
        $this->assertTrue($this->object->is_revoked());
        $this->object->set_revoked(false);
        $this->assertFalse($this->object->is_revoked());

        $this->assertInstanceOf('DateTime', $this->object->get_created_on());

        $this->assertInstanceOf('API_OAuth2_Token', $this->object->get_token());

        $this->assertInstanceOf('API_OAuth2_Application', $this->object->get_application());
        $this->assertEquals(self::$DI['oauth2-app-user'], $this->object->get_application());
    }

    public function testLoad_with_user()
    {
        $loaded = API_OAuth2_Account::load_with_user(self::$DI['app'], self::$DI['oauth2-app-user'], self::$DI['user']);
        $this->assertInstanceOf('API_OAuth2_Account', $loaded);
        $this->assertEquals($this->object, $loaded);
    }
}
