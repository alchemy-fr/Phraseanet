<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;

use Alchemy\Phrasea\Exception\InvalidArgumentException;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class collection implements cache_cacheableInterface
{
    protected $base_id;
    protected $sbas_id;
    protected $coll_id;
    protected $available = false;
    protected $name;
    protected $prefs;
    protected $pub_wm;
    protected $labels = array();
    private static $_logos = array();
    private static $_stamps = array();
    private static $_watermarks = array();
    private static $_presentations = array();
    private static $_collections = array();
    protected $databox;
    protected $is_active;
    protected $binary_logo;
    protected $ord;
    protected $app;

    const PIC_LOGO = 'minilogos';
    const PIC_WM = 'wm';
    const PIC_STAMP = 'stamp';
    const PIC_PRESENTATION = 'presentation';

    protected function __construct(Application $app, $coll_id, databox $databox)
    {
        $this->app = $app;
        $this->databox = $databox;
        $this->sbas_id = (int) $databox->get_sbas_id();
        $this->coll_id = (int) $coll_id;
        $this->load();

        return $this;
    }

    protected function load()
    {
        try {
            $datas = $this->get_data_from_cache();
            $this->is_active = $datas['is_active'];
            $this->base_id = $datas['base_id'];
            $this->available = $datas['available'];
            $this->pub_wm = $datas['pub_wm'];
            $this->name = $datas['name'];
            $this->ord = $datas['ord'];
            $this->prefs = $datas['prefs'];
            $this->labels = $datas['labels'];

            return $this;
        } catch (Exception $e) {

        }

        $connbas = $this->databox->get_connection();
        $sql = 'SELECT
                    asciiname, prefs, pub_wm, coll_id,
                    label_en, label_fr, label_de, label_nl
                FROM coll WHERE coll_id = :coll_id';
        $stmt = $connbas->prepare($sql);
        $stmt->execute(array(':coll_id' => $this->coll_id));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ( ! $row)
            throw new Exception('Unknown collection ' . $this->coll_id . ' on ' . $this->databox->get_dbname());

        $this->available = true;
        $this->pub_wm = $row['pub_wm'];
        $this->name = $row['asciiname'];
        $this->prefs = $row['prefs'];
        $this->labels = array(
            'fr' => $row['label_fr'],
            'en' => $row['label_en'],
            'de' => $row['label_de'],
            'nl' => $row['label_nl'],
        );

        $conn = connection::getPDOConnection($this->app);

        $sql = 'SELECT server_coll_id, sbas_id, base_id, active, ord FROM bas
            WHERE server_coll_id = :coll_id AND sbas_id = :sbas_id';

        $stmt = $conn->prepare($sql);
        $stmt->execute(array(':coll_id' => $this->coll_id, ':sbas_id' => $this->databox->get_sbas_id()));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $this->is_active = false;

        if ($row) {
            $this->is_active = ! ! $row['active'];
            $this->base_id = (int) $row['base_id'];
            $this->ord = (int) $row['ord'];
        }

        $stmt->closeCursor();

        $datas = array(
            'is_active' => $this->is_active
            , 'base_id'   => $this->base_id
            , 'available' => $this->available
            , 'pub_wm'    => $this->pub_wm
            , 'name'      => $this->name
            , 'ord'       => $this->ord
            , 'prefs'     => $this->prefs
            , 'labels'    => $this->labels
        );

        $this->set_data_to_cache($datas);

        return $this;
    }

    public function enable(appbox $appbox)
    {
        $sql = 'UPDATE bas SET active = "1" WHERE base_id = :base_id';
        $stmt = $appbox->get_connection()->prepare($sql);
        $stmt->execute(array(':base_id' => $this->get_base_id()));
        $stmt->closeCursor();

        $this->is_active = true;
        $this->delete_data_from_cache();
        $appbox->delete_data_from_cache(appbox::CACHE_LIST_BASES);
        $this->databox->delete_data_from_cache(databox::CACHE_COLLECTIONS);
        cache_databox::update($this->app, $this->databox->get_sbas_id(), 'structure');

        return $this;
    }

    public function get_ord()
    {
        return $this->ord;
    }

    public function set_ord($ord)
    {
        $this->app['phraseanet.appbox']->set_collection_order($this, $ord);
        $this->delete_data_from_cache();
        $this->app['phraseanet.appbox']->delete_data_from_cache(appbox::CACHE_LIST_BASES);

        return $this;
    }

    public function disable(appbox $appbox)
    {
        $sql = 'UPDATE bas SET active=0 WHERE base_id = :base_id';
        $stmt = $appbox->get_connection()->prepare($sql);
        $stmt->execute(array(':base_id'       => $this->get_base_id()));
        $stmt->closeCursor();
        $this->is_active = false;
        $this->delete_data_from_cache();
        $appbox->delete_data_from_cache(appbox::CACHE_LIST_BASES);
        $this->databox->delete_data_from_cache(databox::CACHE_COLLECTIONS);
        cache_databox::update($this->app, $this->databox->get_sbas_id(), 'structure');

        return $this;
    }

    public function empty_collection($pass_quantity = 100)
    {
        $pass_quantity = (int) $pass_quantity > 200 ? 200 : (int) $pass_quantity;
        $pass_quantity = (int) $pass_quantity < 10 ? 10 : (int) $pass_quantity;

        $sql = "SELECT record_id FROM record WHERE coll_id = :coll_id
            ORDER BY record_id DESC LIMIT 0, " . $pass_quantity;

        $stmt = $this->databox->get_connection()->prepare($sql);
        $stmt->execute(array(':coll_id' => $this->get_coll_id()));
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        foreach ($rs as $row) {
            $record = $this->databox->get_record($row['record_id']);
            $record->delete();
            unset($record);
        }

        return $this;
    }

    public function is_active()
    {
        return $this->is_active;
    }

    /**
     *
     * @return databox
     */
    public function get_databox()
    {
        return $this->databox;
    }

    public function get_connection()
    {
        return $this->databox->get_connection();
    }

    public function set_public_presentation($publi)
    {
        if (in_array($publi, array('none', 'wm', 'stamp'))) {
            $sql = 'UPDATE coll SET pub_wm = :pub_wm WHERE coll_id = :coll_id';
            $stmt = $this->get_connection()->prepare($sql);
            $stmt->execute(array(':pub_wm'  => $publi, ':coll_id' => $this->get_coll_id()));
            $stmt->closeCursor();

            $this->pub_wm = $publi;

            $this->delete_data_from_cache();
        }

        return $this;
    }

    public function set_name($name)
    {
        $name = trim(strip_tags($name));

        if ($name === '')
            throw new Exception_InvalidArgument ();

        $sql = "UPDATE coll SET asciiname = :asciiname
            WHERE coll_id = :coll_id";
        $stmt = $this->get_connection()->prepare($sql);
        $stmt->execute(array(':asciiname' => $name, ':coll_id'   => $this->get_coll_id()));
        $stmt->closeCursor();

        $this->name = $name;

        $this->delete_data_from_cache();

        phrasea::reset_baseDatas($this->databox->get_appbox());

        return $this;
    }

    public function set_label($code, $label)
    {
        if (!array_key_exists($code, $this->labels)) {
            throw new InvalidArgumentException(sprintf('Code %s is not defined', $code));
        }

        $sql = "UPDATE coll SET label_$code = :label
            WHERE coll_id = :coll_id";
        $stmt = $this->get_connection()->prepare($sql);
        $stmt->execute(array(':label' => $label, ':coll_id'   => $this->get_coll_id()));
        $stmt->closeCursor();

        $this->labels[$code] = $label;

        $this->delete_data_from_cache();

        phrasea::reset_baseDatas($this->databox->get_appbox());

        return $this;
    }

    public function get_label($code, $substitute = true)
    {
        if (!array_key_exists($code, $this->labels)) {
            throw new InvalidArgumentException(sprintf('Code %s is not defined', $code));
        }

        if ($substitute) {
            return isset($this->labels[$code]) ? $this->labels[$code] : $this->name;
        } else {
            return $this->labels[$code];
        }
    }

    public function get_record_amount()
    {
        $sql = "SELECT COUNT(record_id) AS n FROM record WHERE coll_id = :coll_id";
        $stmt = $this->get_connection()->prepare($sql);
        $stmt->execute(array(':coll_id' => $this->get_coll_id()));
        $rowbas = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $amount = $rowbas ? (int) $rowbas["n"] : null;

        return $amount;
    }

    public function get_record_details()
    {

        $sql = "SELECT record.coll_id,name,COALESCE(asciiname, CONCAT('_',record.coll_id)) AS asciiname,
                    SUM(1) AS n, SUM(size) AS size
                  FROM record NATURAL JOIN subdef
                    INNER JOIN coll ON record.coll_id=coll.coll_id AND coll.coll_id = :coll_id
                  GROUP BY record.coll_id, subdef.name";

        $stmt = $this->get_connection()->prepare($sql);
        $stmt->execute(array(':coll_id' => $this->get_coll_id()));
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $ret = array();
        foreach ($rs as $row) {
            $ret[] = array(
                "coll_id" => (int) $row["coll_id"],
                "name"    => $row["name"],
                "amount"  => (int) $row["n"],
                "size"    => (int) $row["size"]);
        }

        return $ret;
    }

    public function update_logo(\SplFileInfo $pathfile = null)
    {
        if (is_null($pathfile)) {
            $this->binary_logo = null;
        } else {
            $this->binary_logo = file_get_contents($pathfile->getPathname());
        }

        $sql = "UPDATE coll SET logo = :logo, majLogo=NOW() WHERE coll_id = :coll_id";
        $stmt = $this->get_connection()->prepare($sql);
        $stmt->execute(array(':logo'    => $this->binary_logo, ':coll_id' => $this->get_coll_id()));
        $stmt->closeCursor();

        return $this;
    }

    public function reset_watermark()
    {

        $sql = 'SELECT path, file FROM record r INNER JOIN subdef s USING(record_id)
            WHERE r.coll_id = :coll_id AND r.type="image" AND s.name="preview"';

        $stmt = $this->get_connection()->prepare($sql);
        $stmt->execute(array(':coll_id' => $this->get_coll_id()));

        while ($row2 = $stmt->fetch(PDO::FETCH_ASSOC)) {
            @unlink(p4string::addEndSlash($row2['path']) . 'watermark_' . $row2['file']);
        }
        $stmt->closeCursor();

        return $this;
    }

    public function reset_stamp($record_id = null)
    {

        $sql = 'SELECT path, file FROM record r INNER JOIN subdef s USING(record_id)
            WHERE r.coll_id = :coll_id
              AND r.type="image" AND s.name IN ("preview", "document")';

        $params = array(':coll_id' => $this->get_coll_id());

        if ($record_id) {
            $sql .= ' AND record_id = :record_id';
            $params[':record_id'] = $record_id;
        }

        $stmt = $this->get_connection()->prepare($sql);
        $stmt->execute($params);

        while ($row2 = $stmt->fetch(PDO::FETCH_ASSOC)) {
            @unlink(p4string::addEndSlash($row2['path']) . 'stamp_' . $row2['file']);
        }
        $stmt->closeCursor();

        return $this;
    }

    public function delete()
    {
        while ($this->get_record_amount() > 0) {
            $this->empty_collection();
        }

        $sql = "DELETE FROM coll WHERE coll_id = :coll_id";
        $stmt = $this->get_connection()->prepare($sql);
        $stmt->execute(array(':coll_id' => $this->get_coll_id()));
        $stmt->closeCursor();

        $appbox = $this->databox->get_appbox();

        $sql = "DELETE FROM bas WHERE base_id = :base_id";
        $stmt = $appbox->get_connection()->prepare($sql);
        $stmt->execute(array(':base_id' => $this->get_base_id()));
        $stmt->closeCursor();

        $sql = "DELETE FROM basusr WHERE base_id = :base_id";
        $stmt = $appbox->get_connection()->prepare($sql);
        $stmt->execute(array(':base_id' => $this->get_base_id()));
        $stmt->closeCursor();

        $sql = "DELETE FROM demand WHERE base_id = :base_id";
        $stmt = $appbox->get_connection()->prepare($sql);
        $stmt->execute(array(':base_id' => $this->get_base_id()));
        $stmt->closeCursor();

        $this->get_databox()->delete_data_from_cache(databox::CACHE_COLLECTIONS);

        return;
    }

    public function get_binary_minilogos()
    {
        return $this->binary_logo;
    }

    /**
     *
     * @param  Application $app
     * @param  int         $base_id
     * @return collection
     */
    public static function get_from_base_id(Application $app, $base_id)
    {
        $coll_id = phrasea::collFromBas($app, $base_id);
        $sbas_id = phrasea::sbasFromBas($app, $base_id);
        if (! $sbas_id || ! $coll_id) {
            throw new Exception_Databox_CollectionNotFound(sprintf("Collection could not be found"));
        }
        $databox = $app['phraseanet.appbox']->get_databox($sbas_id);

        return self::get_from_coll_id($app, $databox, $coll_id);
    }

    /**
     *
     * @param  Application $app
     * @param  databox     $databox
     * @param  int         $coll_id
     * @return collection
     */
    public static function get_from_coll_id(Application $app, databox $databox, $coll_id)
    {
        assert(is_int($coll_id));

        $key = sprintf('%d_%d', $databox->get_sbas_id(), $coll_id);
        if ( ! isset(self::$_collections[$key])) {
            self::$_collections[$key] = new self($app, $coll_id, $databox);
        }

        return self::$_collections[$key];
    }

    public function get_base_id()
    {
        return $this->base_id;
    }

    public function get_sbas_id()
    {
        return $this->sbas_id;
    }

    public function get_coll_id()
    {
        return $this->coll_id;
    }

    public function get_prefs()
    {
        return $this->prefs;
    }

    public function set_prefs(DOMDocument $dom)
    {
        $this->prefs = $dom->saveXML();

        $sql = "UPDATE coll SET prefs = :prefs WHERE coll_id = :coll_id";
        $stmt = $this->get_connection()->prepare($sql);
        $stmt->execute(array(':prefs'   => $this->prefs, ':coll_id' => $this->get_coll_id()));
        $stmt->closeCursor();

        $this->delete_data_from_cache();

        return $this->prefs;
    }

    public function get_name()
    {
        return $this->name;
    }

    public function get_pub_wm()
    {
        return $this->pub_wm;
    }

    public function is_available()
    {
        return $this->available;
    }

    public function unmount_collection(Application $app)
    {
        $params = array(':base_id' => $this->get_base_id());

        $query = new User_Query($app);
        $total = $query->on_base_ids(array($this->get_base_id()))
                ->include_phantoms(false)
                ->include_special_users(true)
                ->include_invite(true)
                ->include_templates(true)->get_total();
        $n = 0;
        while ($n < $total) {
            $results = $query->limit($n, 50)->execute()->get_results();
            foreach ($results as $user) {
                $user->ACL()->delete_data_from_cache(ACL::CACHE_RIGHTS_SBAS);
                $user->ACL()->delete_data_from_cache(ACL::CACHE_RIGHTS_BAS);
            }
            $n+=50;
        }

        $sql = "DELETE FROM basusr WHERE base_id = :base_id";
        $stmt = $app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute($params);
        $stmt->closeCursor();

        $sql = "DELETE FROM bas WHERE base_id = :base_id";
        $stmt = $app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute($params);
        $stmt->closeCursor();

        $sql = "DELETE FROM demand WHERE base_id = :base_id";
        $stmt = $app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute($params);
        $stmt->closeCursor();

        phrasea::reset_baseDatas($app['phraseanet.appbox']);

        return $this;
    }

    public static function create(Application $app, databox $databox, appbox $appbox, $name, User_Adapter $user = null)
    {
        $sbas_id = $databox->get_sbas_id();
        $connbas = $databox->get_connection();
        $conn = $appbox->get_connection();
        $new_bas = false;

        $prefs = '<?xml version="1.0" encoding="UTF-8"?>
            <baseprefs>
                <status>0</status>
                <sugestedValues>
                </sugestedValues>
            </baseprefs>';

        $sql = "INSERT INTO coll (coll_id, asciiname, prefs, logo)
                VALUES (null, :name, :prefs, '')";

        $params = array(
            ':name' => $name,
            'prefs'  => $prefs,
        );

        $stmt = $connbas->prepare($sql);
        $stmt->execute($params);
        $stmt->closeCursor();

        $new_id = (int) $connbas->lastInsertId();

        $sql = "INSERT INTO bas (base_id, active, server_coll_id, sbas_id, aliases)
            VALUES
            (null, 1, :server_coll_id, :sbas_id, '')";
        $stmt = $conn->prepare($sql);
        $stmt->execute(array(':server_coll_id' => $new_id, ':sbas_id'        => $sbas_id));
        $stmt->closeCursor();

        $new_bas = $conn->lastInsertId();
        $databox->delete_data_from_cache(databox::CACHE_COLLECTIONS);

        $appbox->delete_data_from_cache(appbox::CACHE_LIST_BASES);
        cache_databox::update($app, $sbas_id, 'structure');

        phrasea::reset_baseDatas($appbox);

        $collection = self::get_from_coll_id($app, $databox, $new_id);

        if (null !== $user) {
            $collection->set_admin($new_bas, $user);
        }

        return $collection;
    }

    public function set_admin($base_id, user_adapter $user)
    {

        $rights = array(
            "canputinalbum"   => "1",
            "candwnldhd"      => "1",
            "nowatermark"     => "1",
            "candwnldpreview" => "1",
            "cancmd"          => "1",
            "canadmin"        => "1",
            "actif"           => "1",
            "canreport"       => "1",
            "canpush"         => "1",
            "basusr_infousr"  => "",
            "canaddrecord"    => "1",
            "canmodifrecord"  => "1",
            "candeleterecord" => "1",
            "chgstatus"       => "1",
            "imgtools"        => "1",
            "manage"          => "1",
            "modify_struct"   => "1"
        );

        $user->ACL()->update_rights_to_base($base_id, $rights);

        return true;
    }

    public static function mount_collection(Application $app, databox $databox, $coll_id, User_Adapter $user)
    {

        $sql = "INSERT INTO bas (base_id, active, server_coll_id, sbas_id, aliases)
            VALUES
            (null, 1, :server_coll_id, :sbas_id, '')";
        $stmt = $databox->get_appbox()->get_connection()->prepare($sql);
        $stmt->execute(array(':server_coll_id' => $coll_id, ':sbas_id'        => $sbas_id));
        $stmt->closeCursor();

        $new_bas = $databox->get_appbox()->get_connection()->lastInsertId();
        $databox->get_appbox()->delete_data_from_cache(appbox::CACHE_LIST_BASES);

        $databox->delete_data_from_cache(databox::CACHE_COLLECTIONS);

        cache_databox::update($app, $sbas_id, 'structure');

        phrasea::reset_baseDatas($databox->get_appbox());

        self::set_admin($new_bas, $user);

        return $new_bas;
    }

    public static function getLogo($base_id, Application $app, $printname = false)
    {
        $base_id_key = $base_id . '_' . ($printname ? '1' : '0');

        if ( ! isset(self::$_logos[$base_id_key])) {

            if (is_file($app['root.path'] . '/config/minilogos/' . $base_id)) {
                $name = phrasea::bas_labels($base_id, $app);
                self::$_logos[$base_id_key] = '<img title="' . $name
                    . '" src="' . $app['phraseanet.registry']->get('GV_STATIC_URL')
                    . '/custom/minilogos/' . $base_id . '" />';
            } elseif ($printname) {
                self::$_logos[$base_id_key] = phrasea::bas_labels($base_id, $app);
            }
        }

        return isset(self::$_logos[$base_id_key]) ? self::$_logos[$base_id_key] : '';
    }

    public static function getWatermark($base_id)
    {
        if ( ! isset(self::$_watermarks['base_id'])) {

            if (is_file(__DIR__  . '/../../config/wm/' . $base_id))
                self::$_watermarks['base_id'] = '<img src="/custom/wm/' . $base_id . '" />';
        }

        return isset(self::$_watermarks['base_id']) ? self::$_watermarks['base_id'] : '';
    }

    public static function getPresentation($base_id)
    {
        if ( ! isset(self::$_presentations['base_id'])) {

            if (is_file(__DIR__ . '/../../config/presentation/' . $base_id))
                self::$_presentations['base_id'] = '<img src="/custom/presentation/' . $base_id . '" />';
        }

        return isset(self::$_presentations['base_id']) ? self::$_presentations['base_id'] : '';
    }

    public static function getStamp($base_id)
    {
        if ( ! isset(self::$_stamps['base_id'])) {

            if (is_file(__DIR__ . '/../../config/stamp/' . $base_id))
                self::$_stamps['base_id'] = '<img src="/custom/stamp/' . $base_id . '" />';
        }

        return isset(self::$_stamps['base_id']) ? self::$_stamps['base_id'] : '';
    }

    public function get_cache_key($option = null)
    {
        return 'collection_' . $this->coll_id . ($option ? '_' . $option : '');
    }

    public function get_data_from_cache($option = null)
    {
        return $this->databox->get_data_from_cache($this->get_cache_key($option));
    }

    public function set_data_to_cache($value, $option = null, $duration = 0)
    {
        return $this->databox->set_data_to_cache($value, $this->get_cache_key($option), $duration);
    }

    public function delete_data_from_cache($option = null)
    {
        return $this->databox->delete_data_from_cache($this->get_cache_key($option));
    }
}
