<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Helper\Record;

use Alchemy\Phrasea\Helper\Record\Helper as RecordHelper,
    Alchemy\Phrasea\Core;
use Symfony\Component\HttpFoundation\Request;

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Feed extends RecordHelper
{
    /**
     *
     * @var Array
     */
    protected $required_sbas_rights = array('bas_chupub');

    /**
     *
     * @var boolean
     */
    protected $works_on_unique_sbas = true;

    /**
     *
     * @var boolean
     */
    protected $flatten_groupings = true;

    /**
     *
     * @param \Alchemy\Phrasea\Core $core
     * @return Feed
     */
    public function __construct(Core $core, Request $Request)
    {
        parent::__construct($core, $Request);

        if ($this->is_single_grouping()) {
            $record = array_pop($this->selection->get_elements());
            foreach ($record->get_children() as $child) {
                $this->selection->add_element($child);
            }
        }

        return $this;
    }
}
