<?php

class databox_statusTest extends \PhraseanetTestCase
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
        $this->object = $this->databox->getStatusStructure();
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

    public function testOperation_mask()
    {
        $this->assertEquals('001101', databox_status::operation_mask(self::$DI['app'], '010101', '0011xx'));
        $this->assertEquals('001100', databox_status::operation_mask(self::$DI['app'], '0', '0011xx'));
        $this->assertEquals('001101', databox_status::operation_mask(self::$DI['app'], '1', '0011xx'));
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
