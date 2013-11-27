<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Tests\Phrasea\Configuration;

use Alchemy\Phrasea\Model\Entities\User;

class DisplaySettingTest extends \PhraseanetPHPUnitAbstract
{
    private static $userSettings;

    public function setUp()
    {
        parent::setUp();

        if (null === self::$userSettings) {
            self::$userSettings = self::$DI['app']['conf']->get(['user-settings']);
        }
    }

    public static function tearDownAfterClass()
    {
        if (null === self::$userSettings) {
            self::$DI['app']['conf']->remove('user-settings');
        } else {
            self::$DI['app']['conf']->set('user-settings', self::$userSettings);
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

        $user = self::$DI['app']['manipulator.user']->createUser('login', 'password');

        $this->assertNull(self::$DI['app']['settings']->getUserSetting($user, 'lalala'));
        $this->assertSame($default = 'toto', self::$DI['app']['settings']->getUserSetting($user, 'lilili', $default));
        $this->assertSame(666, self::$DI['app']['settings']->getUserSetting($user, 'images_size'));
        $this->assertSame(42, self::$DI['app']['settings']->getUserSetting($user, 'images_per_page'));
        $this->assertSame(self::$DI['app']['settings']->getDefaultUserSettings()['editing_top_box'], self::$DI['app']['settings']->getUserSetting($user, 'editing_top_box'));
    }
}
