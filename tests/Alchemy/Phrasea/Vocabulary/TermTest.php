<?php

require_once __DIR__ . '/../../../PhraseanetPHPUnitAbstract.class.inc';

use \Alchemy\Phrasea\Vocabulary\Term;

class TermTest extends \PhraseanetPHPUnitAbstract
{
    /**
     * @var Term
     */
    protected $object;

    /**
     * @var Term
     */
    protected $basicObject;

    /**
     * @var Term
     */
    protected $objectWithControl;
    protected $value2 = 'Supa valu&é"\'(§è!)';
    protected $context2 = 'context
    oualibi';
    protected $basicValue = 'Joli chien';
    protected $value = 'One value';
    protected $context = 'Another context';
    protected $control;
    protected $id = 25;

    public function setUp()
    {
        parent::setUp();
        $this->control = new Alchemy\Phrasea\Vocabulary\ControlProvider\UserProvider();

        $this->object = new Term($this->value, $this->context);
        $this->basicObject = new Term($this->basicValue);
        $this->objectWithControl = new Term($this->value2, $this->context2, $this->control, $this->id);
    }

    public function testGetValue()
    {
        $this->assertEquals($this->basicValue, $this->basicObject->getValue());
        $this->assertEquals($this->value, $this->object->getValue());
        $this->assertEquals($this->value2, $this->objectWithControl->getValue());
    }

    public function testGetContext()
    {
        $this->assertEquals(null, $this->basicObject->getContext());
        $this->assertEquals($this->context, $this->object->getContext());
        $this->assertEquals($this->context2, $this->objectWithControl->getContext());
    }

    public function testGetType()
    {
        $this->assertEquals(null, $this->basicObject->getType());
        $this->assertEquals(null, $this->object->getType());
        $this->assertEquals($this->control, $this->objectWithControl->getType());
    }

    public function testGetId()
    {
        $this->assertEquals(null, $this->basicObject->getId());
        $this->assertEquals(null, $this->object->getId());
        $this->assertEquals($this->id, $this->objectWithControl->getId());
    }
}
