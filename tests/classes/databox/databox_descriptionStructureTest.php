<?php

class databox_descriptionStructureTest extends PhraseanetPHPUnitAbstract
{
    public function testToArray()
    {
        $structure = new \databox_descriptionStructure();

        $array = ['name1' => 'value1', 'name2' => 'value2'];

        $element = $this->provideDataboxFieldMock();
        $element->expects($this->once())
            ->method('toArray')
            ->will($this->returnValue($array));

        $structure->add_element($element);

        $this->assertEquals([$array], $structure->toArray());
    }

    private function provideDataboxFieldMock()
    {
        return $this->getMockBuilder('databox_field')
            ->disableOriginalConstructor()
            ->getMock();
    }
}
