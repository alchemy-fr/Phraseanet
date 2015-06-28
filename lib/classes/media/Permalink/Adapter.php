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
use Alchemy\Phrasea\Exception\RuntimeException;
use Guzzle\Http\Url;

/**
 *
 * @package     subdefs
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class media_Permalink_Adapter implements media_Permalink_Interface, cache_cacheableInterface
{

    public static function get(Application $app, databox $databox, media_subdef $media_subdef)
    {
        return new self($app, $databox, $media_subdef, true);
    }

    /**
     * @param databox $databox
     * @param media_subdef[] $subdefs
     */
    public static function loadForSubdefs(Application $app, databox $databox, array $subdefs)
    {
        $connection = $databox->get_connection();

        $query = 'SELECT p.id, p.subdef_id, p.token, p.activated, p.created_on, p.last_modified
              , p.label
            FROM permalinks p
            WHERE p.subdef_id IN (%s)';

        $params = array();

        foreach ($subdefs as $subdef) {
            $params[':id_' . $subdef->get_subdef_id()] = strtolower($subdef->get_subdef_id());
        }

        $query = sprintf($query, implode(', ', array_keys($params)));

        $statement = $connection->prepare($query);
        $statement->execute($params);
        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);
        $statement->closeCursor();

        $mappedSubdefs = array();

        foreach ($subdefs as $subdef) {
            $mappedSubdefs[$subdef->get_subdef_id()] = $subdef;
        }

        foreach ($rows as $row) {
            $link = new self($app, $databox, $mappedSubdefs[$row['subdef_id']], false);

            self::mapFromQuery($link, $row);

            $mappedSubdefs[$row['subdef_id']]->set_permalink($link);
        }
    }

    protected static function load(self $link)
    {
        try {
            return self::mapFromCache($link);
        } catch (\Exception $e) {

        }

        $sql = 'SELECT p.id, p.token, p.activated, p.created_on, p.last_modified
              , p.label
            FROM permalinks p
            WHERE p.subdef_id = :subdef_id';
        $stmt = $link->databox->get_connection()->prepare($sql);
        $stmt->execute(array(':subdef_id' => $link->media_subdef->get_subdef_id()));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if ( ! $row) {
            throw new Exception_Media_SubdefNotFound ();
        }

        self::mapFromQuery($link, $row);
        self::putInCache($link);
    }

    /**
     *
     * @var databox
     */
    protected $databox;

    /**
     *
     * @var media_subdef
     */
    protected $media_subdef;

    /**
     *
     * @var int
     */
    protected $id;

    /**
     *
     * @var string
     */
    protected $token;

    /**
     *
     * @var boolean
     */
    protected $is_activated;

    /**
     *
     * @var DateTime
     */
    protected $created_on;

    /**
     *
     * @var DateTime
     */
    protected $last_modified;

    /**
     *
     * @var string
     */
    protected $label;
    protected $app;

    /**
     *
     * @param  databox                 $databox
     * @param  media_subdef            $media_subdef
     * @return media_Permalink_Adapter
     */
    protected function __construct(Application $app, databox $databox, media_subdef $media_subdef, $load = true)
    {
        $this->app = $app;
        $this->databox = $databox;
        $this->media_subdef = $media_subdef;

        if ($load) {
            self::load($this);
        }

        return $this;
    }

    /**
     * @param media_Permalink_Adapter $link
     */
    protected static function mapFromCache(self $link)
    {
        $datas = $link->get_data_from_cache();

        $link->id = $datas['id'];
        $link->token = $datas['token'];
        $link->is_activated = $datas['is_activated'];
        $link->created_on = $datas['created_on'];
        $link->last_modified = $datas['last_modified'];
        $link->label = $datas['label'];
    }

    /**
     * @param media_Permalink_Adapter $link
     */
    protected static function putInCache(self $link)
    {
        $datas = array(
            'id' => $link->id,
            'token' => $link->token,
            'is_activated' => $link->is_activated,
            'created_on' => $link->created_on,
            'last_modified' => $link->last_modified,
            'label' => $link->label,
        );

        $link->set_data_to_cache($datas);
    }

    /**
     * @param media_Permalink_Adapter $link
     * @param $row
     */
    protected static function mapFromQuery(self $link, $row)
    {
        $link->id = (int)$row['id'];
        $link->token = $row['token'];
        $link->is_activated = !!$row['activated'];
        $link->created_on = new DateTime($row['created_on']);
        $link->last_modified = new DateTime($row['last_modified']);
        $link->label = $row['label'];
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
     * @return string
     */
    public function get_token()
    {
        return $this->token;
    }

    /**
     *
     * @return boolean
     */
    public function get_is_activated()
    {
        return $this->is_activated;
    }

    /**
     *
     * @return DateTime
     */
    public function get_created_on()
    {
        return $this->created_on;
    }

    /**
     *
     * @return DateTime
     */
    public function get_last_modified()
    {
        return $this->last_modified;
    }

    /**
     *
     * @return string
     */
    public function get_label()
    {
        return $this->label;
    }

    /**
     *
     * @return Url
     */
    public function get_url()
    {
        $label = $this->get_label() . '.' . pathinfo($this->media_subdef->get_file(), PATHINFO_EXTENSION);

        return Url::factory($this->app->url('permalinks_permalink', array(
            'sbas_id'   => $this->media_subdef->get_sbas_id(),
            'record_id' => $this->media_subdef->get_record_id(),
            'subdef'    => $this->media_subdef->get_name(),
            'label'     => $label,
            'token'     => $this->get_token(),
        )));
    }

    /**
     *
     * @return string
     */
    public function get_page()
    {
        return $this->app->url('permalinks_permaview', array(
            'sbas_id'   => $this->media_subdef->get_sbas_id(),
            'record_id' => $this->media_subdef->get_record_id(),
            'subdef'    => $this->media_subdef->get_name(),
            'token'     => $this->get_token(),
        ));
    }

    /**
     *
     * @param  string                  $token
     * @return media_Permalink_Adapter
     */
    protected function set_token($token)
    {
        $this->token = $token;

        $sql = 'UPDATE permalinks SET token = :token, last_modified = NOW()
            WHERE id = :id';
        $stmt = $this->databox->get_connection()->prepare($sql);
        $stmt->execute(array(':token' => $this->token, ':id'    => $this->get_id()));
        $stmt->closeCursor();

        $this->delete_data_from_cache();

        return $this;
    }

    /**
     *
     * @param  string                  $is_activated
     * @return media_Permalink_Adapter
     */
    public function set_is_activated($is_activated)
    {
        $this->is_activated = ! ! $is_activated;

        $sql = 'UPDATE permalinks SET activated = :activated, last_modified = NOW()
            WHERE id = :id';
        $stmt = $this->databox->get_connection()->prepare($sql);

        $params = array(
            ':activated' => $this->is_activated,
            ':id'        => $this->get_id()
        );

        $stmt->execute($params);
        $stmt->closeCursor();

        $this->delete_data_from_cache();

        return $this;
    }

    /**
     *
     * @param  string                  $label
     * @return media_Permalink_Adapter
     */
    public function set_label($label)
    {
        $label = trim($label) ? trim($label) : 'untitled';
        while (strpos($label, '  ') !== false)
            $label = str_replace('  ', ' ', $label);

        $this->label = $this->app['unicode']->remove_nonazAZ09(
            str_replace(' ', '-', $label)
        );

        $sql = 'UPDATE permalinks SET label = :label, last_modified = NOW()
            WHERE id = :id';
        $stmt = $this->databox->get_connection()->prepare($sql);
        $stmt->execute(array(':label' => $this->label, ':id'    => $this->get_id()));
        $stmt->closeCursor();

        $this->delete_data_from_cache();

        return $this;
    }

    /**
     *
     * @param  Application             $app
     * @param  databox                 $databox
     * @param  media_subdef            $media_subdef
     * @return media_Permalink_Adapter
     */
    public static function getPermalink(Application $app, databox $databox, media_subdef $media_subdef)
    {
        try {
            return new self($app, $databox, $media_subdef);
        } catch (\Exception $e) {

        }

        return self::create($app, $databox, $media_subdef);
    }

    /**
     *
     * @param  Application             $app
     * @param  databox                 $databox
     * @param  media_subdef            $media_subdef
     * @return media_Permalink_Adapter
     */
    public static function create(Application $app, databox $databox, media_subdef $media_subdef)
    {
        $sql = 'INSERT INTO permalinks
            (id, subdef_id, token, activated, created_on, last_modified, label)
            VALUES (null, :subdef_id, :token, :activated, NOW(), NOW(), "")';

        $params = array(
            ':subdef_id' => $media_subdef->get_subdef_id()
            , ':token'     => random::generatePassword(8, random::LETTERS_AND_NUMBERS)
            , ':activated' => '1'
        );

        $error = null;
        $stmt = $databox->get_connection()->prepare($sql);
        try {
            $stmt->execute($params);
        } catch (\PDOException $e) {
            $error = $e;
        }
        $stmt->closeCursor();

        if ($error) {
            throw new RuntimeException('Permalink already exists', $e->getCode(), $e);
        }

        $permalink = self::getPermalink($app, $databox, $media_subdef);
        $permalink->set_label(strip_tags($media_subdef->get_record()->get_title(false, null, true)));

        return $permalink;
    }

    /**
     *
     * @param  Application    $app
     * @param  databox        $databox
     * @param  string         $token
     * @param  int            $record_id
     * @param  string         $name
     * @return record_adapter
     */
    public static function challenge_token(Application $app, databox $databox, $token, $record_id, $name)
    {
        $sql = 'SELECT p.id
            FROM permalinks p, subdef s
            WHERE s.record_id = :record_id
              AND s.name = :name
              AND s.subdef_id = p.subdef_id
              AND activated = "1"
              AND token = :token';

        $params = array(
            ':record_id' => $record_id
            , ':token'     => $token
            , ':name'      => $name
        );

        $stmt = $databox->get_connection()->prepare($sql);
        $stmt->execute($params);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmt->closeCursor();
        unset($stmt);

        if ($row) {
            return new record_adapter($app, $databox->get_sbas_id(), $record_id);
        }

        return null;
    }

    public function get_cache_key($option = null)
    {
        return 'permalink_' . $this->media_subdef->get_subdef_id() . ($option ? '_' . $option : '');
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
