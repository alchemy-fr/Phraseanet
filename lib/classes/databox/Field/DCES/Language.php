<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use JMS\Serializer\Annotation\SerializedName;

/**
 *
 * @package     Databox DCES
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class databox_Field_DCES_Language extends databox_Field_DCESAbstract
{
    /**
     *
     * @var string
     */
    protected $label = 'Language';

    /**
     *
     * @var string
     */
    protected $definition = 'A language of the resource.
                          (see [RFC4646] http://www.ietf.org/rfc/rfc4646.txt)';

    /**
     * @SerializedName("URI")
     *
     * @var string
     */
    protected $URI = 'http://dublincore.org/documents/dces/#language';

}
