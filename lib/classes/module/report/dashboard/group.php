<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class module_report_dashboard_group implements module_report_dashboard_componentInterface
{
    public $group_dash;
    public $dashboard = [];
    private $valid = false;

    /**
     * @desc group the dashboard
     */
    public function __construct(module_report_dashboard $report)
    {
        if ($report->isValid()) {
            $this->valid = true;
            $this->dashboard = $report->getDash();
        }
        $this->process();
    }

    /**
     * @desc GROUP the dashboard
     * @return <void>
     */
    public function process()
    {
        if ($this->valid) {
            if (is_null($this->group_dash))
                $this->group_dash = [];
            foreach ($this->dashboard as $key => $dash) {
                if (is_object($dash) &&
                    $dash instanceof module_report_dashboard_feed &&
                    $dash->isValid()) {
                    $onedash = $dash->getDash();
                    foreach ($onedash as $typeofreport => $value) {
                        if (is_array($value) && sizeof($value) == 0)
                            continue;
                        else {
                            $this->group_dash[$typeofreport][] = $value;
                        }
                    }
                } else {
                    continue;
                }
            }
        }

        return;
    }

    /**
     * @desc check if the grouped dash is valid
     * @return <bool>
     */
    public function isValid()
    {
        if (isset($this->group_dash) && sizeof($this->group_dash) > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @desc return the results
     * @return <array>
     */
    public function getDash()
    {
        return $this->group_dash;
    }

    /**
     * @desc Tri de grouped dash
     * @return dashboard_merge
     */
    public function tri()
    {
        return new module_report_dashboard_merge($this);
    }
}
