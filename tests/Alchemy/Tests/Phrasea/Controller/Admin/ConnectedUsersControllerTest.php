<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2018 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Tests\Phrasea\Controller\Admin;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Controller\Admin\ConnectedUsersController;
use Psr\Log\LoggerInterface;
use Symfony\Component\Translation\TranslatorInterface;

class ConnectedUsersControllerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider provideModuleNames
     * @param string      $id
     * @param string|null $expected
     */
    public function testItProperlyTranslateModuleNames($id, $expected)
    {
        $translator = $this->getMock(TranslatorInterface::class);
        $translator
            ->expects($this->exactly(9))
            ->method('trans')
            ->willReturnMap([
                ['admin::monitor: module inconnu', [], null, null, 'module0'],
                ['admin::monitor: module production', [], null, null, 'module1'],
                ['admin::monitor: module client', [], null, null, 'module2'],
                ['admin::monitor: module admin', [], null, null, 'module3'],
                ['admin::monitor: module report', [], null, null, 'module4'],
                ['admin::monitor: module thesaurus', [], null, null, 'module5'],
                ['admin::monitor: module comparateur', [], null, null, 'module6'],
                ['admin::monitor: module validation', [], null, null, 'module7'],
                ['admin::monitor: module upload', [], null, null, 'module8'],
                [null, [], null, null, null],
            ]);

        $app = $this->getMockBuilder(Application::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $app->expects($this->any())
            ->method('offsetGet')
            ->willReturnMap([
                ['translator', $translator],
                ['monolog', $this->getMock(LoggerInterface::class)],
            ]);

        $controller = new ConnectedUsersController($app);

        $this->assertSame($expected, $controller->getModuleNameFromId($id));
        // Calling twig should not duplicate translator calls
        $this->assertSame($expected, $controller->getModuleNameFromId($id));
    }

    public function provideModuleNames()
    {
        return [
            ['0', 'module0'],
            ['1', 'module1'],
            ['2', 'module2'],
            ['3', 'module3'],
            ['4', 'module4'],
            ['5', 'module5'],
            ['6', 'module6'],
            ['7', 'module7'],
            ['8', 'module8'],
            ['9', null],
        ];
    }
}
