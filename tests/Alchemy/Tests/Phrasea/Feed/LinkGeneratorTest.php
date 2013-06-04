<?php

namespace Alchemy\Tests\Phrasea\Feed;

use Alchemy\Phrasea\Feed\LinkGenerator;
use Symfony\Component\Routing\Generator\UrlGenerator;

class LinkGeneratorTest extends \PhraseanetPHPUnitAbstract
{
    /**
     * @dataProvider provideGenerationData
     */
    public function testGenerate($expected, $format, $feed, $user, $page, $renew, $alreadyCreated)
    {
        self::$DI['app']['EM']->persist($feed);
        self::$DI['app']['EM']->flush();

        $generator = $this->getMockBuilder('Symfony\Component\Routing\Generator\UrlGenerator')
            ->disableOriginalConstructor()
            ->getMock();

        if ($alreadyCreated) {
            $token = $this->insertOneFeedToken($feed, $user);
            $tokenValue = $token->getValue();
        }

        $capture = null;
        $generator->expects($this->once())
            ->method('generate')
            ->with('feed_user', $this->isType('array'), UrlGenerator::ABSOLUTE_URL)
            ->will($this->returnCallback(function ($name, $data, $option) use (&$capture, $expected) {
                $capture = $data;

                return $expected;
            }));

        $random = self::$DI['app']['tokens'];

        $linkGenerator = new LinkGenerator($generator, self::$DI['app']['EM'], $random);

        $link = $linkGenerator->generate($feed, $user, $format, $page, $renew);

        $this->assertSame($expected, $link->getUri());
        if ($format == "atom") {
            $this->assertSame("application/atom+xml", $link->getMimetype());
            $this->assertSame("Title - Atom", $link->getTitle());
        }
        elseif ($format == "rss") {
            $this->assertSame("application/rss+xml", $link->getMimetype());
            $this->assertSame("Title - RSS", $link->getTitle());
        }

        if ($alreadyCreated) {
            if ($renew) {
                $this->assertEquals($feed->getId(), $capture['id']);
                $this->assertEquals($format, $capture['format']);
                $this->assertNotEquals($tokenValue, $capture['token']);
                if (null !== $page) {
                    $this->assertEquals($page, $capture['page']);
                }

                $this->assertCount(0, self::$DI['app']['EM']
                    ->getRepository("Entities\FeedToken")
                    ->findBy(array('value' => $tokenValue)));
                $this->assertCount(1, self::$DI['app']['EM']
                    ->getRepository("Entities\FeedToken")
                    ->findBy(array('value' => $capture['token'])));
            } else {
                $expectedParams = array(
                    'token'  => $tokenValue,
                    'id'     => $feed->getId(),
                    'format' => $format,
                );

                if ($page !== null) {
                    $expectedParams['page'] = $page;
                }

                $this->assertEquals($expectedParams, $capture);

                $this->assertCount(1, self::$DI['app']['EM']
                    ->getRepository("Entities\FeedToken")
                    ->findBy(array('value' => $tokenValue)));
            }
        } else {
            if (null !== $page) {
                $this->assertEquals($page, $capture['page']);
            }
            $this->assertEquals($feed->getId(), $capture['id']);
            $this->assertEquals($format, $capture['format']);
            $this->assertEquals(12, strlen($capture['token']));

            $this->assertCount(1, self::$DI['app']['EM']
                ->getRepository("Entities\FeedToken")
                ->findBy(array('value' => $capture['token'])));
        }
        $token = self::$DI['app']['EM']
            ->getRepository('Entities\FeedToken')
            ->findByFeedAndUser($feed, $user);
        self::$DI['app']['EM']->remove($token);
        self::$DI['app']['EM']->flush();
    }

    /**
     * @dataProvider provideGenerationDataPublic
     */
    public function testGeneratePublic($expected, $format, $feed, $page)
    {
        self::$DI['app']['EM']->persist($feed);
        self::$DI['app']['EM']->flush();

        $generator = $this->getMockBuilder('Symfony\Component\Routing\Generator\UrlGenerator')
            ->disableOriginalConstructor()
            ->getMock();

        $capture = null;
        $generator->expects($this->once())
            ->method('generate')
            ->with('feed_public', $this->isType('array'), UrlGenerator::ABSOLUTE_URL)
            ->will($this->returnCallback(function ($name, $data, $option) use (&$capture, $expected) {
                $capture = $data;

                return $expected;
            }));

        $random = self::$DI['app']['tokens'];

        $linkGenerator = new LinkGenerator($generator, self::$DI['app']['EM'], $random);

        $link = $linkGenerator->generatePublic($feed, $format, $page);

        $this->assertSame($expected, $link->getUri());
        if ($format == "atom") {
            $this->assertSame("application/atom+xml", $link->getMimetype());
            $this->assertSame("Title - Atom", $link->getTitle());
        }
        elseif ($format == "rss") {
            $this->assertSame("application/rss+xml", $link->getMimetype());
            $this->assertSame("Title - RSS", $link->getTitle());
        }

        if (null !== $page) {
            $this->assertEquals($page, $capture['page']);
        }
        $this->assertEquals($feed->getId(), $capture['id']);
        $this->assertEquals($format, $capture['format']);
    }

    public function provideGenerationData()
    {
        $user = $this->getMockBuilder('User_Adapter')
            ->disableOriginalConstructor()
            ->getMock();

        $user->expects($this->any())
            ->method('get_id')
            ->will($this->returnValue(42));

        $feed = new \Entities\Feed();
        $feed->setTitle('Title');

        return array(
            array('doliprane', 'atom', $feed, $user, null, false, false),
            array('doliprane', 'atom', $feed, $user, null, false, true),
            array('doliprane', 'atom', $feed, $user, null, true, false),
            array('doliprane', 'atom', $feed, $user, null, true, true),
            array('doliprane', 'atom', $feed, $user, 1, false, false),
            array('doliprane', 'atom', $feed, $user, 1, false, true),
            array('doliprane', 'atom', $feed, $user, 1, true, false),
            array('doliprane', 'atom', $feed, $user, 1, true, true),
            array('doliprane', 'rss', $feed, $user, null, false, false),
            array('doliprane', 'rss', $feed, $user, null, false, true),
            array('doliprane', 'rss', $feed, $user, null, true, false),
            array('doliprane', 'rss', $feed, $user, null, true, true),
            array('doliprane', 'rss', $feed, $user, 1, false, false),
            array('doliprane', 'rss', $feed, $user, 1, false, true),
            array('doliprane', 'rss', $feed, $user, 1, true, false),
            array('doliprane', 'rss', $feed, $user, 1, true, true),
        );
    }

    public function provideGenerationDataPublic()
    {
        $feed = new \Entities\Feed();
        $feed->setTitle('Title');

        return array(
            array('doliprane', 'atom', $feed, null),
            array('doliprane', 'atom', $feed, 1),
            array('doliprane', 'rss', $feed, null),
            array('doliprane', 'rss', $feed, 1)
        );
    }
}
