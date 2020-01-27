<?php

namespace Alchemy\Tests\Phrasea\Core\Configuration;

use Alchemy\Phrasea\Model\Entities\User;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @group functional
 * @group legacy
 */
class DisplaySettingServiceTest extends \PhraseanetTestCase
{
    private  $userSettings = false;
    private  $appSettings = false;

    public function setUp()
    {
        parent::setUp();

        $this->userSettings = self::$DI['app']['conf']->get(['user-settings']);
        $this->appSettings = self::$DI['app']['conf']->get(['registry']);
    }

    public function tearDown()
    {
        if (is_null($this->userSettings)) {
            self::$DI['app']['conf']->remove('user-settings');
        }
        else {
            self::$DI['app']['conf']->set('user-settings', $this->userSettings);
        }

        if (is_null($this->appSettings)) {
            self::$DI['app']['conf']->remove('registry');
        }
        else {
            self::$DI['app']['conf']->set('registry', $this->appSettings);
        }

        parent::tearDownAfterClass();
    }

    public function testGetUserSetting()
    {
        self::$DI['app']['conf']->set('user-settings', [
            'images_per_page' => 42,
            'images_size'     => 666,
            'lalala'          => 'didou',
        ]);

        $user = $this->createUserMock();
        $user->expects($this->any())->method('getSettings')->will($this->returnValue(new ArrayCollection()));

        $this->assertNull(self::$DI['app']['settings']->getUserSetting($user, 'lalala'));
        $this->assertSame($default = 'toto', self::$DI['app']['settings']->getUserSetting($user, 'lilili', $default));
        $this->assertSame(666, self::$DI['app']['settings']->getUserSetting($user, 'images_size'));
        $this->assertSame(42, self::$DI['app']['settings']->getUserSetting($user, 'images_per_page'));
        $this->assertSame(self::$DI['app']['settings']->getUsersSettings()['editing_top_box'], self::$DI['app']['settings']->getUserSetting($user, 'editing_top_box'));
    }

    public function testGetApplicationSettings()
    {
        self::$DI['app']['conf']->set('registry', [
            'int' => 42,
            'null' => null,
            'string' => 'didou',
            'true' => true,
            'false' => false,
        ]);

        $this->assertNull(self::$DI['app']['settings']->getApplicationSetting('null'));
        $this->assertNull(self::$DI['app']['settings']->getApplicationSetting('does_not_exists'));
        $this->assertSame($default = 'toto', self::$DI['app']['settings']->getApplicationSetting('does_not_exists', $default));
        $this->assertSame(42, self::$DI['app']['settings']->getApplicationSetting('int'));
        $this->assertSame('didou', self::$DI['app']['settings']->getApplicationSetting('string'));
        $this->assertFalse(self::$DI['app']['settings']->getApplicationSetting('false'));
        $this->assertTrue(self::$DI['app']['settings']->getApplicationSetting('true'));
    }
}
