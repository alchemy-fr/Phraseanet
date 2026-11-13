<?php

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class caption_fieldTest extends \PhraseanetTestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('getMultiValues')]
    public function testGet_multi_values($separator, $serialized, $values)
    {
        $this->assertEquals($values, caption_field::get_multi_values($serialized, $separator));
    }

    public static function getMultiValues()
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
