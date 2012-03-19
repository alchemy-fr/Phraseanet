<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 * @package     User
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
interface User_Interface
{
  public function get_id();

  public function is_admin();

  public function __construct($id, appbox &$appbox);

  public function ACL();

  public function set_password($pasword);

  public function set_email($email);

  public function get_protected_rss_url($renew = false);

  public function get_country();

  public function set_defaultftpdatas($datas);

  public function set_mail_notifications($boolean);

  public function set_activeftp($boolean);

  public function set_ftp_address($address);

  public function set_ftp_login($login);

  public function set_ftp_password($password);

  public function set_ftp_passif($boolean);

  public function set_ftp_dir($ftp_dir);

  public function set_ftp_dir_prefix($ftp_dir_prefix);

  public function set_firstname($firstname);

  public function set_lastname($lastname);

  public function set_address($address);

  public function set_city($city);

  public function set_geonameid($geonameid);

  public function set_zip($zip);

  public function set_gender($gender);

  public function set_tel($tel);

  public function set_fax($fax);

  public function set_job($job);

  public function set_position($position);

  public function set_company($company);

  public function delete();

  public function get_defaultftpdatas();

  public function get_mail_notifications();

  public function get_activeftp();

  public function get_ftp_address();

  public function get_ftp_login();

  public function get_ftp_password();

  public function get_ftp_passif();

  public function get_ftp_dir();

  public function get_ftp_dir_prefix();

  public function load($id);

  public function set_last_template(User_Interface $template);

  public function set_mail_locked($boolean);

  public function get_mail_locked();

  public function is_guest();

  public function get_login();

  public function get_email();

  public function get_firstname();

  public function get_lastname();

  public function get_company();

  public function get_tel();

  public function get_fax();

  public function get_job();

  public function get_position();

  public function get_zipcode();

  public function get_city();

  public function get_address();

  public function get_gender();

  public function get_geonameid();

  public function get_applied_template();

  public function get_creation_date();

  public function get_notifications_preference($notification_id);

  public function set_notification_preference($notification_id, $value);

  public function get_display_name();

  public function get_nonce();

  public static function avLanguages();

  public function setPrefs($prop, $value);

  public function getPrefs($prop);

  public static function updateClientInfos($app_id);

  public static function get_sys_admins();

  public static function set_sys_admins($admins);

  public static function reset_sys_admins_rights();

  public static function get_locale($usr_id);

  public static function create(appbox &$appbox, $login, $password, $email, $admin, $invite = false);

  public static function salt_password($password, $nonce);

  public static function getInstance($id, appbox &$appbox);

  public static function saveQuery($query);

  public static function get_usr_id_from_login($login);

  public static function get_usr_id_from_email($email);
}
