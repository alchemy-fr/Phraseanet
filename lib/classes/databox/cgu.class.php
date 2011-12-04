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
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class databox_cgu
{
  public function  __construct(databox $databox, $locale)
  {
    return $this;
  }

  public static function askAgreement()
  {
    $terms = self::getUnvalidated();

    $out = '';

    foreach ($terms as $name => $term)
    {
      if (trim($term['terms']) == '')
        continue;

      $out .= '<div style="display:none;" class="cgu-dialog" title="' . str_replace('"', '&quot;', sprintf(_('cgus:: CGUs de la base %s'), $name)) . '">';

      $out .= '<blockquote>' . $term['terms'] . '</blockquote>';
      $out .= '<div>' . _('cgus:: Pour continuer a utiliser lapplication, vous devez accepter les conditions precedentes') . '
                <input id="terms_of_use_' . $term['sbas_id'] . '" type="button" date="' . $term['date'] . '" class="cgus-accept" value="' . _('cgus :: accepter') . '"/>
                <input id="sbas_' . $term['sbas_id'] . '" type="button" class="cgus-cancel" value="' . _('cgus :: refuser') . '"/>
                </div>';
      $out .= '</div>';
    }

    return $out;
  }

  public static function denyCgus($sbas_id)
  {
    $appbox = appbox::get_instance();
    $session = $appbox->get_session();
    if (!$session->is_authenticated())

      return '2';

    $ret = '1';

    try
    {
      $sql = 'DELETE FROM sbasusr WHERE sbas_id = :sbas_id AND usr_id = :usr_id';

      $stmt = $appbox->get_connection()->prepare($sql);
      $stmt->execute(array(':sbas_id' => $sbas_id, ':usr_id' => $session->get_usr_id()));
      $stmt->closeCursor();
    }
    catch (Exception $e)
    {
      $ret = '0';
    }

    try
    {
      $sql = 'DELETE FROM basusr
        WHERE base_id IN (SELECT base_id FROM bas WHERE sbas_id = :sbas_id)
          AND usr_id = :usr_id';

      $stmt = $appbox->get_connection()->prepare($sql);
      $stmt->execute(array(':sbas_id' => $sbas_id, ':usr_id' => $session->get_usr_id()));
      $stmt->closeCursor();
    }
    catch (Exception $e)
    {
      $ret = '0';
    }

    $session->logout();

    return $ret;
  }

  private static function getUnvalidated($home=false)
  {
    $terms = array();
    $appbox = appbox::get_instance();
    $session = $appbox->get_session();

    foreach ($appbox->get_databoxes() as $databox)
    {
      try
      {
        $user = User_Adapter::getInstance($session->get_usr_id(), $appbox);
        $cgus = $databox->get_cgus();

        if (!isset($cgus[Session_Handler::get_locale()]))
          throw new Exception('No CGus for this locale');
        $name = $databox->get_viewname();

        $update = $cgus[Session_Handler::get_locale()]['updated_on'];
        $value = $cgus[Session_Handler::get_locale()]['value'];
        $userValidation = true;

        if (!$home)
        {
          $userValidation = ($user->getPrefs('terms_of_use_' . $databox->get_sbas_id()) !== $update && trim($value) !== '');
        }

        if ($userValidation)
          $terms[$name] = array('sbas_id' => $databox->get_sbas_id(), 'terms' => $value, 'date' => $update);
      }
      catch (Exception $e)
      {

      }
    }

    return $terms;
  }

  public static function getHome()
  {
    $terms = self::getUnvalidated(true);

    $out = '';

    foreach ($terms as $name => $term)
    {
      if (trim($term['terms']) == '')
        continue;

      if ($out != '')
        $out .= '<hr/>';

      $out .= '<div><h1 style="text-align:center;">' . str_replace('"', '&quot;', sprintf(_('cgus:: CGUs de la base %s'), $name)) . '</h1>';

      $out .= '<blockquote>' . $term['terms'] . '</blockquote>';

      $out .= '</div>';
    }

    return $out;
  }

}
