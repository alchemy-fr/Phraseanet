<?php

class databox_statusTest extends PHPUnit_Framework_TestCase
{
    public function testOperation_and()
    {
        $this->assertSame('000000000000', databox_status::operation_and('0x001', '0x010'));
        $this->assertSame('01', databox_status::operation_and('01', '11'));
        $this->assertSame('00', databox_status::operation_and('01', '10'));
        $this->assertSame('10', databox_status::operation_and('11', '10'));
    }

    public function testOperation_and_not()
    {
        $this->assertSame('000000000000', databox_status::operation_and_not('0x001', '0x011'));
        $this->assertSame('00', databox_status::operation_and_not('01', '11'));
        $this->assertSame('01', databox_status::operation_and_not('01', '10'));
        $this->assertSame('01', databox_status::operation_and_not('11', '10'));
        $this->assertSame('10', databox_status::operation_and_not('10', '01'));
    }

    public function testOperation_mask()
    {
        $this->assertSame('001101', databox_status::operation_mask('010101', '0011xx'));
        $this->assertSame('001100', databox_status::operation_mask('0', '0011xx'));
        $this->assertSame('001101', databox_status::operation_mask('1', '0011xx'));
    }

    public function testOperation_or()
    {
        $this->assertSame('000000010001', databox_status::operation_or('0x001', '0x011'));
        $this->assertSame('11', databox_status::operation_or('01', '11'));
    }

    public function testDec2bin()
    {
        $this->assertSame('1010', databox_status::dec2bin('10'));
    }

    public function testHex2bin()
    {
        $this->assertSame('000010100001', databox_status::hex2bin('0x0A1'));
        $this->assertSame('000010100001', databox_status::hex2bin('0A1'));

        try {
            databox_status::hex2bin('G1');
            $this->fail('Should raise an exception');
        } catch (Exception $e) {
            $this->assertSame('Non-hexadecimal value', $e->getMessage());
        }
    }
}
