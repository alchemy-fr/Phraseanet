<?php

class api_oauthv2_AccountTest extends \PhraseanetTestCase
{
    /**
     * @var ApiApplication
     */
    protected $object;

    public function setUp()
    {
        parent::setUp();
        $this->object = self::$DI['app']['repo.api-accounts']->findByUserAndApplication(self::$DI['user'], self::$DI['oauth2-app-user']);
    }

    public function testGettersAndSetters()
    {
        $this->assertTrue(is_int($this->object->getId()));
        $this->assertInstanceOf('Alchemy\Phrasea\Model\Entities\User', $this->object->getUser());
        $this->assertEquals(self::$DI['user']->getId(), $this->object->getUser()->getId());

        $this->assertEquals('1.0', $this->object->getApiVersion());

        $this->assertTrue(is_bool($this->object->isRevoked()));

        $this->object->set_revoked(true);
        $this->assertTrue($this->object->isRevoked());
        $this->object->set_revoked(false);
        $this->assertFalse($this->object->isRevoked());

        $this->assertInstanceOf('DateTime', $this->object->getCreated());
        $this->assertInstanceOf('ApiApplication', $this->object->getApplication());
        $this->assertEquals(self::$DI['oauth2-app-user'], $this->object->getApplication());
    }

    public function testLoad_with_user()
    {
        $loaded = self::$DI['app']['repo.api-accounts']->findByUserAndApplication(self::$DI['user'], self::$DI['oauth2-app-user']);
        $this->assertInstanceOf('ApiAccount', $loaded);
        $this->assertEquals($this->object, $loaded);
    }
}
