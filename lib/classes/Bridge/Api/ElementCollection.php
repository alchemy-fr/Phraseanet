<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Bridge_Api_ElementCollection extends Bridge_Api_AbstractCollection
{

    /**
     *
     * @param  Bridge_Api_ElementInterface  $element
     * @return Bridge_Api_ElementCollection
     */
    public function add_element(Bridge_Api_ElementInterface $element)
    {
        $this->elements[] = $element;

        return $this;
    }
}
