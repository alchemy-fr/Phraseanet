<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use JMS\Serializer\Annotation\SerializedName;

class databox_Field_DCES_Contributor extends databox_Field_DCESAbstract
{
    /**
     *
     * @var string
     */
    protected $label = 'Contributor';

    /**
     *
     * @var string
     */
    protected $definition = 'An entity responsible for making contributions
                          to the resource.';

    /**
     * @SerializedName("URI")
     *
     * @var string
     */
    protected $URI = 'http://dublincore.org/documents/dces/#contributor';

}
