<?php

namespace Alchemy\Tests\Phrasea\Feed;

use Alchemy\Phrasea\Feed\Aggregate;
use Alchemy\Phrasea\Feed\Link\AggregateLinkGenerator;
use Alchemy\Phrasea\Model\Entities\Feed;
use Symfony\Component\Routing\Generator\UrlGenerator;

class AggregateLinkGeneratorTest extends \PhraseanetTestCase
{
    /**
     * @dataProvider provideGenerationData
     */
    public function testGenerate($expected, $format, $page, $renew, $alreadyCreated)
    {
        $user = self::$DI['user'];
        $feed = new Feed();
        $feed->setTitle("title");

        $another_feed = new Feed($user);
        $another_feed->setTitle("another_title");

        $feeds = [$feed, $another_feed];

        $aggregate = new Aggregate(self::$DI['app']['EM'], $feeds);

        $generator = $this->getMockBuilder('Symfony\Component\Routing\Generator\UrlGenerator')
            ->disableOriginalConstructor()
            ->getMock();

        if ($alreadyCreated) {
            $token = self::$DI['app']['EM']->find('Phraseanet:AggregateToken', 1);
            $tokenValue = $token->getValue();
        }

        $capture = null;
        $generator->expects($this->once())
            ->method('generate')
            ->with('feed_user_aggregated', $this->isType('array'), UrlGenerator::ABSOLUTE_URL)
            ->will($this->returnCallback(function ($name, $data, $option) use (&$capture, $expected) {
                $capture = $data;

                return $expected;
            }));

        $random = self::$DI['app']['tokens'];

        $linkGenerator = new AggregateLinkGenerator($generator, self::$DI['app']['EM'], $random);

        $link = $linkGenerator->generate($aggregate, $user, $format, $page, $renew);

        if ($format == "atom") {
            $this->assertSame("application/atom+xml", $link->getMimetype());
            $this->assertSame("AGGREGATE - Atom", $link->getTitle());
        } elseif ($format == "rss") {
            $this->assertSame("application/rss+xml", $link->getMimetype());
            $this->assertSame("AGGREGATE - RSS", $link->getTitle());
        }

        if ($alreadyCreated) {
            if ($renew) {
                $this->assertEquals($format, $capture['format']);
                if (null !== $page) {
                    $this->assertEquals($page, $capture['page']);
                }
                $this->assertNotEquals($tokenValue, $capture['token']);

                $this->assertCount(0, self::$DI['app']['EM']
                    ->getRepository('Alchemy\Phrasea\Model\Entities\AggregateToken')
                    ->findBy(['value' => $tokenValue]));
                $this->assertCount(1, self::$DI['app']['EM']
                    ->getRepository('Alchemy\Phrasea\Model\Entities\AggregateToken')
                    ->findBy(['value' => $capture['token']]));
            } else {
                $expectedParams = [
                    'token'  => $tokenValue,
                    'format' => $format,
                ];

                if ($page !== null) {
                    $expectedParams['page'] = $page;
                }

                $this->assertEquals($expectedParams, $capture);

                $this->assertCount(1, self::$DI['app']['EM']
                    ->getRepository('Alchemy\Phrasea\Model\Entities\AggregateToken')
                    ->findBy(['value' => $tokenValue]));
            }
        } else {
            if (null !== $page) {
                    $this->assertEquals($page, $capture['page']);
            }
            $this->assertEquals($format, $capture['format']);
            $this->assertEquals(12, strlen($capture['token']));

            $this->assertCount(1, self::$DI['app']['EM']
                ->getRepository('Alchemy\Phrasea\Model\Entities\AggregateToken')
                ->findBy(['value' => $capture['token']]));
        }
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
