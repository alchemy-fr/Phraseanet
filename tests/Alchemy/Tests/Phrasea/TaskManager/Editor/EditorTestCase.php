<?php

namespace Alchemy\Tests\Phrasea\TaskManager\Editor;

use Alchemy\Phrasea\TaskManager\Editor\EditorInterface;
use Symfony\Component\HttpFoundation\Request;

abstract class EditorTestCase extends \PhraseanetTestCase
{
    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     * @expectedExceptionMessage Invalid XML data.
     */
    public function testThatUpdateXmlWithRequestThrowsABadRequestOnWrongXML()
    {
        $editor = $this->getEditor();
        $editor->updateXMLWithRequest(new Request([], ['xml' => 'invalid xml']));
    }

    /**
     * @dataProvider provideDataForXMLUpdatesFromForm
     */
    public function testThatUpdateXmlWithRequestUpdates($expected, $xml, array $params)
    {
        $editor = $this->getEditor();
        $response = $editor->updateXMLWithRequest(new Request([], array_merge(['xml' => $xml], $params)));
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $response);
        $this->assertEquals($expected, $response->getContent());
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @expectedExceptionMessage Route not found.
     */
    public function testThatFacilityThrowsANotFoundInCaseOfFailure()
    {
        $editor = $this->getEditor();
        $editor->facility(self::$DI['app'], new Request());
    }

    public function testGetDefaultSettingsWithConfiguration()
    {
        $editor = $this->getEditor();
        $dom = new \DOMDocument();
        $dom->strictErrorChecking = true;
        $this->assertTrue(false !== $dom->loadXML($editor->getDefaultSettings(self::$DI['app']['configuration.store'])));
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
        $this->assertInternalType('string', $editor->getTemplatePath());
        $root = self::$DI['app']['root.path'];
        $this->assertFileExists($root . '/templates/web/' . $editor->getTemplatePath());
    }

    abstract public function provideDataForXMLUpdatesFromForm();

    /**
     * @return EditorInterface
     */
    abstract protected function getEditor();
}
