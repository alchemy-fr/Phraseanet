<?php

namespace Alchemy\Tests\Phrasea\ACL;

use Alchemy\Phrasea\ACL\BasketACL;

class BasketACLTest extends \PhraseanetTestCase
{
    public function testOwnerIsOwner()
    {
        $basket = $this->insertOneBasketEnv();

        $acl = new BasketACL();
        $this->assertTrue($acl->isOwner($basket, self::$DI['user']));
    }

    public function testParticipantIsNotAnOwner()
    {
        $basket = $this->insertOneBasketEnv();

        $acl = new BasketACL();
        $this->assertFalse($acl->isOwner($basket, self::$DI['user_alt1']));
    }

    public function testUserIsNotTheOwner()
    {
        $basket = $this->insertOneBasket();

        $acl = new BasketACL();
        $this->assertFalse($acl->isOwner($basket, self::$DI['user_alt1']));
    }

    public function testOwnerHasAccessInValidationEnv()
    {
        $basket = $this->insertOneBasketEnv();

        $acl = new BasketACL();
        $this->assertTrue($acl->hasAccess($basket, self::$DI['user']));
    }

    public function testOwnerHasAccess()
    {
        $basket = $this->insertOneBasket();

        $acl = new BasketACL();
        $this->assertTrue($acl->hasAccess($basket, self::$DI['user']));
    }

    public function testParticipantHasAccess()
    {
        $basket = $this->insertOneBasketEnv();

        $acl = new BasketACL();
        $this->assertTrue($acl->hasAccess($basket, self::$DI['user_alt1']));
    }

    public function testUserHasNotAccess()
    {
        $basket = $this->insertOneBasket();

        $acl = new BasketACL();
        $this->assertFalse($acl->hasAccess($basket, self::$DI['user_alt1']));
    }
}
