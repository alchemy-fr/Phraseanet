<?php

namespace Alchemy\Tests\Phrasea\Core\Configuration;

use Alchemy\Phrasea\Core\Configuration\RegistryManipulator;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class RegistryManipulatorTest extends \PhraseanetTestCase
{
    /** @var RegistryManipulator */
    private $manipulator;

    public function setUp(): void
    {
        parent::setUp();
        $this->manipulator = new RegistryManipulator(self::$DI['app']['form.factory'], self::$DI['app']['translator'], self::$DI['app']['locales.available']);
    }

    public function testCreateForm()
    {
        $form = $this->manipulator->createForm();
        $this->assertInstanceOf('Symfony\Component\Form\FormInterface', $form);
        $data = $form->getData();

        $this->assertEquals('Phraseanet', $data['general']['title']);
    }

    public function testCreateFormWithConf()
    {
        $conf = $this->createMock('Alchemy\Phrasea\Core\Configuration\PropertyAccess');
        $conf->expects($this->once())
            ->method('get')
            ->with('registry')
            ->will($this->returnValue(['general' => ['title' => 'Grumpf']]));

        $form = $this->manipulator->createForm($conf);
        $this->assertInstanceOf('Symfony\Component\Form\FormInterface', $form);
        $data = $form->getData();

        $this->assertEquals('Grumpf', $data['general']['title']);
    }

    public function testGetRegistryData()
    {
        $data = $this->manipulator->getRegistryData();
        $this->assertIsArray($data);
        $this->assertEquals('Phraseanet', $data['general']['title']);
    }

    public function testGetRegistryDataWithForm()
    {
        $form = $this->manipulator->createForm();
        $form->submit(['general' => ['title' => 'Grumpf']]);

        $data = $this->manipulator->getRegistryData($form);
        $this->assertIsArray($data);
        $this->assertEquals('Grumpf', $data['general']['title']);
    }
}
