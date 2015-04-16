<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Test\Phrasea\Console;

use Alchemy\Phrasea\Console\Application;
use Symfony\Component\Console\Application as SymfonyConsoleApplication;

class ApplicationTest extends \PHPUnit_Framework_TestCase
{
    public function testItExtendsSymfonyConsoleApplication()
    {
        $sut = new Application();

        $this->assertInstanceOf(SymfonyConsoleApplication::class, $sut);
    }

    public function testItAddsDefaultOptionToDisablePlugins()
    {
        $sut = new Application();

        $this->assertTrue(
            $sut->getDefinition()->hasOption('disable-plugins'),
            'Console Application should implement a way to disable plugins'
        );
    }
}
