<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Collection\CollectionReference;
use Alchemy\Phrasea\Collection\CollectionRepository;
use Alchemy\Phrasea\Core\Event\Collection\CollectionEvent;
use Alchemy\Phrasea\Core\Event\Collection\CollectionEvents;
use Alchemy\Phrasea\Core\Event\Collection\CreatedEvent;
use Alchemy\Phrasea\Core\Event\Collection\NameChangedEvent;
use Alchemy\Phrasea\Core\Thumbnail\ThumbnailedElement;
use Alchemy\Phrasea\Core\Thumbnail\ThumbnailManager;
use Alchemy\Phrasea\Exception\InvalidArgumentException;
use Alchemy\Phrasea\Model\Entities\User;
use Doctrine\DBAL\Driver\Connection;
use Symfony\Component\HttpFoundation\File\File;

class collection implements cache_cacheableInterface, ThumbnailedElement
{

    const PIC_LOGO = 'minilogos';
    const PIC_WM = 'wm';
    const PIC_STAMP = 'stamp';
    const PIC_PRESENTATION = 'presentation';

    private static $_logos = [];
    private static $_stamps = [];
    private static $_watermarks = [];
    private static $_presentations = [];
    private static $_collections = [];

    private static function getNewOrder(Connection $conn, $sbas_id)
    {
        $sql = "SELECT GREATEST(0, MAX(ord)) + 1 AS ord FROM bas WHERE sbas_id = :sbas_id";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':sbas_id' => $sbas_id]);
        $ord = $stmt->fetch(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        return $ord['ord'] ?: 1;
    }

    public static function create(Application $app, databox $databox, appbox $appbox, $name, User $user = null)
    {
        $sbas_id = $databox->get_sbas_id();
        $connbas = $databox->get_connection();
        $conn = $appbox->get_connection();
        $new_bas = false;

        $prefs = <<<EOT
<?xml version="1.0" encoding="UTF-8"?>
<baseprefs>
    <status>0</status>
    <sugestedValues></sugestedValues>
</baseprefs>
EOT;

        $sql = "INSERT INTO coll (coll_id, asciiname, prefs, logo)
                VALUES (null, :name, :prefs, '')";

        $params = [
            ':name' => $name,
            'prefs'  => $prefs,
        ];

        $stmt = $connbas->prepare($sql);
        $stmt->execute($params);
        $stmt->closeCursor();

        $new_id = (int) $connbas->lastInsertId();

        $sql = "INSERT INTO bas (base_id, active, ord, server_coll_id, sbas_id, aliases)
            VALUES
            (null, 1, :ord, :server_coll_id, :sbas_id, '')";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':server_coll_id' => $new_id,
            ':sbas_id' => $sbas_id,
            ':ord' => self::getNewOrder($conn, $sbas_id),
        ]);
        $stmt->closeCursor();

        $new_bas = $conn->lastInsertId();
        $databox->delete_data_from_cache(databox::CACHE_COLLECTIONS);
        $appbox->delete_data_from_cache(appbox::CACHE_LIST_BASES);

        phrasea::reset_baseDatas($appbox);

        $collection = self::getByCollectionId($app, $databox, $new_id);

        if (null !== $user) {
            $collection->set_admin($new_bas, $user);
        }

        $app['dispatcher']->dispatch(CollectionEvents::CREATED, new CreatedEvent($collection));

        return $collection;
    }

    public static function mount_collection(Application $app, databox $databox, $coll_id, User $user)
    {
        $sql = "INSERT INTO bas (base_id, active, server_coll_id, sbas_id, aliases, ord)
            VALUES
            (null, 1, :server_coll_id, :sbas_id, '', :ord)";
        $stmt = $databox->get_appbox()->get_connection()->prepare($sql);
        $stmt->execute([
            ':server_coll_id' => $coll_id,
            ':sbas_id'        => $databox->get_sbas_id(),
            ':ord'            => self::getNewOrder($databox->get_appbox()->get_connection(), $databox->get_sbas_id()),
        ]);
        $stmt->closeCursor();

        $new_bas = $databox->get_appbox()->get_connection()->lastInsertId();
        $databox->get_appbox()->delete_data_from_cache(appbox::CACHE_LIST_BASES);

        $databox->delete_data_from_cache(databox::CACHE_COLLECTIONS);

        cache_databox::update($app, $databox->get_sbas_id(), 'structure');

        phrasea::reset_baseDatas($databox->get_appbox());

        $coll = self::getByBaseId($app, $new_bas);
        $coll->set_admin($new_bas, $user);

        return $new_bas;
    }

    public static function getLogo($base_id, Application $app, $printname = false)
    {
        $base_id_key = $base_id . '_' . ($printname ? '1' : '0');

        if ( ! isset(self::$_logos[$base_id_key])) {

            if (is_file($app['root.path'] . '/config/minilogos/' . $base_id)) {
                $name = phrasea::bas_labels($base_id, $app);
                self::$_logos[$base_id_key] = '<img title="' . $name
                    . '" src="/custom/minilogos/' . $base_id . '" />';
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

    public static function purge()
    {
        self::$_collections = [];
    }

    /**
     * @param  Application $app
     * @param  int         $base_id
     * @return collection
     */
    public static function getByBaseId(Application $app, $base_id)
    {
        /** @var CollectionRepository $repository */
        $repository = $app['repo.collections'];
        $collection = $repository->find($base_id);

        if (! $collection) {
            throw new Exception_Databox_CollectionNotFound(sprintf("Collection with base_id %s could not be found", $base_id));
        }

        if (!$app['conf.restrictions']->isCollectionAvailable($collection)) {
            throw new Exception_Databox_CollectionNotFound('Collection `' . $collection->get_base_id() . '` is not available here.');
        }

        return $collection;
    }

    /**
     * @param  Application $app
     * @param  databox     $databox
     * @param  int         $coll_id
     * @return collection
     */
    public static function getByCollectionId(Application $app, databox $databox, $coll_id)
    {
        assert(is_int($coll_id));

        /** @var CollectionRepository $repository */
        $repository = $app['repo.collections'];
        $collection = $repository->findByCollectionId($databox->get_sbas_id(), $coll_id);

        if (! $collection) {
            throw new Exception_Databox_CollectionNotFound(sprintf("Collection with base_id %s could not be found", $base_id));
        }

        if (!$app['conf.restrictions']->isCollectionAvailable($collection)) {
            throw new Exception_Databox_CollectionNotFound('Collection `' . $collection->get_base_id() . '` is not available here.');
        }

        return $collection;
    }

    /**
     * @var Application
     */
    protected $app;

    /**
     * @var databox
     */
    protected $databox;

    /**
     * @var CollectionReference
     */
    protected $reference;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $preferences;

    /**
     * @var string
     */
    protected $pub_wm;

    /**
     * @var string[]
     */
    protected $labels = [];

    /**
     * @var int[]|string
     */
    protected $binary_logo;

    /**
     * @param Application $app
     * @param $baseId
     * @param CollectionReference $reference
     * @param array $row
     */
    public function __construct(Application $app, $baseId, CollectionReference $reference, array $row)
    {
        $this->app = $app;
        $this->databox = $app->getApplicationBox()->get_databox($reference->getDataboxId());

        $this->reference = $reference;

        $this->name = $row['asciiname'];
        $this->available = true;
        $this->pub_wm = $row['pub_wm'];
        $this->preferences = $row['prefs'];
        $this->labels = [
            'fr' => $row['label_fr'],
            'en' => $row['label_en'],
            'de' => $row['label_de'],
            'nl' => $row['label_nl'],
        ];
    }

    public function __sleep()
    {
        return array(
            'reference',
            'name',
            'preferences',
            'pub_wm',
            'labels',
            'binary_logo'
        );
    }

    public function hydrate(Application $app)
    {
        $this->app = $app;
        $this->databox = $app->getApplicationBox()->get_databox($this->reference->getDataboxId());
    }

    private function dispatch($eventName, CollectionEvent $event)
    {
        $this->app['dispatcher']->dispatch($eventName, $event);
    }

    public function enable(appbox $appbox)
    {
        $sql = 'UPDATE bas SET active = "1" WHERE base_id = :base_id';
        $stmt = $appbox->get_connection()->prepare($sql);
        $stmt->execute([':base_id' => $this->get_base_id()]);
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
        return $this->reference->getDisplayIndex();
    }

    public function set_ord($ord)
    {
        $this->app->getApplicationBox()->set_collection_order($this, $ord);
        $this->delete_data_from_cache();
        $this->app->getApplicationBox()->delete_data_from_cache(appbox::CACHE_LIST_BASES);

        $this->reference->setDisplayIndex($ord);

        return $this;
    }

    public function disable(appbox $appbox)
    {
        $sql = 'UPDATE bas SET active=0 WHERE base_id = :base_id';
        $stmt = $appbox->get_connection()->prepare($sql);
        $stmt->execute([':base_id'       => $this->get_base_id()]);
        $stmt->closeCursor();
        $this->is_active = false;
        $this->delete_data_from_cache();
        $appbox->delete_data_from_cache(appbox::CACHE_LIST_BASES);
        $this->databox->delete_data_from_cache(databox::CACHE_COLLECTIONS);
        cache_databox::update($this->app, $this->databox->get_sbas_id(), 'structure');

        $this->reference->disable();

        return $this;
    }

    public function empty_collection($pass_quantity = 100)
    {
        $pass_quantity = (int) $pass_quantity > 200 ? 200 : (int) $pass_quantity;
        $pass_quantity = (int) $pass_quantity < 10 ? 10 : (int) $pass_quantity;

        $sql = "SELECT record_id FROM record WHERE coll_id = :coll_id
            ORDER BY record_id DESC LIMIT 0, " . $pass_quantity;

        $stmt = $this->databox->get_connection()->prepare($sql);
        $stmt->execute([':coll_id' => $this->get_coll_id()]);
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        foreach ($rs as $row) {
            $record = $this->databox->get_record($row['record_id']);
            $record->delete();
            unset($record);
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function is_active()
    {
        return $this->reference->isActive();
    }

    /**
     *
     * @return databox
     */
    public function get_databox()
    {
        return $this->databox;
    }

    /**
     * @return \Doctrine\DBAL\Connection
     */
    public function get_connection()
    {
        return $this->databox->get_connection();
    }

    /**
     * @return int
     */
    public function getRootIdentifier()
    {
        return $this->reference->getBaseId();
    }

    /**
     * @param string $thumbnailType
     * @param File $file
     */
    public function updateThumbnail($thumbnailType, File $file = null)
    {
        switch ($thumbnailType) {
            case ThumbnailManager::TYPE_WM;
                $this->reset_watermark();
                break;
            case ThumbnailManager::TYPE_LOGO:
                $this->update_logo($file);
                break;
            case ThumbnailManager::TYPE_PRESENTATION:
                break;
            case ThumbnailManager::TYPE_STAMP:
                $this->reset_stamp();
                break;
            default:
                throw new \InvalidArgumentException('Unsupported thumbnail type.');
        }
    }

    public function set_public_presentation($publi)
    {
        if (in_array($publi, ['none', 'wm', 'stamp'])) {
            $sql = 'UPDATE coll SET pub_wm = :pub_wm WHERE coll_id = :coll_id';
            $stmt = $this->get_connection()->prepare($sql);
            $stmt->execute([':pub_wm'  => $publi, ':coll_id' => $this->get_coll_id()]);
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
        $stmt->execute([':asciiname' => $name, ':coll_id'   => $this->get_coll_id()]);
        $stmt->closeCursor();

        $this->name = $name;

        $this->delete_data_from_cache();

        phrasea::reset_baseDatas($this->databox->get_appbox());

        $this->dispatch(CollectionEvents::NAME_CHANGED, new NameChangedEvent($this));

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
        $stmt->execute([':label' => $label, ':coll_id'   => $this->get_coll_id()]);
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
        $stmt->execute([':coll_id' => $this->get_coll_id()]);
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
        $stmt->execute([':coll_id' => $this->get_coll_id()]);
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $ret = [];
        foreach ($rs as $row) {
            $ret[] = [
                "coll_id" => (int) $row["coll_id"],
                "name"    => $row["name"],
                "amount"  => (int) $row["n"],
                "size"    => (int) $row["size"]];
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
        $stmt->execute([':logo'    => $this->binary_logo, ':coll_id' => $this->get_coll_id()]);
        $stmt->closeCursor();

        return $this;
    }

    public function reset_watermark()
    {
        $sql = 'SELECT path, file FROM record r INNER JOIN subdef s USING(record_id)
            WHERE r.coll_id = :coll_id AND r.type="image" AND s.name="preview"';

        $stmt = $this->get_connection()->prepare($sql);
        $stmt->execute([':coll_id' => $this->get_coll_id()]);

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

        $params = [':coll_id' => $this->get_coll_id()];

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
        $stmt->execute([':coll_id' => $this->get_coll_id()]);
        $stmt->closeCursor();

        $appbox = $this->databox->get_appbox();

        $sql = "DELETE FROM bas WHERE base_id = :base_id";
        $stmt = $appbox->get_connection()->prepare($sql);
        $stmt->execute([':base_id' => $this->get_base_id()]);
        $stmt->closeCursor();

        $sql = "DELETE FROM basusr WHERE base_id = :base_id";
        $stmt = $appbox->get_connection()->prepare($sql);
        $stmt->execute([':base_id' => $this->get_base_id()]);
        $stmt->closeCursor();

        $this->app['manipulator.registration']->deleteRegistrationsOnCollection($this);

        $this->get_databox()->delete_data_from_cache(databox::CACHE_COLLECTIONS);
        $appbox->delete_data_from_cache(appbox::CACHE_LIST_BASES);
        phrasea::reset_baseDatas($appbox);

        return;
    }

    public function get_binary_minilogos()
    {
        return $this->binary_logo;
    }

    public function get_base_id()
    {
        return $this->reference->getBaseId();
    }

    public function get_sbas_id()
    {
        return $this->reference->getDataboxId();
    }

    public function get_coll_id()
    {
        return $this->reference->getCollectionId();
    }

    public function get_prefs()
    {
        return $this->preferences;
    }

    public function set_prefs(DOMDocument $dom)
    {
        $this->preferences = $dom->saveXML();

        $sql = "UPDATE coll SET prefs = :prefs WHERE coll_id = :coll_id";
        $stmt = $this->get_connection()->prepare($sql);
        $stmt->execute([':prefs'   => $this->preferences, ':coll_id' => $this->get_coll_id()]);
        $stmt->closeCursor();

        $this->delete_data_from_cache();

        return $this->preferences;
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
        $params = [':base_id' => $this->get_base_id()];

        $query = $app['phraseanet.user-query'];
        $total = $query->on_base_ids([$this->get_base_id()])
                ->include_phantoms(false)
                ->include_special_users(true)
                ->include_invite(true)
                ->include_templates(true)->get_total();
        $n = 0;
        while ($n < $total) {
            $results = $query->limit($n, 50)->execute()->get_results();
            foreach ($results as $user) {
                $app->getAclForUser($user)->delete_data_from_cache(ACL::CACHE_RIGHTS_SBAS);
                $app->getAclForUser($user)->delete_data_from_cache(ACL::CACHE_RIGHTS_BAS);
            }
            $n+=50;
        }

        $sql = "DELETE FROM basusr WHERE base_id = :base_id";
        $stmt = $app->getApplicationBox()->get_connection()->prepare($sql);
        $stmt->execute($params);
        $stmt->closeCursor();

        $sql = "DELETE FROM bas WHERE base_id = :base_id";
        $stmt = $app->getApplicationBox()->get_connection()->prepare($sql);
        $stmt->execute($params);
        $stmt->closeCursor();

        $this->app['manipulator.registration']->deleteRegistrationsOnCollection($this);

        phrasea::reset_baseDatas($app['phraseanet.appbox']);

        return $this;
    }

    public function set_admin($base_id, User $user)
    {

        $rights = [
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
        ];

        $this->app->getAclForUser($user)->update_rights_to_base($base_id, $rights);

        return true;
    }

    public function get_cache_key($option = null)
    {
        return 'collection_' . $this->get_coll_id() . ($option ? '_' . $option : '');
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

    /**
     * Tells whether registration is activated for provided collection or not.
     *
     * @return boolean
     */
    public function isRegistrationEnabled()
    {
        if (false === $xml = simplexml_load_string($this->get_prefs())) {
            return false;
        }

        $element = $xml->xpath('/baseprefs/caninscript');

        if (count($element) === 0) {
            return $this->databox->isRegistrationEnabled();
        }

        foreach ($element as $caninscript) {
            if (false !== (Boolean) (string) $caninscript) {
                return true;
            }
        }

        return false;
    }

    /**
     * Gets terms of use.
     *
     * @param \collection $collection
     *
     * @return null|string
     */
    public function getTermsOfUse()
    {
        if (false === $xml = simplexml_load_string($this->get_prefs())) {
            return;
        }

        foreach ($xml->xpath('/baseprefs/cgu') as $sbpcgu) {
            return $sbpcgu->saveXML();
        }
    }
}
