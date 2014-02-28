<?php

namespace Alchemy\Tests\Phrasea\Feed;

use Alchemy\Phrasea\Feed\Link\FeedLinkGenerator;
use Symfony\Component\Routing\Generator\UrlGenerator;

class FeedLinkGeneratorTest extends \PhraseanetTestCase
{
    /**
     * @dataProvider provideGenerationData
     */
    public function testGenerate($expected, $format, $page, $renew, $alreadyCreated)
    {
        $user = self::$DI['user'];
        $feed = self::$DI['app']['EM']->find('Phraseanet:Feed', 1);

        $generator = $this->getMockBuilder('Symfony\Component\Routing\Generator\UrlGenerator')
            ->disableOriginalConstructor()
            ->getMock();

        if ($alreadyCreated) {
            $token = self::$DI['app']['EM']->find('Phraseanet:FeedToken', 1);
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

        $random = self::$DI['app']['random.low'];

        $linkGenerator = new FeedLinkGenerator($generator, self::$DI['app']['EM'], $random);

        $link = $linkGenerator->generate($feed, self::$DI['user'], $format, $page, $renew);

        $this->assertSame($expected, $link->getUri());
        if ($format == "atom") {
            $this->assertSame("application/atom+xml", $link->getMimetype());
            $this->assertSame("Feed test, YOLO! - Atom", $link->getTitle());
        } elseif ($format == "rss") {
            $this->assertSame("application/rss+xml", $link->getMimetype());
            $this->assertSame("Feed test, YOLO! - RSS", $link->getTitle());
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
                    ->getRepository('Phraseanet:FeedToken')
                    ->findBy(['value' => $tokenValue]));
                $this->assertCount(1, self::$DI['app']['EM']
                    ->getRepository('Phraseanet:FeedToken')
                    ->findBy(['value' => $capture['token']]));
            } else {
                $expectedParams = [
                    'token'  => $tokenValue,
                    'id'     => $feed->getId(),
                    'format' => $format,
                ];

                if ($page !== null) {
                    $expectedParams['page'] = $page;
                }

                $this->assertEquals($expectedParams, $capture);

                $this->assertCount(1, self::$DI['app']['EM']
                    ->getRepository('Phraseanet:FeedToken')
                    ->findBy(['value' => $tokenValue]));
            }
        } else {
            if (null !== $page) {
                $this->assertEquals($page, $capture['page']);
            }
            $this->assertEquals($feed->getId(), $capture['id']);
            $this->assertEquals($format, $capture['format']);
            $this->assertEquals(64, strlen($capture['token']));

            $this->assertCount(1, self::$DI['app']['EM']
                ->getRepository('Phraseanet:FeedToken')
                ->findBy(['value' => $capture['token']]));
        }
    }

    /**
     * @dataProvider provideGenerationDataPublic
     */
    public function testGeneratePublic($expected, $format, $page)
    {
        $feed = self::$DI['app']['EM']->find('Phraseanet:Feed', 1);

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

        $random = self::$DI['app']['random.low'];

        $linkGenerator = new FeedLinkGenerator($generator, self::$DI['app']['EM'], $random);

        $link = $linkGenerator->generatePublic($feed, $format, $page);

        $this->assertSame($expected, $link->getUri());
        if ($format == "atom") {
            $this->assertSame("application/atom+xml", $link->getMimetype());
            $this->assertSame("Feed test, YOLO! - Atom", $link->getTitle());
        } elseif ($format == "rss") {
            $this->assertSame("application/rss+xml", $link->getMimetype());
            $this->assertSame("Feed test, YOLO! - RSS", $link->getTitle());
        }

        if (null !== $page) {
            $this->assertEquals($page, $capture['page']);
        }
        $this->assertEquals($feed->getId(), $capture['id']);
        $this->assertEquals($format, $capture['format']);
    }

    public function provideGenerationData()
    {
        return [
            ['doliprane', 'atom', null, false, false],
            ['doliprane', 'atom', null, false, true],
            ['doliprane', 'atom', null, true, false],
            ['doliprane', 'atom', null, true, true],
            ['doliprane', 'atom', 1, false, false],
            ['doliprane', 'atom', 1, false, true],
            ['doliprane', 'atom', 1, true, false],
            ['doliprane', 'atom', 1, true, true],
            ['doliprane', 'rss', null, false, false],
            ['doliprane', 'rss', null, false, true],
            ['doliprane', 'rss', null, true, false],
            ['doliprane', 'rss', null, true, true],
            ['doliprane', 'rss', 1, false, false],
            ['doliprane', 'rss', 1, false, true],
            ['doliprane', 'rss', 1, true, false],
            ['doliprane', 'rss', 1, true, true],
        ];
    }

    public function provideGenerationDataPublic()
    {
        return [
            ['doliprane', 'atom', null],
            ['doliprane', 'atom', 1],
            ['doliprane', 'rss', null],
            ['doliprane', 'rss', 1]
        ];
    }
}
