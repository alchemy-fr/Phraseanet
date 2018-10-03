<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

interface module_report_dashboard_componentInterface
{

    public function process();

    public function getDash();

    public function isValid();
}
