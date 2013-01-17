<?php

require_once __DIR__ . '/PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';

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
    }
}
