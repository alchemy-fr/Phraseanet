<?php

namespace Alchemy\Tests\Phrasea\Authentication\Provider\Token;

use Alchemy\Phrasea\Authentication\Provider\Token\Identity;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class IdentityTest extends \PhraseanetTestCase
{
    public function testThatOffsetAreSetOnConstruct()
    {
        $identity = new Identity();

        $this->assertNull($identity->get(Identity::PROPERTY_ID));
        $this->assertNull($identity->get(Identity::PROPERTY_IMAGEURL));
    }

    public function testThatConstructArgumentAreSet()
    {
        $identity = new Identity([Identity::PROPERTY_IMAGEURL => 'image-uri']);

        $this->assertNull($identity->get(Identity::PROPERTY_ID));
        $this->assertEquals('image-uri', $identity->get(Identity::PROPERTY_IMAGEURL));
    }

    public function testThatGetOnUnknownArgThrowsAnException()
    {
        $this->expectException(\Alchemy\Phrasea\Exception\InvalidArgumentException::class);
        $identity = new Identity();

        $identity->get('unknown');
    }

    public function testGetFirstname()
    {
        $identity = new Identity([
            Identity::PROPERTY_ID        => 'id',
            Identity::PROPERTY_IMAGEURL  => 'image url',
            Identity::PROPERTY_COMPANY   => 'company',
            Identity::PROPERTY_USERNAME  => 'username',
            Identity::PROPERTY_EMAIL     => 'email',
            Identity::PROPERTY_FIRSTNAME => 'first name',
            Identity::PROPERTY_LASTNAME  => 'last name',
        ]);

        $this->assertEquals($identity->getId(), $identity->get($identity::PROPERTY_ID));
        $this->assertEquals($identity->getImageURI(), $identity->get($identity::PROPERTY_IMAGEURL));
        $this->assertEquals($identity->getCompany(), $identity->get($identity::PROPERTY_COMPANY));
        $this->assertEquals($identity->getUsername(), $identity->get($identity::PROPERTY_USERNAME));
        $this->assertEquals($identity->getEmail(), $identity->get($identity::PROPERTY_EMAIL));
        $this->assertEquals($identity->getFirstname(), $identity->get($identity::PROPERTY_FIRSTNAME));
        $this->assertEquals($identity->getLastname(), $identity->get($identity::PROPERTY_LASTNAME));

        $this->assertTrue(false !== strpos($identity->getDisplayName(), $identity->get($identity::PROPERTY_FIRSTNAME)));
        $this->assertTrue(false !== strpos($identity->getDisplayName(), $identity->get($identity::PROPERTY_LASTNAME)));
    }

    public function testHas()
    {
        $identity = new Identity([
                                      Identity::PROPERTY_IMAGEURL  => 'image url',
                                 ]);

        $this->assertTrue($identity->has(Identity::PROPERTY_IMAGEURL));

        $identity = new Identity();

        $this->assertFalse($identity->has(Identity::PROPERTY_IMAGEURL));
    }

    public function testSet()
    {
        $identity = new Identity();

        $this->assertNull($identity->get(Identity::PROPERTY_IMAGEURL));
        $identity->set(Identity::PROPERTY_IMAGEURL, 'image-uri');
        $this->assertEquals('image-uri', $identity->get(Identity::PROPERTY_IMAGEURL));

        return $identity;
    }

    public function testAll()
    {
        $identity = new Identity();

        $this->assertEquals([], $identity->all());

        $identity->set(Identity::PROPERTY_IMAGEURL, 'image-uri');
        $this->assertEquals(['image_url' => 'image-uri'], $identity->all());

        return $identity;
    }

    #[\PHPUnit\Framework\Attributes\Depends('testSet')]
    public function testRemove($identity)
    {
        $identity->remove(Identity::PROPERTY_IMAGEURL);
        $this->assertFalse($identity->has(Identity::PROPERTY_IMAGEURL));
    }

    #[\PHPUnit\Framework\Attributes\Depends('testSet')]
    public function testRemoveThrowsAnException($identity)
    {
        $this->expectException(\Alchemy\Phrasea\Exception\InvalidArgumentException::class);
        $identity->remove(Identity::PROPERTY_IMAGEURL);
        $this->assertFalse($identity->get(Identity::PROPERTY_IMAGEURL));
    }
}
