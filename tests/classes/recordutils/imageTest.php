<?php

use Symfony\Component\HttpFoundation\File\File as SymfoFile;

/**
 * @covers recordutils_image
 */
class recordutils_imageTest extends PhraseanetPHPUnitAbstract
{
    public function testWatermarkWithoutFile()
    {
        self::$DI['app']['phraseanet.appbox']->write_collection_pic(
            self::$DI['app']['media-alchemyst'],
            self::$DI['app']['filesystem'],
            self::$DI['record_1']->get_collection(),
            null,
            \collection::PIC_WM
        );

        $path = recordutils_image::watermark(self::$DI['app'], self::$DI['record_1']->get_subdef('preview'));

        $this->assertTrue(0 === strpos(basename($path), 'watermark_'));
        unlink($path);
    }

    public function testWatermarkWithFile()
    {
        self::$DI['app']['phraseanet.appbox']->write_collection_pic(
            self::$DI['app']['media-alchemyst'],
            self::$DI['app']['filesystem'],
            self::$DI['record_1']->get_collection(),
            new SymfoFile(__DIR__ . '/../../files/logocoll.gif'),
            \collection::PIC_WM
        );

        $path = recordutils_image::watermark(self::$DI['app'], self::$DI['record_1']->get_subdef('preview'));

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
        $this->addStampConf(self::$DI['record_1']->get_collection());

        self::$DI['app']['phraseanet.appbox']->write_collection_pic(
            self::$DI['app']['media-alchemyst'],
            self::$DI['app']['filesystem'],
            self::$DI['record_1']->get_collection(),
            null,
            \collection::PIC_STAMP
        );

        $path = recordutils_image::stamp(self::$DI['app'], self::$DI['record_1']->get_subdef('preview'));

        $this->assertTrue(0 === strpos(basename($path), 'stamp_'));
        unlink($path);
    }

    public function testStampWithFile()
    {
        $this->addStampConf(self::$DI['record_1']->get_collection());

        self::$DI['app']['phraseanet.appbox']->write_collection_pic(
            self::$DI['app']['media-alchemyst'],
            self::$DI['app']['filesystem'],
            self::$DI['record_1']->get_collection(),
            new SymfoFile(__DIR__ . '/../../files/logocoll.gif'),
            \collection::PIC_STAMP
        );

        $path = recordutils_image::stamp(self::$DI['app'], self::$DI['record_1']->get_subdef('preview'));

        $this->assertTrue(0 === strpos(basename($path), 'stamp_'));
        unlink($path);
    }
}