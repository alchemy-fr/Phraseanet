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

        $creator = new AccountCreator($random, $appbox, $enabled, array());

        $this->assertSame($enabled, $creator->isEnabled());
    }

    public function provideEnabledOptions()
    {
        return array(
            array(true),
            array(false),
        );
    }

    /**
     * @expectedException \Alchemy\Phrasea\Exception\InvalidArgumentException
     */
    public function testCreateWithAnExistingMail()
    {
        $random = $this->createRandomMock();
        $appbox = $this->createAppboxMock();

        $creator = new AccountCreator($random, $appbox, true, array());
        $creator->create(self::$DI['app'], self::$DI['app']['tokens']->generatePassword(), self::$DI['user']->getEmail());
    }

    /**
     * @expectedException \Alchemy\Phrasea\Exception\RuntimeException
     */
    public function testCreateWithDisabledCreator()
    {
        $random = $this->createRandomMock();
        $appbox = $this->createAppboxMock();

        $creator = new AccountCreator($random, $appbox, false, array());
        $creator->create(self::$DI['app'], self::$DI['app']['tokens']->generatePassword());
    }

    public function testCreateWithoutTemplates()
    {
        $creator = new AccountCreator(self::$DI['app']['tokens'], self::$DI['app']['phraseanet.appbox'], true, array());
        $user = $creator->create(self::$DI['app'], self::$DI['app']['tokens']->generatePassword());

        $this->assertInstanceOf('Alchemy\Phrasea\Model\Entities\User', $user);
        $user->delete();
    }

    public function testCreateWithTemplates()
    {
        $random = self::$DI['app']['tokens'];
        $template1 = self::$DI['app']['manipulator.user']->createUser('template' . $random->generatePassword(), $random->generatePassword());
        $template1->set_template(self::$DI['user']);
        $template2 = self::$DI['app']['manipulator.user']->createUser('template' . $random->generatePassword(), $random->generatePassword());
        $template2->set_template(self::$DI['user']);
        $template3 = self::$DI['app']['manipulator.user']->createUser('template' . $random->generatePassword(), $random->generatePassword());
        $template3->set_template(self::$DI['user']);

        $templates = array($template1, $template2);
        $extra = array($template3);

        $creator = new AccountCreator($random, self::$DI['app']['phraseanet.appbox'], true, $templates);
        $user = $creator->create(self::$DI['app'], self::$DI['app']['tokens']->generatePassword(), null, $extra);

        $this->assertInstanceOf('Alchemy\Phrasea\Model\Entities\User', $user);
        $user->delete();
        $template1->delete();
        $template2->delete();
        $template3->delete();
    }

    public function testCreateWithAlreadyExistingLogin()
    {
        $creator = new AccountCreator(self::$DI['app']['tokens'], self::$DI['app']['phraseanet.appbox'], true, array());
        $user = $creator->create(self::$DI['app'], self::$DI['user']->getLogin());

        $this->assertInstanceOf('Alchemy\Phrasea\Model\Entities\User', $user);
        $this->assertNotEquals(self::$DI['user']->getLogin(), $user->getLogin());
        $user->delete();
    }
}
