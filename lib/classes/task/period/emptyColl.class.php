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
 * @package     task_manager
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class task_period_emptyColl extends task_appboxAbstract
{
    protected $base_id;
    protected $suicidable = true;

    public function getName()
    {
        return(_("Vidage de collection"));
    }

    public static function interfaceAvailable()
    {
        return false;
    }

    public function help()
    {
        return("Vide une collection");
    }

    protected function load_settings(SimpleXMLElement $sx_task_settings)
    {
        $this->base_id = (int) $sx_task_settings->base_id;
        parent::load_settings($sx_task_settings);

        return $this;
    }
    protected $total_records = 0;

    protected function retrieve_content(appbox $appbox)
    {
        if ( ! $this->base_id) {
            $this->current_state = self::STATE_FINISHED;

            return;
        }
        $collection = collection::get_from_base_id($this->base_id);
        $this->total_records = $collection->get_record_amount();
        $collection->empty_collection(200);
        $this->records_done +=$this->total_records;
        $this->setProgress($this->records_done, $this->total_records);

        if ($this->total_records == 0) {
            $this->current_state = self::STATE_FINISHED;
            $this->log('Job finished');
        }

        return array();
    }

    protected function process_one_content(appbox $appbox, Array $row)
    {
        return $this;
    }

    protected function post_process_one_content(appbox $appbox, Array $row)
    {
        return $this;
    }
}
