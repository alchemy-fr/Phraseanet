<?php

namespace Alchemy\Tests\Phrasea\TaskManager\Editor;

use Alchemy\Phrasea\TaskManager\Editor\EditorInterface;
use Symfony\Component\HttpFoundation\Request;

abstract class EditorTestCase extends \PhraseanetTestCase
{
    public function testThatUpdateXmlWithRequestThrowsABadRequestOnWrongXML()
    {
        $this->expectException(\Symfony\Component\HttpKernel\Exception\BadRequestHttpException::class);
        $this->expectExceptionMessage('Invalid XML data.');
        $editor = $this->getEditor();
        $editor->updateXMLWithRequest(new Request([], ['xml' => 'invalid xml']));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideDataForXMLUpdatesFromForm')]
    public function testThatUpdateXmlWithRequestUpdates($expected, $xml, array $params)
    {
        $editor = $this->getEditor();
        $response = $editor->updateXMLWithRequest(new Request([], array_merge(['xml' => $xml], $params)));
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $response);
        $this->assertEquals($expected, $response->getContent());
    }

    public function testThatFacilityThrowsANotFoundInCaseOfFailure()
    {
        $this->expectException(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);
        $this->expectExceptionMessage('Route not found.');
        $editor = $this->getEditor();
        $editor->facility(self::$DI['app'], new Request());
    }

    public function testGetDefaultSettingsWithConfiguration()
    {
        $editor = $this->getEditor();
        $dom = new \DOMDocument();
        $dom->strictErrorChecking = true;
        $this->assertTrue(false !== $dom->loadXML($editor->getDefaultSettings(self::$DI['app']['conf'])));
    }

    public function testGetDefaultSettingsWithoutConfiguration()
    {
        $editor = $this->getEditor();
        $dom = new \DOMDocument();
        $dom->strictErrorChecking = true;
        $this->assertTrue(false !== $dom->loadXML($editor->getDefaultSettings()));
    }

    public function testGetDefaultPeriod()
    {
        $editor = $this->getEditor();
        $this->assertGreaterThan(0, $editor->getDefaultPeriod());
    }

    public function testGetTemplatePath()
    {
        $editor = $this->getEditor();
        $this->assertIsString($editor->getTemplatePath());
        $root = self::$DI['app']['root.path'];
        $this->assertFileExists($root . '/templates/web/' . $editor->getTemplatePath());
    }

    abstract public static function provideDataForXMLUpdatesFromForm();

    /**
     * @return EditorInterface
     */
    abstract protected function getEditor();
}
