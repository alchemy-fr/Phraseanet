<?php

namespace Alchemy\Tests\Phrasea\Model\Entities;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Model\Entities\User;

class UserTest extends \PhraseanetPHPUnitAbstract
{
    private $user;

    public function setUp()
    {
        parent::setUp();
        $this->user = new User();
    }

    public function testConstructor()
    {
        $this->assertInstanceOf('\Doctrine\Common\Collections\ArrayCollection', $this->user->getQueries());
        $this->assertInstanceOf('\Doctrine\Common\Collections\ArrayCollection', $this->user->getNotificationSettings());
        $this->assertInstanceOf('\Doctrine\Common\Collections\ArrayCollection', $this->user->getSettings());
    }

    /**
     * @dataProvider genderProvider
     */
    public function testSetGender($gender)
    {
        $this->user->setGender($gender);
        $this->assertEquals($this->user->getGender(), $gender);
    }

    /**
     * @dataProvider invalidGenderProvider
     */
    public function testInvalidSetGender($gender)
    {
        $this->setExpectedException(
            'Alchemy\Phrasea\Exception\InvalidArgumentException',
            'Invalid gender '. (string) $gender . '.'
        );
        $this->user->setGender($gender);
    }

    public function testSetLocale()
    {
        foreach (array_keys(Application::getAvailableLanguages()) as $locale) {
            $this->user->setLocale($locale);
            $this->assertEquals($this->user->getLocale(), $locale);
        }

        $this->user->setLocale(null);
        $this->assertEquals($this->user->getLocale(), null);
    }

    public function testInvalidLocale()
    {
        $this->setExpectedException(
            'Alchemy\Phrasea\Exception\InvalidArgumentException',
            'Invalid locale invalid_local.'
        );
        $this->user->setLocale('invalid_local');
    }

    public function testSetGeonameId()
    {
        $this->user->setGeonameId(1234);
        $this->assertEquals($this->user->getGeonameId(), 1234);
        $this->user->setGeonameId(null);
        $this->assertEquals($this->user->getGeonameId(), null);
    }

    public function testValidEmail()
    {
        $this->user->setEmail('aa@aa.fr');
        $this->assertEquals('aa@aa.fr', $this->user->getEmail());
    }

    public function testGetDisplayName()
    {
        $this->user->setLogin('login');
        $this->user->setFirstName('firstname');
        $this->user->setLastName('lastname');
        $this->user->setEmail('email@email.com');
        $this->assertEquals($this->user->getDisplayName(self::$DI['app']['translator']), 'firstname lastname');
        $this->user->setLastName('');
        $this->assertEquals($this->user->getDisplayName(self::$DI['app']['translator']), 'firstname');
        $this->user->setFirstName('');
        $this->assertEquals($this->user->getDisplayName(self::$DI['app']['translator']), 'email@email.com');
        $this->user->setEmail(null);
        $this->assertEquals($this->user->getDisplayName(self::$DI['app']['translator']), 'Unnamed user');
        $this->user->setLastName('lastname');
        $this->assertEquals($this->user->getDisplayName(self::$DI['app']['translator']), 'lastname');
    }

    public function testIsTemplate()
    {
        $this->assertFalse($this->user->isTemplate());
        $template = $this->insertOneUser('login2');
        $this->user->setModelOf($template);
        $this->assertTrue($this->user->isTemplate());
    }

    public function testIsSpecial()
    {
        $this->user->setLogin('login');
        $this->assertFalse($this->user->isSpecial());
        $this->user->setLogin(User::USER_AUTOREGISTER);
        $this->assertTrue($this->user->isSpecial());
        $this->user->setLogin('login');
        $this->assertFalse($this->user->isSpecial());
        $this->user->setLogin(User::USER_GUEST);
        $this->assertTrue($this->user->isSpecial());
    }

    public function testSetModelOf()
    {
        $template = $this->insertOneUser('login');
        $user = $this->insertOneUser('login2');
        $template->setModelOf($user);
        $this->assertEquals('login2', $template->getModelOf()->getLogin());
    }

    public function genderProvider()
    {
        return [
            [null],
            [User::GENDER_MISS],
            [User::GENDER_MR],
            [User::GENDER_MR],
        ];
    }

    public function invalidGenderProvider()
    {
        return [
            [false],
            [''],
            [1],
            ['madame']
        ];
    }
}
