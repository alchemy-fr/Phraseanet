<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2018 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Tests\Phrasea\TaskManager;

use Alchemy\Phrasea\TaskManager\NotifierInterface;
use Alchemy\Phrasea\TaskManager\NullNotifier;

class NullNotifierTest extends \PHPUnit_Framework_TestCase
{
    public function testItImplementsNotifierInterface()
    {
        $this->assertInstanceOf(NotifierInterface::class, new NullNotifier());
    }
}
