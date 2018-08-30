<?php
/**
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Report;


Interface ReportInterface
{
    public function getColumnTitles();

    public function getSql();

    public function getSqlParms();
}
