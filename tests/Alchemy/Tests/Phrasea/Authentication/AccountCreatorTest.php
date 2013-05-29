<?php

namespace Alchemy\Tests\Phrasea\Authentication;

use Alchemy\Phrasea\Authentication\AccountCreator;

class AccountCreatorTest extends \PhraseanetPHPUnitAbstract
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
     * @expectedException Alchemy\Phrasea\Exception\InvalidArgumentException
     */
    public function testCreateWithAnExistingMail()
    {
        $random = $this->createRandomMock();
        $appbox = $this->createAppboxMock();

        $creator = new AccountCreator($random, $appbox, true, array());
        $creator->create(self::$DI['app'], self::$DI['app']['tokens']->generatePassword(), self::$DI['user']->get_email());
    }

    /**
     * @expectedException Alchemy\Phrasea\Exception\RuntimeException
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

        $this->assertInstanceOf('User_Adapter', $user);
        $user->delete();
    }

    public function testCreateWithTemplates()
    {
        $random = self::$DI['app']['tokens'];
        $template1 = \User_Adapter::create(self::$DI['app'], 'template' . $random->generatePassword(), $random->generatePassword(), null, false);
        $template1->set_template(self::$DI['user']);
        $template2 = \User_Adapter::create(self::$DI['app'], 'template' . $random->generatePassword(), $random->generatePassword(), null, false);
        $template2->set_template(self::$DI['user']);
        $template3 = \User_Adapter::create(self::$DI['app'], 'template' . $random->generatePassword(), $random->generatePassword(), null, false);
        $template3->set_template(self::$DI['user']);

        $templates = array($template1, $template2);
        $extra = array($template3);

        $creator = new AccountCreator($random, self::$DI['app']['phraseanet.appbox'], true, $templates);
        $user = $creator->create(self::$DI['app'], self::$DI['app']['tokens']->generatePassword(), null, $extra);

        $this->assertInstanceOf('User_Adapter', $user);
        $user->delete();
        $template1->delete();
        $template2->delete();
        $template3->delete();
    }

    public function testCreateWithAlreadyExistingLogin()
    {
        $creator = new AccountCreator(self::$DI['app']['tokens'], self::$DI['app']['phraseanet.appbox'], true, array());
        $user = $creator->create(self::$DI['app'], self::$DI['user']->get_login());

        $this->assertInstanceOf('User_Adapter', $user);
        $this->assertNotEquals(self::$DI['user']->get_login(), $user->get_login());
        $user->delete();
    }
}
