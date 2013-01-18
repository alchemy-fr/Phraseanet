<?php

class caption_fieldTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers caption_field::get_multi_values
     * @dataProvider getMultiValues
     */
    public function testGet_multi_values($separator, $serialized, $values)
    {
        $this->assertEquals($values, caption_field::get_multi_values($serialized, $separator));
    }

    public function getMultiValues()
    {
        return array(
            array(';', 'arbre;fleur-chien maison', array('arbre', 'fleur-chien maison')),
            array('-', 'arbre;fleur-chien maison', array('arbre;fleur', 'chien maison')),
            array(';-', 'arbre;fleur-chien maison', array('arbre','fleur', 'chien maison')),
            array(';- ', 'arbre;fleur-chien maison', array('arbre','fleur', 'chien', 'maison')),
            array('/', 'arbre/fleur/chien maison', array('arbre','fleur', 'chien maison')),
            array('\\', 'arbre\fleur\chien maison', array('arbre','fleur', 'chien maison')),
            array('|', 'arbre|fleur|chien maison', array('arbre','fleur', 'chien maison')),
            array(' ', 'arbre|fleur|chien maison', array('arbre|fleur|chien','maison')),
            array(' ', 'arbre\fleur|chien maison', array('arbre\fleur|chien','maison')),
        );
    }

}
