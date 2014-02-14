<?php

class caption_fieldTest extends \PhraseanetTestCase
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
        return [
            [';', 'arbre;fleur-chien maison', ['arbre', 'fleur-chien maison']],
            ['-', 'arbre;fleur-chien maison', ['arbre;fleur', 'chien maison']],
            [';-', 'arbre;fleur-chien maison', ['arbre','fleur', 'chien maison']],
            [';- ', 'arbre;fleur-chien maison', ['arbre','fleur', 'chien', 'maison']],
            ['/', 'arbre/fleur/chien maison', ['arbre','fleur', 'chien maison']],
            ['\\', 'arbre\fleur\chien maison', ['arbre','fleur', 'chien maison']],
            ['|', 'arbre|fleur|chien maison', ['arbre','fleur', 'chien maison']],
            [' ', 'arbre|fleur|chien maison', ['arbre|fleur|chien','maison']],
            [' ', 'arbre\fleur|chien maison', ['arbre\fleur|chien','maison']],
        ];
    }

}
