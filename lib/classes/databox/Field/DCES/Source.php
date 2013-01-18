<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 * @package     Databox DCES
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class databox_Field_DCES_Source extends databox_Field_DCESAbstract
{
    /**
     *
     * @var string
     */
    protected $label = 'Source';

    /**
     *
     * @var string
     */
    protected $definition = 'A related resource from which
                          the described resource is derived.';

    /**
     *
     * @var string
     */
    protected $URI = 'http://dublincore.org/documents/dces/#source';

}
