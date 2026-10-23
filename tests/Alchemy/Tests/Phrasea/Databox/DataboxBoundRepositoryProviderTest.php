<?php
/**
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Tests\Phrasea\Databox;

use Alchemy\Phrasea\Databox\DataboxBoundRepositoryFactory;
use Alchemy\Phrasea\Databox\DataboxBoundRepositoryProvider;
use Prophecy\Argument;

class DataboxBoundRepositoryProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var DataboxBoundRepositoryProvider
     */
    private $sut;

    protected function setUp()
    {
        $factory = $this->prophesize(DataboxBoundRepositoryFactory::class);

        $factory
            ->createRepositoryFor(Argument::type('integer'))
            ->will(function ($args) {
                return (object)['databoxId' => $args[0]];
            });

        $this->sut = new DataboxBoundRepositoryProvider($factory->reveal());
    }

    public function testItCreatesRepositoriesIfUnknown()
    {
        $repository = $this->sut->getRepositoryForDatabox(42);

        $this->assertNotNull($repository, 'Failed to create a repository');
        $this->assertSame($repository, $this->sut->getRepositoryForDatabox(42));
    }

    public function testItShouldNotCreateTwoRepositoriesPerDatabox()
    {
        $repository1 = $this->sut->getRepositoryForDatabox(1);
        $repository2 = $this->sut->getRepositoryForDatabox(2);

        $this->assertNotNull($repository1, 'Failed to create first repository');
        $this->assertNotNull($repository2, 'Failed to create second repository');
        $this->assertNotSame($repository1, $repository2, 'Different Databoxes should have different repositories');

        $this->assertSame($repository2, $this->sut->getRepositoryForDatabox(2), 'Second Repository should be returned');
        $this->assertSame($repository1, $this->sut->getRepositoryForDatabox(1), 'First Repository should be returned');
    }
}
