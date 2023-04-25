<?php

use Symfony\Component\HttpFoundation\File\File as SymfoFile;

/**
 * @group functional
 * @group legacy
 * @covers recordutils_image
 */
class recordutils_imageTest extends \PhraseanetTestCase
{
    public function testWatermarkWithoutFile()
    {
        $app = $this->getApplication();
        /** @var record_adapter $record_1 */
        $record_1 = self::$DI['record_1'];
        $app->getApplicationBox()->write_collection_pic(
            $app['media-alchemyst'],
            $app['filesystem'],
            $record_1->getCollection(),
            null,
            \collection::PIC_WM
        );

        $path = recordutils_image::watermark($app, $record_1->get_subdef('preview'));

        $this->assertTrue(0 === strpos(basename($path), 'watermark_'));
        unlink($path);
    }

    public function testWatermarkWithFile()
    {
        $app = $this->getApplication();
        /** @var record_adapter $record_1 */
        $record_1 = self::$DI['record_1'];
        $app->getApplicationBox()->write_collection_pic(
            $app['media-alchemyst'],
            $app['filesystem'],
            $record_1->getCollection(),
            new SymfoFile(__DIR__ . '/../../files/logocoll.gif'),
            \collection::PIC_WM
        );

        $path = recordutils_image::watermark($app, $record_1->get_subdef('preview'));

        $this->assertTrue(0 === strpos(basename($path), 'watermark_'));
        unlink($path);
    }

    private function addStampConf(\collection $coll)
    {
        $domprefs = new DOMDocument();
        $domprefs->loadXML($coll->get_prefs());

        $prefs = '<?xml version="1.0" encoding="UTF-8"?>
<baseprefs>
    <status>0</status>

    <stamp>
        <logo position="left" width="25%"/>
        <text size="50%">Date: <var name="date"/></text>
        <text size="50%">Record_id: <var name="record_id"/></text>';

        foreach ($coll->get_databox()->get_meta_structure() as $databox_field) {
            $name = $databox_field->get_name();
            $prefs .= '<text size="50%">'.$name.': <field name="'.$name.'"/></text>' . "\n";
        }

        $prefs .= '</stamp>
    <caninscript>1</caninscript>
    <sugestedValues>
    </sugestedValues>
</baseprefs>';

        $newdom = new DOMDocument();
        $newdom->loadXML($prefs);

        $coll->set_prefs($newdom);
    }

    public function testStampWithoutFile()
    {
        /** @var record_adapter $record_1 */
        $record_1 = self::$DI['record_1'];
        $this->addStampConf($record_1->getCollection());

        $app = $this->getApplication();
        $app->getApplicationBox()->write_collection_pic(
            $app['media-alchemyst'],
            $app['filesystem'],
            $record_1->getCollection(),
            null,
            \collection::PIC_STAMP
        );

        $imagick = new \Imagick();

        //TODO: upgrade php imagine ???
        if (method_exists($imagick, 'setImageOpacity')) {
            $path = recordutils_image::stamp($app, $record_1->get_subdef('preview'));

            $this->assertTrue(0 === strpos(basename($path), 'stamp_'));
            unlink($path);
        }
    }

    public function testStampWithFile()
    {
        /** @var record_adapter $record_1 */
        $record_1 = self::$DI['record_1'];
        $this->addStampConf($record_1->getCollection());

        $app = $this->getApplication();
        $app->getApplicationBox()->write_collection_pic(
            $app['media-alchemyst'],
            $app['filesystem'],
            $record_1->getCollection(),
            new SymfoFile(__DIR__ . '/../../files/logocoll.gif'),
            \collection::PIC_STAMP
        );

        $imagick = new \Imagick();

        //TODO: upgrade php imagine ???
        if (method_exists($imagick, 'setImageOpacity')) {
            $path = recordutils_image::stamp($app, $record_1->get_subdef('preview'));

            $this->assertTrue(0 === strpos(basename($path), 'stamp_'));
            unlink($path);
        }
    }
}
