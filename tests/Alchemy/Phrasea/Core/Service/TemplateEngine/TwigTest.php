<?php

require_once __DIR__ . '/../../../../../PhraseanetPHPUnitAbstract.class.inc';

class TwigTest extends PhraseanetPHPUnitAbstract
{
    protected $options;

    public function setUp()
    {
        parent::setUp();
        $this->options = array(
            'debug'            => true
            , 'charset'          => 'utf-8'
            , 'strict_variables' => true
            , 'autoescape'       => true
            , 'optimizer'        => true
        );
    }

    public function testService()
    {
        $doctrine = new \Alchemy\Phrasea\Core\Service\TemplateEngine\Twig(
                self::$core, $this->options
        );

        $this->assertInstanceOf("\Twig_Environment", $doctrine->getDriver());
    }

    public function testServiceExcpetion()
    {
        $doctrine = new \Alchemy\Phrasea\Core\Service\TemplateEngine\Twig(
                self::$core, $this->options
        );

        $this->assertInstanceOf("\Twig_Environment", $doctrine->getDriver());
    }

    public function testType()
    {
        $doctrine = new \Alchemy\Phrasea\Core\Service\TemplateEngine\Twig(
                self::$core, $this->options
        );

        $this->assertEquals("twig", $doctrine->getType());
    }
}
