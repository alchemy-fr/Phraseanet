<?php

class databoxTest extends PhraseanetWebTestCaseAuthenticatedAbstract
{
    /**
     * @covers databox::get_thesaurus
     * @covers databox::get_dom_thesaurus
     */
    public function testGetThesaurus()
    {
        $testValue = rand(1000, 9999);
        $xmlth = "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?>\n"
            . "<thesaurus version=\"2.0.5\" creation_date=\"20100101000000\" modification_date=\"20100101000000\" nextid=\"4\">"
            . "<testnode value=\"" . $testValue . "\"/>"
            . "</thesaurus>\n";

        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->loadXML($xmlth);

        $databox = $this->createDatabox();
        $databox->saveThesaurus($dom);

        $newxml = $databox->get_thesaurus();

        if (!$dom->loadXML($newxml)) {
            $this->fail('Unable to load XML thesaurus');
        }

        $this->assertNotEquals("20100101000000", $dom->documentElement->getAttribute("modification_date"));
        $this->assertEquals($testValue, $dom->documentElement->firstChild->getAttribute("value"));

        $newdom = $databox->get_dom_thesaurus();
        $this->assertNotEquals("20100101000000", $newdom->documentElement->getAttribute("modification_date"));
        $this->assertEquals($testValue, $newdom->documentElement->firstChild->getAttribute("value"));

        $databox->unmount_databox();
        $databox->delete();
    }

    /**
     * @covers databox::get_cterms
     * @covers databox::get_dom_cterms
     */
    public function test_get_cterms()
    {
        $testValue = rand(1000, 9999);
        $xmlth = "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?>\n"
            . "<cterms version=\"2.0.5\" creation_date=\"20100101000000\" modification_date=\"20100101000000\" nextid=\"4\">"
            . "<testnode value=\"" . $testValue . "\"/>"
            . "</cterms>\n";

        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->loadXML($xmlth);

        $databox = $this->createDatabox();
        $databox->saveCterms($dom);

        $newxml = $databox->get_cterms();

        if (!$dom->loadXML($newxml)) {
            $this->fail('Unable to load XML cterms');
        }

        $this->assertNotEquals("20100101000000", $dom->documentElement->getAttribute("modification_date"));
        $this->assertEquals($testValue, $dom->documentElement->firstChild->getAttribute("value"));

        $newdom = $databox->get_dom_cterms();
        $this->assertNotEquals("20100101000000", $newdom->documentElement->getAttribute("modification_date"));
        $this->assertEquals($testValue, $newdom->documentElement->firstChild->getAttribute("value"));

        $databox->unmount_databox();
        $databox->delete();
    }

    public function testViewname()
    {
        $databox = self::$DI['record_1']->get_databox();
        $databox->set_viewname(null);

        $this->assertEquals($databox->get_dbname(), $databox->get_viewname());
        $databox->set_viewname('cool view name');
        $this->assertEquals('cool view name', $databox->get_viewname());
        $databox->set_viewname(null);
        $this->assertEquals($databox->get_dbname(), $databox->get_viewname());
    }

    public function testSet_label()
    {
        $databox = self::$DI['record_1']->get_databox();

        $databox->set_viewname('pretty name');
        $databox->set_label('fr', 'french label');
        $databox->set_label('en', 'english label');
        $databox->set_label('nl', null);
        $databox->set_label('de', null);
        $this->assertEquals('french label', $databox->get_label('fr'));
        $this->assertEquals('english label', $databox->get_label('en'));
        $this->assertEquals('pretty name', $databox->get_label('nl'));
        $this->assertEquals('pretty name', $databox->get_label('de'));
        $this->assertNull($databox->get_label('nl', false));
        $this->assertNull($databox->get_label('de', false));

        $databox->set_viewname(null);
        $databox->set_label('fr', null);
        $databox->set_label('en', null);
        $databox->set_label('nl', 'dutch label');
        $databox->set_label('de', 'german label');
        $this->assertEquals($databox->get_dbname(), $databox->get_label('fr'));
        $this->assertEquals($databox->get_dbname(), $databox->get_label('en'));
        $this->assertEquals('dutch label', $databox->get_label('nl'));
        $this->assertEquals('german label', $databox->get_label('de'));
        $this->assertNull($databox->get_label('fr', false));
        $this->assertNull($databox->get_label('en', false));
    }
}
