<?php

namespace Alchemy\Tests\Phrasea\Authentication;

use Alchemy\Phrasea\Authentication\AccountCreator;

class AccountCreatorTest extends \PhraseanetTestCase
{
    /**
     * @dataProvider provideEnabledOptions
     */
    public function testIsEnabled($enabled)
    {
        $random = $this->createRandomMock();
        $appbox = $this->createAppboxMock();

        $creator = new AccountCreator($random, $appbox, $enabled, []);

        $this->assertSame($enabled, $creator->isEnabled());
    }

    public function provideEnabledOptions()
    {
        return [
            [true],
            [false],
        ];
    }

    /**
     * @expectedException \Alchemy\Phrasea\Exception\InvalidArgumentException
     */
    public function testCreateWithAnExistingMail()
    {
        $random = $this->createRandomMock();
        $appbox = $this->createAppboxMock();

        $creator = new AccountCreator($random, $appbox, true, []);
        $creator->create(self::$DI['app'], self::$DI['app']['tokens']->generatePassword(), self::$DI['user']->getEmail());
    }

    /**
     * @expectedException \Alchemy\Phrasea\Exception\RuntimeException
     */
    public function testCreateWithDisabledCreator()
    {
        $random = $this->createRandomMock();
        $appbox = $this->createAppboxMock();

        $creator = new AccountCreator($random, $appbox, false, []);
        $creator->create(self::$DI['app'], self::$DI['app']['tokens']->generatePassword());
    }

    public function testCreateWithoutTemplates()
    {
        $creator = new AccountCreator(self::$DI['app']['tokens'], self::$DI['app']['phraseanet.appbox'], true, []);
        $user = $creator->create(self::$DI['app'], self::$DI['app']['tokens']->generatePassword());

        $this->assertInstanceOf('Alchemy\Phrasea\Model\Entities\User', $user);

        self::$DI['app']['manipulator.user']->delete($user);
    }

    public function testCreateWithTemplates()
    {
        $template1 = self::$DI['app']['manipulator.user']->createTemplate('template1', self::$DI['user']);
        $template2 = self::$DI['app']['manipulator.user']->createTemplate('template2', self::$DI['user']);
        $template3 = self::$DI['app']['manipulator.user']->createTemplate('template3', self::$DI['user']);

        $templates = [$template1, $template2];
        $extra = [$template3];

        $creator = new AccountCreator(self::$DI['app']['tokens'], self::$DI['app']['phraseanet.appbox'], true, $templates);
        $user = $creator->create(self::$DI['app'], self::$DI['app']['tokens']->generatePassword(), null, $extra);

        $this->assertInstanceOf('Alchemy\Phrasea\Model\Entities\User', $user);
        self::$DI['app']['manipulator.user']->delete($user);
        self::$DI['app']['manipulator.user']->delete($template1);
        self::$DI['app']['manipulator.user']->delete($template2);
        self::$DI['app']['manipulator.user']->delete($template3);
    }

    public function testCreateWithAlreadyExistingLogin()
    {
        $creator = new AccountCreator(self::$DI['app']['tokens'], self::$DI['app']['phraseanet.appbox'], true, []);
        $user = $creator->create(self::$DI['app'], self::$DI['user']->getLogin());

        $this->assertInstanceOf('Alchemy\Phrasea\Model\Entities\User', $user);
        $this->assertNotEquals(self::$DI['user']->getLogin(), $user->getLogin());
        self::$DI['app']['manipulator.user']->delete($user);
    }
}
