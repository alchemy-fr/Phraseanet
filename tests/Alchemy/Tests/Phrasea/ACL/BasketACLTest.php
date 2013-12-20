<?php

namespace Alchemy\Tests\Phrasea\ACL;

use Alchemy\Phrasea\ACL\BasketACL;

class BasketACLTest extends \PhraseanetTestCase
{
    public function testOwnerIsOwner()
    {
        $basket = self::$DI['app']['EM']->find('Alchemy\Phrasea\Model\Entities\Basket', 4);
        $this->assertTrue((new BasketACL())->isOwner($basket, self::$DI['user']));
    }

    public function testParticipantIsNotAnOwner()
    {
        $basket = self::$DI['app']['EM']->find('Alchemy\Phrasea\Model\Entities\Basket', 4);
        $this->assertFalse((new BasketACL())->isOwner($basket, self::$DI['user_alt1']));
    }

    public function testUserIsNotTheOwner()
    {
        $basket = self::$DI['app']['EM']->find('Alchemy\Phrasea\Model\Entities\Basket', 1);
        $this->assertFalse((new BasketACL())->isOwner($basket, self::$DI['user_alt1']));
    }

    public function testOwnerHasAccessInValidationEnv()
    {
        $basket = self::$DI['app']['EM']->find('Alchemy\Phrasea\Model\Entities\Basket', 4);
        $this->assertTrue((new BasketACL())->hasAccess($basket, self::$DI['user']));
    }

    public function testOwnerHasAccess()
    {
        $basket = self::$DI['app']['EM']->find('Alchemy\Phrasea\Model\Entities\Basket', 1);
        $this->assertTrue((new BasketACL())->hasAccess($basket, self::$DI['user']));
    }

    public function testParticipantHasAccess()
    {
        $basket = self::$DI['app']['EM']->find('Alchemy\Phrasea\Model\Entities\Basket', 4);
        $this->assertTrue((new BasketACL())->hasAccess($basket, self::$DI['user_alt1']));
    }

    public function testUserHasNotAccess()
    {
        $basket = self::$DI['app']['EM']->find('Alchemy\Phrasea\Model\Entities\Basket', 1);
        $this->assertFalse((new BasketACL())->hasAccess($basket, self::$DI['user_alt1']));
    }
}
