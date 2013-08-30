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

class UserTest extends \PHPUnit_Framework_TestCase
{
    private $user;

    public function setUp()
    {
        $this->user = new User();
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
        $this->user->setModelOf(1);
        $this->assertTrue($this->user->isTemplate());
    }

    public function testIsSpecial()
    {
        $this->user->setLogin('login');
        $this->assertFalse($this->user->isSpecial());
        $this->user->setLogin('invite');
        $this->assertTrue($this->user->isSpecial());
        $this->user->setLogin('login');
        $this->assertFalse($this->user->isSpecial());
        $this->user->setLogin('autoregister');
        $this->assertTrue($this->user->isSpecial());
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
