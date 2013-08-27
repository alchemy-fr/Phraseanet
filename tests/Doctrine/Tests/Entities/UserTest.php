<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Doctrine\Tests\Entities;

use Alchemy\Phrasea\Application;
use Entities\User;

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
        $this->assertNotNull($this->user->getNonce());
        $this->assertInstanceOf('\Entities\FtpCredential', $this->user->getFtpCredential());
        $this->assertInstanceOf('\Doctrine\Common\Collections\ArrayCollection', $this->user->getQueries());
        $this->assertInstanceOf('\Doctrine\Common\Collections\ArrayCollection', $this->user->getNotificationSettings());
        $settings = $this->user->getSettings();
        $this->assertInstanceOf('\Doctrine\Common\Collections\ArrayCollection', $settings);
        $this->assertGreaterThan(0, $settings->count());
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

    public function testInvalidGeonamedId()
    {
        $this->setExpectedException(
            'Alchemy\Phrasea\Exception\InvalidArgumentException',
            'Invalid geonameid -1.'
        );
        $this->user->setGeonameId(-1);
    }

    public function testInvalidLogin()
    {
        $this->setExpectedException(
            'Alchemy\Phrasea\Exception\InvalidArgumentException',
            'Invalid login.'
        );
        $this->user->setLogin('');
    }

    public function testInvalidEmail()
    {
        $this->setExpectedException(
            'Alchemy\Phrasea\Exception\InvalidArgumentException',
            'Invalid email.'
        );
        $this->user->setEmail('');
    }

    public function testValidEmail()
    {
        $this->user->setEmail('aa@aa.fr');
        $this->assertEquals('aa@aa.fr', $this->user->getEmail());
    }

    public function testInvalidPassword()
    {
        $this->setExpectedException(
            'Alchemy\Phrasea\Exception\InvalidArgumentException',
            'Invalid password.'
        );
        $this->user->setPassword('');
    }

    public function testGetDisplayName()
    {
        $this->user->setLogin('login');
        $this->user->setFirstName('firstname');
        $this->user->setLastName('lastname');
        $this->user->setEmail('email@email.com');
        $this->assertEquals($this->user->getDisplayName(), 'firstname lastname');
        $this->user->setLastName('');
        $this->assertEquals($this->user->getDisplayName(), 'firstname');
        $this->user->setFirstName('');
        $this->assertEquals($this->user->getDisplayName(), 'email@email.com');
        $this->user->setEmail(null);
        $this->assertEquals($this->user->getDisplayName(), 'Unnamed user');
        $this->user->setLastName('lastname');
        $this->assertEquals($this->user->getDisplayName(), 'lastname');
    }

    public function testIsTemplate()
    {
        $this->assertFalse($this->user->isTemplate());
        $template = new User();
        $template->setLogin('login2');
        $template->setPassword('toto');
        $this->insertOneUser($template);
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

    public function testIsUSer()
    {
        $this->user->setLogin('login');
        $this->user->setPassword('toto');
        $this->assertFalse($this->user->isUser(null));
        $this->insertOneUser($this->user);
        $user = new User();
        $user->setLogin('login2');
        $user->setPassword('toto');
        $this->insertOneUser($user);
        $this->assertFalse($user->isUser($this->user));
        $this->asserttrue($this->user->isUser($this->user));
    }

    public function testReset()
    {
        $this->user->setCity('city');
        $this->user->setAddress('address');
        $this->user->setCountry('country');
        $this->user->setZipCode('zipcode');
        $this->user->setTimezone('timezone');
        $this->user->setCompany('company');
        $this->user->setEmail('email@email.com');
        $this->user->setFax('fax');
        $this->user->setPhone('phone');
        $this->user->setFirstName('firstname');
        $this->user->setGender(User::GENDER_MR);
        $this->user->setGeonameId(1);
        $this->user->setJob('job');
        $this->user->setActivity('activity');
        $this->user->setLastName('lastname');
        $this->user->setMailNotificationsActivated(true);
        $this->user->setRequestNotificationsActivated(true);

        $this->user->reset();

        $this->assertEmpty($this->user->getCity());
        $this->assertEmpty($this->user->getAddress());
        $this->assertEmpty($this->user->getCountry());
        $this->assertEmpty($this->user->getZipCode());
        $this->assertEmpty($this->user->getTimezone());
        $this->assertEmpty($this->user->getCompany());
        $this->assertEmpty($this->user->getFax());
        $this->assertEmpty($this->user->getPhone());
        $this->assertEmpty($this->user->getFirstName());
        $this->assertEmpty($this->user->getJob());
        $this->assertEmpty($this->user->getActivity());
        $this->assertEmpty($this->user->getLastName());
        $this->assertNull($this->user->getEmail());
        $this->assertNull($this->user->getGeonameId());
        $this->assertNull($this->user->getGender());
        $this->assertFalse($this->user->hasMailNotificationsActivated());
        $this->assertFalse($this->user->hasRequestNotificationsActivated());
    }

    public function testSetModelOf()
    {
        $this->user->setLogin('login');
        $this->user->setPassword('toto');
        $user = new User();
        $user->setLogin('login2');
        $user->setPassword('toto');
        $this->insertOneUser($this->user);
        $this->insertOneUser($user);

        $this->user->setModelOf($user);
        $this->assertEquals('login2', $this->user->getModelOf()->getLogin());
        $this->setExpectedException('Alchemy\Phrasea\Exception\InvalidArgumentException');
        $this->user->setModelOf($this->user);
    }

    public function genderProvider()
    {
        return array(
            array(null),
            array(User::GENDER_MISS),
            array(User::GENDER_MR),
            array(User::GENDER_MR),
        );
    }

    public function invalidGenderProvider()
    {
        return array(
            array(false),
            array(''),
            array(1),
            array('madame')
        );
    }
}
