<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 * @package     Feeds
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Feed_Entry_Item implements Feed_Entry_ItemInterface, cache_cacheableInterface
{
    /**
     *
     * @var appbox
     */
    protected $appbox;

    /**
     *
     * @var int
     */
    protected $id;

    /**
     *
     * @var record_adapter
     */
    protected $record;

    /**
     *
     * @var Feed_Entry_Adapter
     */
    protected $entry;

    /**
     *
     * @var int
     */
    protected $ord;

    /**
     *
     * @param  appbox             $appbox
     * @param  Feed_Entry_Adapter $entry
     * @param  int                $id
     * @return Feed_Entry_Item
     */
    public function __construct(appbox $appbox, Feed_Entry_Adapter $entry, $id)
    {
        $this->appbox = $appbox;
        $this->id = (int) $id;
        $this->entry = $entry;
        $this->load();

        return $this;
    }

    public function get_entry()
    {
        return $this->entry;
    }

    /**
     *
     * @return Feed_Entry_Item
     */
    protected function load()
    {
        try {
            $datas = $this->get_data_from_cache();
            $this->record = $this->appbox->get_databox($datas['sbas_id'])
                ->get_record($datas['record_id'], $datas['ord']);
            $this->ord = $datas['ord'];

            return $this;
        } catch (Exception $e) {

        }

        $sql = 'SELECT id, sbas_id, record_id, ord
            FROM feed_entry_elements WHERE id = :id';

        $stmt = $this->appbox->get_connection()->prepare($sql);
        $stmt->execute(array(':id' => $this->id));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if ( ! $row)
            throw new Exception_Feed_ItemNotFound();

        $this->record = $this->appbox->get_databox($row['sbas_id'])
            ->get_record($row['record_id']);
        $this->ord = (int) $row['ord'];

        $datas = array(
            'record_id' => $this->record->get_record_id()
            , 'sbas_id'   => $this->record->get_sbas_id()
            , 'ord'       => $this->ord
        );

        $this->set_data_to_cache($datas);

        return $this;
    }

    /**
     *
     * @return int
     */
    public function get_id()
    {
        return $this->id;
    }

    /**
     *
     * @return record_adapter
     */
    public function get_record()
    {
        return $this->record;
    }

    /**
     *
     * @return int
     */
    public function get_ord()
    {
        return $this->ord;
    }

    public function set_ord($order)
    {
        $order = (int) $order;
        $sql = 'UPDATE feed_entry_elements SET ord = :ord WHERE id = :item_id';
        $stmt = $this->appbox->get_connection()->prepare($sql);
        $stmt->execute(array(':ord'     => $order, ':item_id' => $this->get_id()));
        $stmt->closeCursor();

        $this->ord = $order;

        return $this;
    }

    /**
     *
     * @return void
     */
    public function delete()
    {
        $sql = 'DELETE FROM feed_entry_elements WHERE id = :id';
        $stmt = $this->appbox->get_connection()->prepare($sql);
        $stmt->execute(array(':id' => $this->get_id()));
        $stmt->closeCursor();

        return;
    }

    /**
     *
     * @param  appbox             $appbox
     * @param  Feed_Entry_Adapter $entry
     * @param  record_adapter     $record
     * @return Feed_Entry_Item
     */
    public static function create(appbox $appbox, Feed_Entry_Adapter $entry, record_adapter $record)
    {
        $sql = 'SELECT (MAX(ord)+1) as sorter FROM feed_entry_elements
            WHERE entry_id = :entry_id';

        $stmt = $appbox->get_connection()->prepare($sql);
        $stmt->execute(array(':entry_id' => $entry->get_id()));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $sorter = ($row && $row['sorter'] > 0) ? (int) $row['sorter'] : 1;

        $sql = 'INSERT INTO feed_entry_elements
            (id, entry_id, sbas_id, record_id, ord)
            VALUES (null, :entry_id, :sbas_id, :record_id, :ord)';

        $params = array(
            ':entry_id'  => $entry->get_id()
            , ':sbas_id'   => $record->get_sbas_id()
            , ':record_id' => $record->get_record_id()
            , ':ord'       => $sorter
        );

        $stmt = $appbox->get_connection()->prepare($sql);
        $stmt->execute($params);
        $stmt->closeCursor();

        $item_id = $appbox->get_connection()->lastInsertId();

        $entry->delete_data_from_cache(Feed_Entry_Adapter::CACHE_ELEMENTS);

        return new self($appbox, $entry, $item_id);
    }

    public function get_cache_key($option = null)
    {
        return 'feedentryitem_' . $this->get_id() . '_' . ($option ? '_' . $option : '');
    }

    public function get_data_from_cache($option = null)
    {
        return $this->appbox->get_data_from_cache($this->get_cache_key($option));
    }

    public function set_data_to_cache($value, $option = null, $duration = 0)
    {
        return $this->appbox->set_data_to_cache($value, $this->get_cache_key($option), $duration);
    }

    public function delete_data_from_cache($option = null)
    {
        return $this->appbox->delete_data_from_cache($this->get_cache_key($option));
    }
}
