<?php

require_once __DIR__ . '/../PhraseanetPHPUnitAbstract.class.inc';

class databox_statusTest extends PhraseanetPHPUnitAbstract
{
    /**
     * @var databox_status
     */
    protected $object;
    protected $databox;

    public function setUp()
    {
        parent::setUp();
        $this->databox = self::$DI['record_1']->get_databox();
        $this->object = $this->databox->get_statusbits();
    }

    public function testGetStatus()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    public function testGetDisplayStatus()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @todo Implement testGetSearchStatus().
     */
    public function testGetSearchStatus()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @todo Implement testGetPath().
     */
    public function testGetPath()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @todo Implement testGetUrl().
     */
    public function testGetUrl()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @todo Implement testDeleteStatus().
     */
    public function testDeleteStatus()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @todo Implement testUpdateStatus().
     */
    public function testUpdateStatus()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @todo Implement testDeleteIcon().
     */
    public function testDeleteIcon()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @todo Implement testUpdateIcon().
     */
    public function testUpdateIcon()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    public function testOperation_and()
    {
        $this->assertEquals('0', databox_status::operation_and(self::$DI['app'], '0x001', '0x010'));
        $this->assertEquals('1', databox_status::operation_and(self::$DI['app'], '01', '11'));
        $this->assertEquals('0', databox_status::operation_and(self::$DI['app'], '01', '10'));
        $this->assertEquals('10', databox_status::operation_and(self::$DI['app'], '11', '10'));
    }

    public function testOperation_and_not()
    {
        $this->assertEquals('0', databox_status::operation_and_not(self::$DI['app'], '0x001', '0x011'));
        $this->assertEquals('0', databox_status::operation_and_not(self::$DI['app'], '01', '11'));
        $this->assertEquals('1', databox_status::operation_and_not(self::$DI['app'], '01', '10'));
        $this->assertEquals('1', databox_status::operation_and_not(self::$DI['app'], '11', '10'));
        $this->assertEquals('10', databox_status::operation_and_not(self::$DI['app'], '10', '01'));
    }

    public function testOperation_or()
    {
        $this->assertEquals('10001', databox_status::operation_or(self::$DI['app'], '0x001', '0x011'));
        $this->assertEquals('11', databox_status::operation_or(self::$DI['app'], '01', '11'));
    }

    public function testDec2bin()
    {
        $this->assertEquals('1010', databox_status::dec2bin(self::$DI['app'], '10'));
    }

    public function testHex2bin()
    {
        $this->assertEquals('10100001', databox_status::hex2bin(self::$DI['app'], '0x0A1'));
        $this->assertEquals('10100001', databox_status::hex2bin(self::$DI['app'], '0A1'));

        try {
            databox_status::hex2bin(self::$DI['app'], 'G1');
            $this->fail('Should raise an exception');
        } catch (Exception $e) {

        }
    }
}
