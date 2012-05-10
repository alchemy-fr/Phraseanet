<?php

require_once __DIR__ . '/../../../../PhraseanetPHPUnitAbstract.class.inc';

class AbstractBuilderTest extends PhraseanetPHPUnitAbstract
{

    public function testConstructExceptionNameEmpty()
    {
        try {
            $stub = $this->getMock(
                "\Alchemy\Phrasea\Core\Service\Builder"
                , array()
                , array(
                self::$core
                , ''
                , new \Symfony\Component\DependencyInjection\ParameterBag\ParameterBag()
                )
            );
            $this->fail("should raise an exception");
        } catch (\Exception $e) {

        }
    }

    public function testConstructExceptionCreate()
    {
        try {
            $stub = $this->getMock(
                "\\Alchemy\\Phrasea\\Core\\Service\\Builder"
                , array()
                , array(
                self::$core,
                'test',
                new \Symfony\Component\DependencyInjection\ParameterBag\ParameterBag(),
                )
            );
            $this->fail("should raise an exception");
        } catch (\Exception $e) {

        }
    }
}
