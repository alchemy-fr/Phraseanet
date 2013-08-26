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

use Alchemy\Phrasea\Exception\SessionNotFound;
use Alchemy\Geonames\Exception\ExceptionInterface as GeonamesExceptionInterface;
use Entities\FtpCredential;

/**
 *
 * @package     User
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class User_Adapter implements User_Interface, cache_cacheableInterface
{
    /**
     *
     * @var ACL
     */
    protected $ACL;

    /**
     *
     * @var Array
     */
    public static $locales = array(
        'ar_SA' => 'العربية'
        , 'de_DE' => 'Deutsch'
        , 'nl_NL' => 'Dutch'
        , 'en_GB' => 'English'
        , 'es_ES' => 'Español'
        , 'fr_FR' => 'Français'
    );

    /**
     *
     * @var array
     */
    protected static $_instance = array();

    /**
     *
     * @var array
     */
    protected $_prefs = array();

    /**
     *
     * @var array
     */
    protected static $_users = array();

    /**
     *
     * @var array
     */
    protected $_updated_prefs = array();

    /**
     *
     * @var array
     */
    protected static $def_values = array(
        'view'                    => 'thumbs',
        'images_per_page'         => 20,
        'images_size'             => 120,
        'editing_images_size'     => 134,
        'editing_top_box'         => '180px',
        'editing_right_box'       => '400px',
        'editing_left_box'        => '710px',
        'basket_sort_field'       => 'name',
        'basket_sort_order'       => 'ASC',
        'warning_on_delete_story' => 'true',
        'client_basket_status'    => '1',
        'css'                     => '000000',
        'start_page_query'        => 'last',
        'start_page'              => 'QUERY',
        'rollover_thumbnail'      => 'caption',
        'technical_display'       => '1',
        'doctype_display'         => '1',
        'bask_val_order'          => 'nat',
        'basket_caption_display'  => '0',
        'basket_status_display'   => '0',
        'basket_title_display'    => '0'
    );

    /**
     *
     * @var array
     */
    protected static $available_values = array(
        'view' => array('thumbs', 'list'),
        'basket_sort_field' => array('name', 'date'),
        'basket_sort_order' => array('ASC', 'DESC'),
        'start_page' => array('PUBLI', 'QUERY', 'LAST_QUERY', 'HELP'),
        'technical_display' => array('0', '1', 'group'),
        'rollover_thumbnail' => array('caption', 'preview'),
        'bask_val_order' => array('nat', 'asc', 'desc')
    );

    /**
     *
     * @var Application
     */
    protected $app;

    /**
     *
     * @var int
     */
    protected $id;

    /**
     *
     * @var string
     */
    protected $email;

    /**
     *
     * @var string
     */
    protected $login;
    /**
     *
     * @var string
     */
    protected $locale;

    /**
     *
     * @var string
     */
    protected $firstname;

    /**
     *
     * @var string
     */
    protected $lastname;

    /**
     *
     * @var string
     */
    protected $address;

    /**
     *
     * @var string
     */
    protected $city;

    /**
     *
     * @var int
     */
    protected $geonameid;

    /**
     *
     * @var string
     */
    protected $zip;

    /**
     *
     * @var int
     */
    protected $gender;

    /**
     *
     * @var string
     */
    protected $tel;

    /**
     *
     * @var int
     */
    protected $lastModel;

    /**
     *
     * @var DateTime
     */
    protected $creationdate;

    /**
     *
     * @var DateTime
     */
    protected $modificationdate;

    /**
     *
     * @var string
     */
    protected $fax;

    /**
     *
     * @var string
     */
    protected $job;

    /**
     *
     * @var string
     */
    protected $position;

    /**
     *
     * @var string
     */
    protected $company;

    /**
     *
     * @var boolean
     */
    protected $ldap_created;

    /**
     *
     * @var boolean
     */
    protected $is_guest;

    /**
     *
     * @var boolean
     */
    protected $mail_locked;

    /**
     *
     * @var FtpCredential
     */
    protected $ftpCredential;
    /**
     *
     * @var string
     */
    protected $mail_notifications;

    /**
     *
     * @var string
     */
    protected $country;

    /**
     *
     * @var boolean
     */
    protected $is_template;

    /**
     *
     * @var User_Adapter
     */
    protected $template_owner;

    protected $password;

    /**
     *
     * @param Integer     $id
     * @param Application $app
     *
     * @return User_Adapter
     */
    public function __construct($id, Application $app)
    {

        $this->app = $app;
        $this->load($id);

        return $this;
    }

    public static function unsetInstances()
    {
        foreach (self::$_instance as $id => $user) {
            self::unsetInstance($id);
        }
    }

    public static function unsetInstance($id)
    {
        if (isset(self::$_instance[$id])) {
            self::$_instance[$id] = null;
            unset(self::$_instance[$id]);
        }
    }

    /**
     *
     * @param  type         $id
     * @param  Application  $app
     * @return User_Adapter
     */
    public static function getInstance($id, Application $app)
    {
        if (is_int((int) $id) && (int) $id > 0) {
            $id = (int) $id;
        } else
            throw new Exception('Invalid usr_id');

        if (!isset(self::$_instance[$id])) {
            try {
                self::$_instance[$id] = $app['phraseanet.appbox']->get_data_from_cache('_user_' . $id);
                self::$_instance[$id]->set_app($app);
            } catch (Exception $e) {
                self::$_instance[$id] = new self($id, $app);
                $app['phraseanet.appbox']->set_data_to_cache(self::$_instance[$id], '_user_' . $id);
            }
        }

        return array_key_exists($id, self::$_instance) ? self::$_instance[$id] : false;
    }

    /**
     * Return Access Control List object for the user
     *
     * @return ACL
     */
    public function ACL()
    {
        return $this->get_ACL();
    }

    /**
     *
     * @param Application $app
     */
    protected function set_app(Application $app)
    {
        $this->app = $app;
    }

    /**
     *
     * @param  type         $pasword
     * @return User_Adapter
     */
    public function set_password($pasword)
    {
        $sql = 'UPDATE usr SET usr_password = :password, salted_password = "1"
            WHERE usr_id = :usr_id';

        $password = $this->app['auth.password-encoder']->encodePassword($pasword, $this->get_nonce());

        $stmt = $this->app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute(array(':password' => $password, ':usr_id'   => $this->get_id()));
        $stmt->closeCursor();

        $this->password = $password;

        return $this;
    }

    /**
     *
     * @param  string       $email
     * @return User_Adapter
     */
    public function set_email($email)
    {
        if (trim($email) == '') {
            $email = null;
        }

        $test_user = User_Adapter::get_usr_id_from_email($this->app, $email);

        if ($test_user && $test_user != $this->get_id()) {
            throw new Exception_InvalidArgument(sprintf(_('A user already exists with email addres %s'), $email));
        }

        $sql = 'UPDATE usr SET usr_mail = :new_email WHERE usr_id = :usr_id';
        $stmt = $this->app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute(array(':new_email' => $email, ':usr_id'    => $this->get_id()));
        $stmt->closeCursor();
        $this->email = $email;
        $this->delete_data_from_cache();

        return $this;
    }

    /**
     * Load if needed of the ACL for the current user
     *
     * @return ACL
     */
    protected function get_ACL()
    {
        if (!$this->ACL instanceof ACL) {
            $this->ACL = new ACL($this, $this->app);
        }

        return $this->ACL;
    }

    /**
     *
     * @return string
     */
    public function get_country()
    {
        if ($this->geonameid) {
            try {
                $country = $this->app['geonames.connector']
                    ->geoname($this->geonameid)
                    ->get('country');

                if (isset($country['name'])) {
                    return $country['name'];
                }
            } catch (GeonamesExceptionInterface $e) {

            }
        }

        return '';
    }

    /**
     *
     * @param Application $app
     * @param string      $login
     *
     * @return integer
     */
    public static function get_usr_id_from_login(Application $app, $login)
    {
        $conn = connection::getPDOConnection($app);
        $sql = 'SELECT usr_id FROM usr WHERE usr_login = :login';
        $stmt = $conn->prepare($sql);
        $stmt->execute(array(':login' => trim($login)));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $usr_id = $row ? (int) $row['usr_id'] : false;

        return $usr_id;
    }

    /**
     *
     * @param  bollean      $boolean
     * @return User_Adapter
     */
    public function set_mail_notifications($boolean)
    {
        $value = $boolean ? '1' : '0';
        $sql = 'UPDATE usr SET mail_notifications = :mail_notifications WHERE usr_id = :usr_id';
        $stmt = $this->app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute(array(':mail_notifications'     => $value, ':usr_id'                 => $this->get_id()));
        $stmt->closeCursor();
        $this->mail_notifications = !!$boolean;
        $this->delete_data_from_cache();

        return $this;
    }

    /**
     *
     * @param  boolean      $boolean
     * @return User_Adapter
     */
    public function set_ldap_created($boolean)
    {
        $value = $boolean ? '1' : '0';
        $sql = 'UPDATE usr SET ldap_created = :ldap_created WHERE usr_id = :usr_id';
        $stmt = $this->app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute(array(':ldap_created'     => $value, ':usr_id'           => $this->get_id()));
        $stmt->closeCursor();
        $this->ldap_created = $boolean;

        return $this;
    }

    public function set_firstname($firstname)
    {
        $sql = 'UPDATE usr SET usr_prenom = :usr_prenom WHERE usr_id = :usr_id';
        $stmt = $this->app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute(array(':usr_prenom'    => $firstname, ':usr_id'        => $this->get_id()));
        $stmt->closeCursor();
        $this->firstname = $firstname;
        $this->delete_data_from_cache();

        return $this;
    }

    public function set_lastname($lastname)
    {
        $sql = 'UPDATE usr SET usr_nom = :usr_nom WHERE usr_id = :usr_id';
        $stmt = $this->app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute(array(':usr_nom'      => $lastname, ':usr_id'       => $this->get_id()));
        $stmt->closeCursor();
        $this->lastname = $lastname;
        $this->delete_data_from_cache();

        return $this;
    }

    public function set_address($address)
    {
        $sql = 'UPDATE usr SET adresse = :adresse WHERE usr_id = :usr_id';
        $stmt = $this->app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute(array(':adresse'     => $address, ':usr_id'      => $this->get_id()));
        $stmt->closeCursor();
        $this->address = $address;
        $this->delete_data_from_cache();

        return $this;
    }

    public function set_city($city)
    {
        $sql = 'UPDATE usr SET ville = :city WHERE usr_id = :usr_id';
        $stmt = $this->app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute(array(':city'     => $city, ':usr_id'   => $this->get_id()));
        $stmt->closeCursor();
        $this->city = $city;
        $this->delete_data_from_cache();

        return $this;
    }

    public function set_geonameid($geonameid)
    {
        $country_code = null;

        try {
            $country = $this->app['geonames.connector']
                ->geoname($this->geonameid)
                ->get('country');

            if (isset($country['code'])) {
                $country_code = $country['code'];
            }
        } catch (GeonamesExceptionInterface $e) {

        }

        $sql = 'UPDATE usr SET geonameid = :geonameid, pays=:country_code WHERE usr_id = :usr_id';

        $datas = array(
            ':geonameid'    => $geonameid,
            ':usr_id'       => $this->get_id(),
            ':country_code' => $country_code
        );

        $stmt = $this->app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute($datas);
        $stmt->closeCursor();
        $this->geonameid = $geonameid;
        $this->country = $country_code;
        $this->delete_data_from_cache();

        return $this;
    }

    public function set_zip($zip)
    {
        $sql = 'UPDATE usr SET cpostal = :cpostal WHERE usr_id = :usr_id';
        $stmt = $this->app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute(array(':cpostal' => $zip, ':usr_id'  => $this->get_id()));
        $stmt->closeCursor();
        $this->zip = $zip;
        $this->delete_data_from_cache();

        return $this;
    }

    public function set_gender($gender)
    {
        $sql = 'UPDATE usr SET usr_sexe = :usr_sexe WHERE usr_id = :usr_id';
        $stmt = $this->app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute(array(':usr_sexe'   => $gender, ':usr_id'     => $this->get_id()));
        $stmt->closeCursor();
        $this->gender = $gender;
        $this->delete_data_from_cache();

        return $this;
    }

    public function set_tel($tel)
    {
        $sql = 'UPDATE usr SET tel = :tel WHERE usr_id = :usr_id';
        $stmt = $this->app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute(array(':tel'     => $tel, ':usr_id'  => $this->get_id()));
        $stmt->closeCursor();
        $this->tel = $tel;
        $this->delete_data_from_cache();

        return $this;
    }

    public function set_fax($fax)
    {
        $sql = 'UPDATE usr SET fax = :fax WHERE usr_id = :usr_id';
        $stmt = $this->app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute(array(':fax'     => $fax, ':usr_id'  => $this->get_id()));
        $stmt->closeCursor();
        $this->fax = $fax;
        $this->delete_data_from_cache();

        return $this;
    }

    public function set_job($job)
    {
        $sql = 'UPDATE usr SET fonction = :fonction WHERE usr_id = :usr_id';
        $stmt = $this->app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute(array(':fonction' => $job, ':usr_id'   => $this->get_id()));
        $stmt->closeCursor();
        $this->job = $job;
        $this->delete_data_from_cache();

        return $this;
    }

    public function set_position($position)
    {
        $sql = 'UPDATE usr SET activite = :activite WHERE usr_id = :usr_id';
        $stmt = $this->app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute(array(':activite'     => $position, ':usr_id'       => $this->get_id()));
        $stmt->closeCursor();
        $this->position = $position;
        $this->delete_data_from_cache();

        return $this;
    }

    public function set_company($company)
    {
        $sql = 'UPDATE usr SET societe = :company WHERE usr_id = :usr_id';
        $stmt = $this->app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute(array(':company'     => $company, ':usr_id'      => $this->get_id()));
        $stmt->closeCursor();
        $this->company = $company;
        $this->delete_data_from_cache();

        return $this;
    }

    public function set_template(User_Adapter $owner)
    {
        $this->is_template = true;
        $this->template_owner = $owner;

        if ($owner->get_id() == $this->get_id())
            throw new Exception_InvalidArgument ();

        $sql = 'UPDATE usr SET model_of = :owner_id WHERE usr_id = :usr_id';
        $stmt = $this->app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute(array(':owner_id' => $owner->get_id(), ':usr_id'   => $this->get_id()));
        $stmt->closeCursor();

        $this
            ->set_city('')
            ->set_company('')
            ->set_email(null)
            ->set_fax('')
            ->set_firstname('')
            ->set_gender('')
            ->set_geonameid('')
            ->set_job('')
            ->set_lastname('')
            ->set_mail_locked(false)
            ->set_mail_notifications(true)
            ->set_position('')
            ->set_zip('')
            ->set_tel('');

        $this->ftpCredential = new FtpCredential();
        $this->ftpCredential->setUsrId($this->get_id());
        $this->app['EM']->persist($this->ftpCredential);
        $this->app['EM']->flush();

        $this->delete_data_from_cache();

        return $this;
    }

    /**
     * @return FtpCredential
     */
    public function getFtpCredential()
    {
        if (null === $this->ftpCredential) {
            $this->ftpCredential = $this->app['EM']->getRepository('Entities\FtpCredential')->findOneBy(array(
                'usrId' => $this->get_id()
            ));

            if (null === $this->ftpCredential) {
                $this->ftpCredential = new FtpCredential();
                $this->ftpCredential->setUsrId($this->get_id());
            }
        }

        return $this->ftpCredential;
    }

    public function is_template()
    {
        return $this->is_template;
    }

    public function is_special()
    {
        return in_array($this->login, array('invite', 'autoregister'));
    }

    public function get_template_owner()
    {
        return $this->template_owner;
    }

    public static function get_usr_id_from_email(Application $app, $email)
    {
        if (is_null($email)) {
            return false;
        }

        $conn = connection::getPDOConnection($app);
        $sql = 'SELECT usr_id FROM usr
            WHERE usr_mail = :email
              AND usr_login NOT LIKE "(#deleted_%"
              AND invite="0" AND usr_login != "autoregister"';
        $stmt = $conn->prepare($sql);
        $stmt->execute(array(':email' => trim($email)));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $usr_id = $row ? $row['usr_id'] : false;

        return $usr_id;
    }

    /**
     * @todo close all open session
     * @return type
     */
    public function delete()
    {
        $repo = $this->app['EM']->getRepository('Entities\UsrAuthProvider');

        foreach ($repo->findByUser($this) as $provider) {
            $this->app['EM']->remove($provider);
        }

        $this->app['EM']->flush();

        $sql = 'UPDATE usr SET usr_login = :usr_login , usr_mail = null
            WHERE usr_id = :usr_id';
        $stmt = $this->app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute(array(':usr_login' => '(#deleted_' . $this->get_login() . '_' . $this->get_id(), ':usr_id'    => $this->get_id()));
        $stmt->closeCursor();

        $sql = 'DELETE FROM basusr WHERE usr_id = :usr_id';
        $stmt = $this->app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute(array(':usr_id' => $this->get_id()));
        $stmt->closeCursor();

        $sql = 'DELETE FROM sbasusr WHERE usr_id = :usr_id';
        $stmt = $this->app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute(array(':usr_id' => $this->get_id()));
        $stmt->closeCursor();

        $sql = 'DELETE FROM dsel WHERE usr_id = :usr_id';
        $stmt = $this->app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute(array(':usr_id' => $this->get_id()));
        $stmt->closeCursor();

        $sql = 'DELETE FROM edit_presets WHERE usr_id = :usr_id';
        $stmt = $this->app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute(array(':usr_id' => $this->get_id()));
        $stmt->closeCursor();

        $sql = 'DELETE FROM ftp_export WHERE usr_id = :usr_id';
        $stmt = $this->app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute(array(':usr_id' => $this->get_id()));
        $stmt->closeCursor();

        $sql = 'DELETE FROM `order` WHERE usr_id = :usr_id';
        $stmt = $this->app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute(array(':usr_id' => $this->get_id()));
        $stmt->closeCursor();

        $sql = 'DELETE FROM sselnew WHERE usr_id = :usr_id';
        $stmt = $this->app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute(array(':usr_id' => $this->get_id()));
        $stmt->closeCursor();

        $sql = 'DELETE FROM tokens WHERE usr_id = :usr_id';
        $stmt = $this->app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute(array(':usr_id' => $this->get_id()));
        $stmt->closeCursor();

        $sql = 'DELETE FROM usr_settings WHERE usr_id = :usr_id';
        $stmt = $this->app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute(array(':usr_id' => $this->get_id()));
        $stmt->closeCursor();

        unset(self::$_instance[$this->get_id()]);

        return;
    }
    public function get_mail_notifications()
    {
        return $this->mail_notifications;
    }

    /**
     *
     * @param  <type> $id
     * @return user
     */
    public function load($id)
    {
        $sql = 'SELECT usr_id, ldap_created, create_db, usr_login, usr_password, usr_nom, activite,
            usr_prenom, usr_sexe as gender, usr_mail, adresse, usr_creationdate, usr_modificationdate,
            ville, cpostal, tel, fax, fonction, societe, geonameid, lastModel, invite,
            mail_notifications, mail_locked, model_of, locale
          FROM usr WHERE usr_id= :id ';

        $stmt = $this->app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute(array(':id' => $id));

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if (!$row) {
            throw new \Exception('User unknown');
        }

        $this->id = (int) $row['usr_id'];
        $this->email = $row['usr_mail'];
        $this->login = $row['usr_login'];
        $this->password = $row['usr_password'];

        $this->ldap_created = $row['ldap_created'];

        $this->mail_notifications = $row['mail_notifications'];

        $this->mail_locked = !!$row['mail_locked'];

        $this->firstname = $row['usr_prenom'];
        $this->lastname = $row['usr_nom'];
        $this->address = $row['adresse'];
        $this->city = $row['ville'];
        $this->geonameid = $row['geonameid'];
        $this->zip = $row['cpostal'];
        $this->gender = $row['gender'];
        $this->tel = $row['tel'];
        $this->locale = $row['locale'];
        $this->fax = $row['fax'];
        $this->job = $row['fonction'];
        $this->position = $row['activite'];
        $this->company = $row['societe'];
        $this->creationdate = new DateTime($row['usr_creationdate']);
        $this->modificationdate = new DateTime($row['usr_modificationdate']);
        $this->applied_template = $row['lastModel'];

        $this->country = $this->get_country();

        $this->is_guest = ($row['invite'] == '1');

        if ($row['model_of'] > 0) {
            $this->is_template = true;
            $this->template_owner = self::getInstance($row['model_of'], $this->app);
        }

        return $this;
    }

    public function set_last_template(User_Interface $template)
    {
        $sql = 'UPDATE usr  SET lastModel = :template_id WHERE usr_id = :usr_id';

        $params = array(
            ':usr_id'      => $this->get_id()
            , ':template_id' => $template->get_login()
        );

        $stmt = $this->app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute($params);
        $stmt->closeCursor();
        $this->delete_data_from_cache();

        return $this;
    }

    public function set_mail_locked($boolean)
    {
        $sql = 'UPDATE usr  SET mail_locked = :mail_locked WHERE usr_id = :usr_id';
        $stmt = $this->app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute(array(':usr_id'          => $this->get_id(), ':mail_locked'     => ($boolean ? '1' : '0')));
        $stmt->closeCursor();
        $this->mail_locked = !!$boolean;

        return $this;
    }

    public function get_mail_locked()
    {
        return $this->mail_locked;
    }

    /**
     *
     * @return int
     */
    public function get_id()
    {
        return $this->id;
    }

    public function get_ldap_created()
    {
        return $this->ldap_created;
    }

    public function is_guest()
    {
        return $this->is_guest;
    }

    public function get_login()
    {
        return $this->login;
    }

    public function get_password()
    {
        return $this->password;
    }

    public function get_email()
    {
        return $this->email;
    }

    public function get_firstname()
    {
        return $this->firstname;
    }

    public function get_lastname()
    {
        return $this->lastname;
    }

    public function get_company()
    {
        return $this->company;
    }

    public function get_tel()
    {
        return $this->tel;
    }

    public function get_fax()
    {
        return $this->fax;
    }

    public function get_job()
    {
        return $this->job;
    }

    public function get_position()
    {
        return $this->position;
    }

    public function get_zipcode()
    {
        return $this->zip;
    }

    public function get_city()
    {
        return $this->city;
    }

    public function get_address()
    {
        return $this->address;
    }

    public function get_gender()
    {
        return $this->gender;
    }

    public function get_geonameid()
    {
        return $this->geonameid;
    }

    public function get_last_connection()
    {
        $sql = 'SELECT last_conn FROM usr WHERE usr_id = :usr_id';

        $stmt = $this->app['phraseanet.appbox']->get_connection()->prepare($sql);

        $stmt->execute(array(':usr_id' => $this->get_id()));

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmt->closeCursor();

        $date_obj = new DateTime($row['last_conn']);

        return $date_obj;
    }

    public function get_applied_template()
    {
        return $this->applied_template;
    }

    public function get_creation_date()
    {
        return $this->creationdate;
    }

    public function get_modification_date()
    {
        return $this->modificationdate;
    }

    protected function load_preferences()
    {
        if ($this->_prefs) {
            return $this;
        }

        $sql = 'SELECT prop, value FROM usr_settings WHERE usr_id= :id';
        $stmt = $this->app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute(array(':id' => $this->id));
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        foreach ($rs as $row) {
            $this->_prefs[$row['prop']] = $row['value'];
        }

        foreach (self::$def_values as $k => $v) {
            if (!isset($this->_prefs[$k])) {
                if ($k == 'start_page_query' && $this->app['phraseanet.registry']->get('GV_defaultQuery')) {
                    $v = $this->app['phraseanet.registry']->get('GV_defaultQuery');
                }

                $this->_prefs[$k] = $v;
                $this->update_pref($k, $v);
            }
        }

        return $this;
    }

    protected function load_notifications_preferences(Application $app)
    {
        $notifications = $app['events-manager']->list_notifications_available($this->id);

        foreach ($notifications as $notification_group => $nots) {
            foreach ($nots as $notification) {
                if (!isset($this->_prefs['notification_' . $notification['id']])) {
                    $this->_prefs['notification_' . $notification['id']] = '1';

                    $this->update_pref('notification_' . $notification['id'], '1');
                }
            }
        }
        $this->notification_preferences_loaded = true;
    }
    protected $notifications_preferences_loaded = false;

    public function get_notifications_preference(Application $app, $notification_id)
    {
        if (!$this->notifications_preferences_loaded)
            $this->load_notifications_preferences($app);

        return $this->_prefs['notification_' . $notification_id];
    }

    public function set_notification_preference(Application $app, $notification_id, $value)
    {
        if (!$this->notifications_preferences_loaded)
            $this->load_notifications_preferences($app);

        return $this->_prefs['notification_' . $notification_id] = $value ? '1' : '0';
    }

    public function get_display_name()
    {
        if ($this->is_template())
            $display_name = sprintf(_('modele %s'), $this->get_login());
        elseif (trim($this->lastname) !== '' || trim($this->firstname) !== '')
            $display_name = $this->firstname . ' ' . $this->lastname;
        elseif (trim($this->email) !== '')
            $display_name = $this->email;
        else
            $display_name = _('phraseanet::utilisateur inconnu');

        return $display_name;
    }

    protected function update_pref($prop, $value)
    {
        try {
            $sql = 'REPLACE INTO usr_settings (usr_id, prop, value)
        VALUES (:usr_id, :prop, :value)';

            $stmt = $this->app['phraseanet.appbox']->get_connection()->prepare($sql);
            $stmt->execute(array(
                ':usr_id' => $this->id,
                ':prop'   => $prop,
                ':value'  => $value
            ));
            $this->delete_data_from_cache();
        } catch (Exception $e) {

        }

        return $this;
    }

    public function get_cache_key($option = null)
    {
        return '_user_' . $this->get_id() . ($option ? '_' . $option : '');
    }

    public function delete_data_from_cache($option = null)
    {
        $this->app['phraseanet.appbox']->delete_data_from_cache($this->get_cache_key($option));

        return $this;
    }

    public function get_data_from_cache($option = null)
    {
        $this->app['phraseanet.appbox']->get_data_from_cache($this->get_cache_key($option));

        return $this;
    }

    public function set_data_to_cache($value, $option = null, $duration = 0)
    {
        $this->app['phraseanet.appbox']->set_data_to_cache($value, $this->get_cache_key($option), $duration);

        return $this;
    }

    public static function get_wrong_email_users(Application $app)
    {

        $sql = 'SELECT usr_mail, usr_id FROM usr WHERE usr_mail IS NOT NULL';

        $stmt = $app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute();

        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt->closeCursor();

        $users = array();

        foreach ($rs as $row) {
            if (!isset($users[$row['usr_mail']])) {
                $users[$row['usr_mail']] = array();
            }

            $users[$row['usr_mail']][] = $row['usr_id'];
        }

        $bad_users = array();

        foreach ($users as $email => $usrs) {
            if (count($usrs) > 1) {
                $bad_users[$email] = array();
                foreach ($usrs as $usr_id) {
                    $user = User_Adapter::getInstance($usr_id, $app);
                    $bad_users[$email][$user->get_id()] = $user;
                }
            }
        }

        unset($users);

        return $bad_users;
    }

    public function setPrefs($prop, $value)
    {
        $this->load_preferences();
        if (isset($this->_prefs[$prop]) && $this->_prefs[$prop] === $value) {
            return $this->_prefs[$prop];
        }

        $ok = true;

        if (isset(self::$available_values[$prop])) {
            $ok = false;
            if (in_array($value, self::$available_values[$prop]))
                $ok = true;
        }

        if ($ok) {
            $this->_prefs[$prop] = $value;
            $this->update_pref($prop, $value);
        }

        return $this->_prefs[$prop];
    }

    public function getPrefs($prop)
    {
        $this->load_preferences();
        if (!isset($this->_prefs[$prop])) {
            $this->_prefs[$prop] = null;
            $this->update_pref($prop, null);
        }

        return $this->_prefs[$prop];
    }

    public static function get_sys_admins(Application $app)
    {
        $sql = 'SELECT usr_id, usr_login FROM usr
                WHERE create_db="1"
                    AND model_of="0"
                    AND usr_login NOT LIKE "(#deleted%"';
        $conn = connection::getPDOConnection($app);
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $users = array();

        foreach ($rs as $row)
            $users[$row['usr_id']] = $row['usr_login'];

        return $users;
    }

    public static function set_sys_admins(Application $app, $admins)
    {
        try {
            $sql = "UPDATE usr SET create_db='0' WHERE create_db='1' AND usr_id != :usr_id";
            $stmt = $app['phraseanet.appbox']->get_connection()->prepare($sql);
            $stmt->execute(array(':usr_id' => $app['authentication']->getUser()->get_id()));
            $stmt->closeCursor();

            $sql = "UPDATE usr SET create_db='1' WHERE usr_id IN (" . implode(',', $admins) . ")";
            $stmt = $app['phraseanet.appbox']->get_connection()->prepare($sql);
            $stmt->execute();
            $stmt->closeCursor();

            return true;
        } catch (Exception $e) {

        }

        return false;
    }

    public static function reset_sys_admins_rights(Application $app)
    {
        $users = self::get_sys_admins($app);

        foreach ($app['phraseanet.appbox']->get_databoxes() as $databox) {
            foreach (array_keys($users) as $usr_id) {
                $user = User_Adapter::getInstance($usr_id, $app);
                $user->ACL()->give_access_to_sbas(array($databox->get_sbas_id()));

                $rights = array(
                    'bas_manage'        => '1'
                    , 'bas_modify_struct' => '1'
                    , 'bas_modif_th'      => '1'
                    , 'bas_chupub'        => '1'
                );

                $user->ACL()->update_rights_to_sbas($databox->get_sbas_id(), $rights);

                foreach ($databox->get_collections() as $collection) {
                    $user->ACL()->give_access_to_base(array($collection->get_base_id()));

                    $rights = array(
                        'canputinalbum'     => '1'
                        , 'candwnldhd'        => '1'
                        , 'candwnldsubdef'    => '1'
                        , 'nowatermark'       => '1'
                        , 'candwnldpreview'   => '1'
                        , 'cancmd'            => '1'
                        , 'canadmin'          => '1'
                        , 'canreport'         => '1'
                        , 'canpush'           => '1'
                        , 'creationdate'      => '1'
                        , 'canaddrecord'      => '1'
                        , 'canmodifrecord'    => '1'
                        , 'candeleterecord'   => '1'
                        , 'chgstatus'         => '1'
                        , 'imgtools'          => '1'
                        , 'manage'            => '1'
                        , 'modify_struct'     => '1'
                        , 'bas_modify_struct' => '1'
                    );

                    $user->ACL()->update_rights_to_base($collection->get_base_id(), $rights);
                    $user->ACL()->set_limits($collection->get_base_id(), false);
                }
            }
        }

        return;
    }

    public function get_locale()
    {
        return $this->locale ?: $this->app['phraseanet.registry']->get('GV_default_lng', 'en_GB');
    }

    public function set_locale($locale)
    {
        if (!array_key_exists($locale, $this->app['locales.available'])) {
            throw new \InvalidArgumentException(sprintf('Locale %s is not recognized', $locale));
        }

        $sql = 'UPDATE usr SET locale = :locale WHERE usr_id = :usr_id';
        $stmt = $this->app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute(array(':locale'     => $locale, ':usr_id'  => $this->get_id()));
        $stmt->closeCursor();
        $this->delete_data_from_cache();

        $this->locale = $locale;

        return $this->locale;
    }

    public static function create(Application $app, $login, $password, $email, $admin, $invite = false)
    {
        $conn = $app['phraseanet.appbox']->get_connection();

        if (trim($login) == '') {
            throw new \InvalidArgumentException('Invalid username');
        }

        if (trim($password) == '') {
            throw new \InvalidArgumentException('Invalid password');
        }

        $login = $invite ? 'invite' . random::generatePassword(16) : $login;

        $nonce = random::generatePassword(16);

        $sql = 'INSERT INTO usr
                (usr_id, usr_login, usr_password, usr_creationdate, usr_mail, create_db, nonce, salted_password, invite)
                VALUES (null, :login, :password, NOW(), :email, :admin, :nonce, 1, :invite)';

        $stmt = $conn->prepare($sql);
        $stmt->execute(array(
            ':login'    => $login,
            ':nonce'    => $nonce,
            ':password' => $app['auth.password-encoder']->encodePassword($password, $nonce),
            ':email'    => ($email ? $email : null),
            ':admin'    => ($admin ? '1' : '0'),
            ':invite'   => ($invite ? '1' : '0')
        ));
        $stmt->closeCursor();

        $usr_id = $conn->lastInsertId();

        $ftpCredential = new FtpCredential();
        $ftpCredential->setUsrId($usr_id);
        $app['EM']->persist($ftpCredential);
        $app['EM']->flush();

        if ($invite) {
            $sql = 'UPDATE usr SET usr_login = "invite' . $usr_id . '" WHERE usr_id="' . $usr_id . '"';
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $stmt->closeCursor();
        }

        return self::getInstance($usr_id, $app);
    }

    protected $nonce;

    public function get_nonce()
    {
        if ($this->nonce) {
            return $this->nonce;
        }

        $nonce = false;

        $sql = 'SELECT nonce FROM usr WHERE usr_id = :usr_id ';
        $stmt = $this->app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute(array(':usr_id' => $this->get_id()));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        unset($stmt);

        $nonce = $row['nonce'];

        $this->nonce = $nonce;

        return $this->nonce;
    }

    public function __sleep()
    {
        $vars = array();
        foreach ($this as $key => $value) {
            if (in_array($key, array('ACL', 'app')))
                continue;
            $vars[] = $key;
        }

        return $vars;
    }
}
