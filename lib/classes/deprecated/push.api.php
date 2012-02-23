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
function get_distinct_activite(array $baslist)
{
  $conn = connection::getPDOConnection();
  $sql = 'SELECT DISTINCT usr.activite
          FROM usr
            LEFT JOIN basusr b ON b.usr_id=usr.usr_id
            LEFT JOIN demand on usr.usr_id=demand.usr_id
          WHERE (b.base_id="' . implode('" OR b.base_id="', $baslist) . '")
            AND usr_login not like "(#deleted_%"  AND isnull(demand.base_id)
            AND usr.model_of=0 ORDER BY usr.activite ASC';
  $stmt = $conn->prepare($sql);
  $stmt->execute();
  $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
  $stmt->closeCursor();

  return $rs;
}

function get_distinct_fonction(array $baslist)
{
  $conn = connection::getPDOConnection();
  $sql = 'SELECT DISTINCT usr.fonction' .
          ' FROM usr' .
          ' LEFT JOIN basusr b ON b.usr_id=usr.usr_id' .
          ' left join demand on usr.usr_id=demand.usr_id' .
          ' WHERE ((b.base_id="' . implode('" OR b.base_id="', $baslist) . '"))' .
          ' AND usr_login not like "(#deleted_%"  AND isnull(demand.base_id)' .
          ' AND usr.model_of=0  ORDER BY usr.activite ASC';
  $stmt = $conn->prepare($sql);
  $stmt->execute();
  $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
  $stmt->closeCursor();

  return $rs;
}

function get_distinct_pays($baslist)
{
  $conn = connection::getPDOConnection();
  $sql = 'SELECT DISTINCT usr.pays' .
          ' FROM usr' .
          ' LEFT JOIN basusr b ON b.usr_id=usr.usr_id' .
          ' left join demand on usr.usr_id=demand.usr_id' .
          ' WHERE ((b.base_id="' . implode('" OR b.base_id="', $baslist) . '"))' .
          ' AND usr_login not like "(#deleted_%"  AND isnull(demand.base_id)' .
          ' AND usr.model_of=0 ';
  $stmt = $conn->prepare($sql);
  $stmt->execute();
  $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
  $stmt->closeCursor();

  return $rs;
}

function get_distinct_societe($baslist)
{
  $conn = connection::getPDOConnection();
  $sql = 'SELECT DISTINCT usr.societe' .
          ' FROM usr' .
          ' LEFT JOIN basusr b ON b.usr_id=usr.usr_id' .
          ' left join demand on usr.usr_id=demand.usr_id' .
          ' WHERE ((b.base_id="' . implode('" OR b.base_id="', $baslist) . '"))' .
          ' AND usr_login not like "(#deleted_%"  AND isnull(demand.base_id)' .
          ' AND usr.model_of=0 ORDER BY usr.societe ASC';
  $stmt = $conn->prepare($sql);
  $stmt->execute();
  $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
  $stmt->closeCursor();

  return $rs;
}

function get_distinct_model($baslist)
{
  $conn = connection::getPDOConnection();
  $sql = 'SELECT DISTINCT usr.lastModel' .
          ' FROM usr' .
          ' LEFT JOIN basusr b ON b.usr_id=usr.usr_id' .
          ' left join demand on usr.usr_id=demand.usr_id' .
          ' WHERE ((b.base_id="' . implode('" OR b.base_id="', $baslist) . '"))' .
          ' AND usr_login not like "(#deleted_%"  AND isnull(demand.base_id)' .
          ' AND usr.model_of=0 ORDER BY usr.lastModel ASC';
  $stmt = $conn->prepare($sql);
  $stmt->execute();
  $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
  $stmt->closeCursor();

  return $rs;
}

function newUserCheckMail($usr, $lng, $mail, $usr_id, $out='HTML')
{
  $conn = connection::getPDOConnection();

  $datas = sqlFromFilters($usr, '');

  $sql = $datas['sql'];
  $params = $datas['params'];

  $sql .= ' AND usr.usr_mail = :extra_usr_mail';
  $params[':extra_usr_mail'] = $mail;

  $stmt = $conn->prepare($sql);
  $stmt->execute($params);
  $n = $stmt->rowCount();
  $stmt->closeCursor();

  if ($n > 0)
  {
    return '<div>' . sprintf(_('push:: %d utilisateurs accessible via le formulaire de recherche ont ete trouves. Vous ne pouvez pas ajouter d\'utilisateur portant cette adresse email'), $n) . '</div>';
  }

  $ret = array();

  $sql = "SELECT usr_id, usr_mail, usr_login, usr_nom, usr_prenom, activite, societe, fonction, pays, usr_sexe" .
          " FROM usr" .
          " WHERE usr_mail = :usr_mail" .
          " AND usr_login" .
          " NOT LIKE '(#deleted_%)#%' AND invite='0'";

  $bases = implode(',', array_keys(whatCanIAdmin($usr)));

  $stmt = $conn->prepare($sql);
  $stmt->execute(array(':usr_mail' => $mail));
  $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
  $stmt->closeCursor();


  foreach ($rs as $row)
  {
    $row['base'] = $row['watermark'] = $row['candwnldpreview'] = array();

    $sql = 'SELECT base_id, nowatermark, candwnldpreview
              FROM basusr WHERE usr_id = :usr_id
              AND base_id IN (' . $bases . ') AND actif="1"';
    $stmt = $conn->prepare($sql);

    $stmt->execute(array(':usr_id' => $row['usr_id']));
    $rsR = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    foreach ($rsR as $raw)
    {
      $row['base'][$raw['base_id']] = '1';
      $row['watermark'][$raw['base_id']] = ($raw['nowatermark'] ? 0 : 1);
      $row['candwnldpreview'][$raw['base_id']] = $raw['candwnldpreview'];
    }
    $ret[$row['usr_id']] = $row;
  }


  if ($out == 'HTML')
    $ret = formatUsrForm($usr, $lng, $usr_id, $ret);

  return $ret;
}

function formatUsrForm($usr, $lng, $usr_id, $datas)
{
  $registry = registry::get_instance();
  require_once($registry->get('GV_RootPath') . 'lib/classes/deprecated/countries.php');

  $ctry = getCountries($lng);

  $canAdmin = whatCanIAdmin($usr);

  $out = '<form id="ADD_USR_FORM" name="add_usr_form" action="push.feedback.php">
                  <div style="margin: 0pt 0pt 0pt 40px; width: 400px;">';

  if (count($datas) > 1)
  {
    $out .= '<div>' . _('push :: Plusieurs utilisateurs correspondant a cette addresse email ont ete trouves dans la base.') . _('push:: Ces utilisateurs ne sont pas presentes car ils n\'ont pas encore acces a une des collections que vous administrez ou parce qu\'ils sont fantomes.') . _('push:: Trouvez le profil correspondant a la personne que vous recherchez et donner lui acces a au moin l\'une de vos collection pour lui transmettre des documents') . '</div>
                  <select onchange="adduserDisp(this)">';

    $out .= '<option value="">' . _('choisir') . '</option>';
    foreach ($datas as $data)
    {
      $sel = $data['usr_id'] == $usr_id ? 'selected' : '';
      ;
      $out .= '<option ' . $sel . ' value="' . $data['usr_id'] . '">' . $data['usr_login'] . '</option>';
    }
    $out .= '</select>';
  }
  if (count($datas) == 1)
  {
    $usr_id = implode('', array_keys($datas));
    $out .= '<div>' . _('push :: Cet utilisateur a ete trouve dans la base, il correspond a l\'adresse email que vous avez renseigne') . '</div>';
  }
  $out .= '</div>';
  if ($usr_id != '' && isset($datas[$usr_id]))
  {
    $part = $datas[$usr_id];
  }
  else
  {
    $part = array(
        'usr_login' => ''
        , 'usr_nom' => ''
        , 'usr_prenom' => ''
        , 'usr_sexe' => ''
        , 'activite' => ''
        , 'fonction' => ''
        , 'pays' => ''
        , 'usr_mail' => ''
        , 'watermark' => ''
        , 'candwnldpreview' => ''
        , 'base' => ''
        , 'societe' => ''
        , 'usr_id' => ''
    );
  }
  if ((count($datas) > 1 && $usr_id != '') || count($datas) <= 1)
  {
    $out .= '<table style="margin: 40px 0pt 0pt 40px;">
                        <tr>
                            <td>
                                <label for="add_ident">' . _('admin::compte-utilisateur identifiant') . ' :</label>
                            </td>
                            <td colspan="3">
                                <input value="' . $part['usr_login'] . '" type="text" name="add_ident" id="add_ident" size="20"/>
                                <input value="' . $part['usr_id'] . '" type="hidden" name="add_id" id="add_id"/>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <label for="nothing">' . _('admin::compte-utilisateur sexe') . ' :</label>
                            </td>
                            <td colspan="3">
                                <input ' . ($part['usr_sexe'] == '0' ? 'checked' : '') . ' style="float:left;width:auto;" id="CIV_0" name="CIV" value="0" checked="checked" type="radio"/>
                                <label style="float:left;width:auto;" for="CIV_0">' . _('admin::compte-utilisateur:sexe: mademoiselle') . '</label>
                                <input ' . ($part['usr_sexe'] == '1' ? 'checked' : '') . ' style="float:left;width:auto;" id="CIV_1" value="1" name="CIV"" type="radio"/>
                                <label style="float:left;width:auto;" for="CIV_1">' . _('admin::compte-utilisateur:sexe: madame') . '</label>
                                <input ' . ($part['usr_sexe'] == '2' ? 'checked' : '') . ' style="float:left;width:auto;" id="CIV_2"" value="2" name="CIV" type="radio"/>
                                <label style="float:left;width:auto;" for="CIV_2">' . _('admin::compte-utilisateur:sexe: monsieur') . '</label>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <label for="add_nom">' . _('admin::compte-utilisateur nom') . ' : </label>
                            </td>
                            <td colspan="3">
                                <input value="' . $part['usr_nom'] . '" type="text" name="add_nom" id="add_nom" size="20"/>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <label for="add_prenom">' . _('admin::compte-utilisateur prenom') . ' : </label>
                            </td>
                            <td colspan="3">
                                <input value="' . $part['usr_prenom'] . '" type="text" name="add_prenom" id="add_prenom" size="20"/>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <label for="add_societe">' . _('admin::compte-utilisateur societe') . ' : </label>
                            </td>
                            <td colspan="3">
                                <input value="' . $part['societe'] . '" type="text" name="add_societe" id="add_societe" size="20"/>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <label for="add_fonction">' . _('admin::compte-utilisateur poste') . ' : </label>
                            </td>
                            <td colspan="3">
                                <input value="' . $part['fonction'] . '" type="text" name="add_fonction" id="add_fonction" size="20"/>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <label for="add_activite">' . _('admin::compte-utilisateur activite') . ' : </label>
                            </td>
                            <td colspan="3">
                                <input value="' . $part['activite'] . '" type="text" name="add_activite" id="add_activite" size="20"/>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <label for="add_pays">' . _('admin::compte-utilisateur pays') . ' : </label>
                            </td>
                            <td colspan="3">';
    $out .= '<select id="add_pays" name="add_pays" style="width:150px;">
                                                <option class="pays_switch" value="">' . _('choisir') . '</option>';
    foreach ($ctry as $k => $c)
    {
      $sel = $part['pays'] == $k ? 'selected' : '';
      $out .= '<option ' . $sel . ' class="pays_switch" value="' . $k . '">' . $c . '</option>';
    }
    $out .= '</select>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="4">
                                <span>' . _('push::L\'utilisateur cree doit pouvoir acceder a au moins l\'une de ces bases') . '</span>
                            </td>
                        </tr>
                        ';

    $out.= '
                                <tr><td> </td><td>' . _('push::Acces') . '</td>
                                <td>' . _('push::preview') . '</td>
                                <td>' . _('push::watermark') . '</td></tr>';

    foreach ($canAdmin as $base => $basename)
      $out.= '
                                <tr><td><span>' . $basename . '"</span></td><td><input ' . ((isset($part['base'][$base]) && $part['base'][$base] == 1) ? 'checked' : '') . ' type="checkbox" value="' . $base . '" class="baseinsc" name="baseinsc[]" /></td>
                                <td><input ' . ((isset($part['candwnldpreview'][$base]) && $part['candwnldpreview'][$base] == 1) ? 'checked' : '') . ' type="checkbox" value="' . $base . '" class="basepreview" name="basepreview[]" /></td>
                                <td><input ' . ((isset($part['watermark'][$base]) && $part['watermark'][$base] == 1) ? 'checked' : '') . ' type="checkbox" value="' . $base . '" class="basewm" name="basewm[]" /></td></tr>
                                        ';

    $out .= '

                        <tr>
                            <td><input type="button" value="' . _('boutton::valider') . '" onclick="addNewUser();" size="20"/></td>
                            <td colspan="3"><input type="button" value="' . _('boutton::annuler') . '" onclick="cancelAddUser();" size="20"/></td>
                        </tr>
                        </table></div>


                    ';
  }

  return $out;
}

function sendHdOk($usr, $lst)
{


  $conn = connection::getPDOConnection();

  $ret = array();

  $bases = array();
  foreach ($lst as $basrec)
  {
    $basrec = explode('_', $basrec);
    if (count($basrec) == 2)
    {
      $record = new record_adapter($basrec[0], $basrec[1]);

      $bases[] = $record->get_base_id();
      unset($record);
    }
  }

  $bases = implode(',', array_unique($bases));
  if ($bases != '')
  {
    $sql = 'SELECT base_id, candwnldhd FROM basusr WHERE usr_id = :usr_id
      AND base_id IN (' . $bases . ') AND actif="1" AND candwnldhd="1" ';

    $stmt = $conn->prepare($sql);
    $stmt->execute(array(':usr_id' => $usr));
    $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    foreach ($rs as $row)
    {
      $ret[] = $row['base_id'];
    }
  }

  return $ret;
}

function whatCanIAdmin($usr)
{
  $conn = connection::getPDOConnection();

  $canAdmin = array();

  $sql = "SELECT bu.canAdmin,bu.base_id FROM basusr bu, bas b
          WHERE bu.usr_id = :usr_id AND b.base_id=bu.base_id AND b.active='1'";

  $stmt = $conn->prepare($sql);
  $stmt->execute(array(':usr_id' => $usr));
  $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
  $stmt->closeCursor();

  foreach ($rs as $row)
  {
    if ($row["canAdmin"] == "1")
      $canAdmin[$row['base_id']] = phrasea::bas_names($row['base_id']);
  }

  return $canAdmin;
}

function getPushLanguage($usr, $lng)
{
  $ret = array();
  $str = array(
      "selNameEmptyVal"
      , "notInList"
      , "userssel"
      , "wrongmail"
      , "noUsersSel"
      , "selNameEmpty"
  );


  $ret["selNameEmptyVal"] = _('push::alertjs: un panier doit etre cree pour votre envoi, merci de specifier un nom');
  $ret["notInList"] = _('push::alertjs: vous n\'etes pas dans la liste des personne validant, voulez vous etre ajoute ?');
  $ret["userssel"] = _('phraseanet::utilisateurs selectionnes');
  $ret["wrongmail"] = _('phraseanet:: email invalide');
  $ret["noUsersSel"] = _('push::alertjs: aucun utilisateur n\'est selectionne');
  $ret["selNameEmpty"] = _('push::alertjs: vous devez specifier un nom de panier');
  $ret['removeIlist'] = _('push:: supprimer la recherche');
  $ret['removeList'] = _('push:: supprimer la(es) liste(s) selectionnee(s)');

  return p4string::jsonencode($ret);
}

function createUserOnFly($usr, $arrayUsr, $arrayBases, $arrayPrev=array(), $arrayWm=array())
{



  $id = trim(stripslashes(urldecode($arrayUsr['ID'])));
  $ident = trim(urldecode($arrayUsr['IDENT']));
  $mail = trim(urldecode($arrayUsr['MAIL']));
  $nom = trim(urldecode($arrayUsr['NOM']));
  $prenom = trim(urldecode($arrayUsr['PREN']));
  $societe = trim(urldecode($arrayUsr['SOCIE']));
  $fonction = trim(urldecode($arrayUsr['FUNC']));
  $activite = trim(urldecode($arrayUsr['ACTI']));
  $country = trim(urldecode($arrayUsr['COUNTRY']));
  $dateEnd = trim(urldecode($arrayUsr['DATE_END']));
  $sexe = $arrayUsr['CIV'];

  $conn = connection::getPDOConnection();

  $n = 1;
  if ($ident == "" && !is_numeric($id))
  {
    if ($nom == "")
    {
      $ident = explode('@', $mail);
      $ident = $ident[0];
    }else
      $ident = $nom;
  }else
    $n = 0;
  while ($n != 0)
  {
    $usr_id = User_Adapter::get_usr_id_from_login($ident);
    if($usr_id)
    {
      $n = 1;
      $ident.=rand(0, 9);
    }
  }

  if (is_numeric($id))
  {
    $sql = 'SELECT usr_id FROM usr
            WHERE usr_id = :usr_id AND usr_mail = :usr_mail
              AND usr_login = :usr_login';
    $stmt = $conn->prepare($sql);
    $stmt->execute(array(':usr_id' => $id, ':usr_mail' => $mail, ':usr_login' => $ident));
    $num_rows = $stmt->rowCount();
    $stmt->closeCursor();

    if ($num_rows == 0)

      return '-23';
    else
      $id = $id;

// verifier que jai bien le droit dediter ce mec
  }
  else
  {
//verifier que ya tjrs pas d'user avec le meme mail

    if (count(newUserCheckMail($usr, '', $mail, '', 'PHP')) != 0)
    {
      return '-24';
    }

    try
    {
      $appbox = appbox::get_instance();
      $password = random::generatePassword(24);
      $user = User_Adapter::create($appbox, $ident, $password, $mail, false, false);

      $user->set_company($societe)
              ->set_job($activite)
              ->set_position($fonction)
              ->set_gender($sexe)
              ->set_firstname($prenom)
              ->set_lastname($nom);

      return $user->get_id();
    }
    catch (Exception $e)
    {
      return '-2';
    }
  }

  foreach ($arrayBases as $base)
  {
    if (is_numeric($base))
    {
      $timeLimit = '0';
      $limitedTo = '0000-00-00 00:00:00';
      if ($dateEnd != '')
      {
        $timeLimit = '1';
        $limitedTo = $dateEnd;
      }
      $sql = "INSERT INTO basusr" .
              " (base_id, usr_id, actif, creationdate,time_limited,limited_to )" .
              " VALUES (:base_id, :usr_id, '1',now() ,:time_limited ,:time_limit_to)";

      $params = array(
          ':base_id' => $base
          , ':usr_id' => $id
          , ':time_limited' => $timeLimit
          , ':time_limit_to' => $limitedTo
      );
      $stmt = $conn->prepare($sql);
      $stmt->execute($params);
      $stmt->closeCursor();

      $sql = "INSERT INTO sbasusr
              (sbas_id, usr_id)
              VALUES (:sbas_id, :usr_id)";

      $params = array(':sbas_id' => phrasea::sbasFromBas($base), ':usr_id' => $id);
      $stmt = $conn->prepare($sql);
      $stmt->execute($params);
      $stmt->closeCursor();
    }
  }
  foreach ($arrayPrev as $base)
  {
    if (is_numeric($base) && in_array($base, $arrayBases))
    {
      $sql = "UPDATE basusr SET candwnldpreview='1'
              WHERE usr_id = :usr_id AND base_id = :base_id";
      $stmt = $conn->prepare($sql);
      $stmt->execute(array(':usr_id' => $id, ':base_id' => $base));
      $stmt->closeCursor();
    }
  }
  foreach ($arrayWm as $base)
  {
    if (is_numeric($base) && in_array($base, $arrayBases))
    {
      $sql = "UPDATE basusr SET nowatermark='0'
              WHERE usr_id = :usr_id AND base_id = :base_id";
      $stmt = $conn->prepare($sql);
      $stmt->execute(array(':usr_id' => $id, ':base_id' => $base));
      $stmt->closeCursor();
    }
  }

  return $id;
}

function whatCanIPush($usr, $lst)
{
  $newlst = array();

  $user = User_Adapter::getInstance($usr, appbox::get_instance());

  foreach ($lst as $basrec)
  {
    $basrec = explode('_', $basrec);
    if (count($basrec) != 2)
      continue;

    $sbas_id = $basrec[0];
    try
    {
      $record = new record_adapter($sbas_id, $basrec[1]);
    }
    catch(Exception $e)
    {
      continue;
    }
    $base_id = $record->get_base_id();

    if (!$user->ACL()->has_right_on_base($base_id, 'canpush'))
      continue;

    if ($record->is_grouping())
    {
      foreach ($record->get_children() as $tmpl)
        $newlst[] = sprintf("%s_%s", $tmpl->get_base_id(), $tmpl->get_record_id());
    }
    else
    {
      $newlst[] = implode('_', $basrec);
    }
    unset($record);
  }

  return $newlst;
}

function loadUsers($usr, $token, $filters)
{
  $appbox = appbox::get_instance();
  $session = $appbox->get_session();
  $registry = $appbox->get_registry();
  require_once($registry->get('GV_RootPath') . 'lib/classes/deprecated/countries.php');

  $conn = $appbox->get_connection();
  $out = array();

  $datas = sqlFromFilters($usr, $filters);

  $sql = $datas['sql'];
  $params = $datas['params'];

  $ret = 0;

  $push_datas = $session->storage()->get('push', array());
  if (isset($push_datas[$token]))
  {
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    foreach ($rs as $row)
    {
      $push_datas[$token]['usrs'][$row['usr_id']] = array('HD' => 0);
    }
    $session->storage()->set('push', $push_datas);
    $ret = count($push_datas[$token]['usrs']);
  }

  return $ret;
}

function unloadUsers($usr, $token, $filters)
{
  $appbox = appbox::get_instance();
  $session = $appbox->get_session();
  $registry = $appbox->get_registry();
  require_once($registry->get('GV_RootPath') . 'lib/classes/deprecated/countries.php');

  $out = array();

  $ret = -1;
  $push_datas = $session->storage()->get('push', array());
  if (isset($push_datas[$token]))
  {
    $push_datas[$token]['usrs'] = array();
    $session->storage()->set('push', $push_datas);
    $ret = count($push_datas[$token]['usrs']);
  }

  return $ret;
}

function addUser($usr, $token, $usr_ids)
{
  $appbox = appbox::get_instance();
  $session = $appbox->get_session();

  $ret = array('result' => array(), 'selected' => 0);

  $conn = $appbox->get_connection();

  $datas = sqlFromFilters($usr, '');

  $sql = $datas['sql'];
  $params = $datas['params'];

  $push_datas = $session->storage()->get('push', array());
  if (isset($push_datas[$token]))
  {
    $usr_ids = json_decode(stripslashes($usr_ids));

    $result = array();
    foreach ($usr_ids as $usr_id => $add)
    {
      $zsql = $sql . ' AND usr.usr_id = :extra_usr_id';

      $params[':extra_usr_id'] = $usr_id;

      $stmt = $conn->prepare($zsql);
      $stmt->execute($params);
      $num_rows = $stmt->rowCount();
      $stmt->closeCursor();

      if ($num_rows == 1)
      {
        if ($add->sel == '0')
        {
          unset($push_datas[$token]['usrs'][$usr_id]);
          $result[$usr_id] = 0;
        }
        if ($add->sel == '1')
        {
          $hd_value = '0';
          if ($add->hd == '1')
            $hd_value = '1';
          $push_datas[$token]['usrs'][$usr_id] = array('HD' => $hd_value);
          $result[$usr_id] = 1;
        }
      }
    }
    $session->storage()->set('push', $push_datas);
    $ret = array('result' => $result, 'selected' => count($push_datas[$token]['usrs']));
  }

  return p4string::jsonencode($ret);
}

function sqlFromFilters($usr, $filters)
{
  $conn = connection::getPDOConnection();

  $params = array();
  $baslist = array();

  $sql = 'SELECT DISTINCT(b.base_id) FROM (bas b, basusr u)' .
          ' WHERE u.usr_id = :usr_id' .
          ' AND b.base_id =u.base_id' .
          ' AND u.canpush="1"' .
          ' AND u.actif="1"' .
          ' AND b.active="1"';

  $stmt = $conn->prepare($sql);
  $stmt->execute(array(':usr_id' => $usr));
  $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
  $stmt->closeCursor();

  foreach ($rs as $row)
  {
    $baslist[] = $row['base_id'];
  }
  $baslist = implode(',', $baslist);
  $precise = '';

  $filters = $filters != '' ? json_decode(urldecode($filters)) : false;
  if ($filters)
  {
    foreach ($filters->strings as $filter)
    {
      if (trim($filter->fieldsearch) == '')
        continue;
      $like = ' LIKE ';

      switch ($filter->operator)
      {
        case 'and':
          $precise .= ' AND ';
          break;
        case 'or':
          $precise .= ' OR ';
          break;
        case 'except':
          $precise .= ' AND ';
          $like = ' NOT LIKE ';
          break;
      }
      switch ($filter->fieldlike)
      {
        case 'BEGIN':
          $start = '';
          $end = '%';
          break;
        case 'CONT':
          $start = '%';
          $end = '%';
          break;
        case 'END':
          $start = '%';
          $end = '';
          break;
      }
      switch ($filter->field)
      {
        case "LOGIN" :
          $precise.=" (usr_login " . $like . " :like1 COLLATE utf8_general_ci )";
          $params[':like1'] = $start . $filter->fieldsearch . $end;
          break;
        case "NAME" :
          $precise.=" (usr_nom " . $like . " :like2 OR usr_prenom like :like2bis)";
          $params[':like2'] = $start . $filter->fieldsearch . $end;
          $params[':like2bis'] = $start . $filter->fieldsearch . $end;
          break;
        case "COMPANY" :
          $precise.=" (usr.societe " . $like . " :like3)";
          $params[':like3'] = $start . $filter->fieldsearch . $end;
          break;
        case "MAIL" :
          $precise.=" (usr.usr_mail " . $like . " :like4)";
          $params[':like4'] = $start . $filter->fieldsearch . $end;
          break;
        case "FCT" :
          $precise.=" (usr.fonction " . $like . " :like5)";
          $params[':like5'] = $start . $filter->fieldsearch . $end;
          break;
        case "ACT" :
          $precise.=" (usr.activite " . $like . " :like6)";
          $params[':like6'] = $start . $filter->fieldsearch . $end;
          break;
        case "LASTMODEL" :
          $precise.=" (usr.lastModel " . $like . " :like7)";
          $params[':like7'] = $start . $filter->fieldsearch . $end;
          break;
      }
    }
    if (count($filters->lists) > 0 && trim($filters->lists[0]) != '')
    {
      $precise.=' AND usr.usr_id IN
          (SELECT ulu.usr_id FROM usrlistusers ulu, usrlist ul
            WHERE ul.usr_id = :usr_id_list
              AND ul.list_id IN (' . implode(',', $filters->lists) . ')
              AND ul.list_id = ulu.list_id) ';
      $params[':usr_id_list'] = $usr;
    }
    if (count($filters->countries) > 0 && trim($filters->countries[0]) != '')
    {
      $c = array();
      $n = 0;
      foreach ($filters->countries as $country)
      {
        $c['country' . $n] = $country;
        $n++;
      }
      $precise.=" AND usr.pays IN (:" . implode(", :", array_keys($c)) . ")";
      $params = array_merge($params, $c);
    }
    if (count($filters->activite) > 0 && trim($filters->activite[0]) != '')
    {
      $c = array();
      $n = 0;
      foreach ($filters->activite as $activite)
      {
        $c['activite' . $n] = $activite;
        $n++;
      }
      $precise.=" AND usr.activite IN (:" . implode(", :", array_keys($c)) . ")";
      $params = array_merge($params, $c);
    }
    if (count($filters->fonction) > 0 && trim($filters->fonction[0]) != '')
    {
      $c = array();
      $n = 0;
      foreach ($filters->fonction as $fonction)
      {
        $c['fonction' . $n] = $fonction;
        $n++;
      }
      $precise.=" AND usr.fonction IN (:" . implode(", :", array_keys($c)) . ")";
      $params = array_merge($params, $c);
    }
    if (count($filters->societe) > 0 && trim($filters->societe[0]) != '')
    {
      $c = array();
      $n = 0;
      foreach ($filters->societe as $societe)
      {
        $c['societe' . $n] = $societe;
        $n++;
      }
      $precise.=" AND usr.societe IN (:" . implode(", :", array_keys($c)) . ")";
      $params = array_merge($params, $c);
    }
    if (count($filters->template) > 0 && trim($filters->template[0]) != '')
    {
      $c = array();
      $n = 0;
      foreach ($filters->template as $template)
      {
        $c['template' . $n] = $template;
        $n++;
      }
      $precise.=" AND usr.lastModel IN (:" . implode(", :", array_keys($c)) . ")";
      $params = array_merge($params, $c);
    }
  }
  $sqlGhost = '';
  if (count(whatCanIAdmin($usr)) > 0)
    $sqlGhost = ' OR (isnull(b.base_id)) ';

  $sql = 'SELECT DISTINCT usr.usr_id,usr_login, usr_mail
            ,CONCAT_WS(" ",usr_nom,usr_prenom) as usr_nomprenom,societe,
            fonction,activite,pays,lastModel
          FROM usr
            LEFT JOIN basusr b ON b.usr_id=usr.usr_id
          WHERE (b.base_id IN (' . $baslist . ') ' . $sqlGhost . ' )
            AND usr_login not like "(#deleted_%"
            AND usr.model_of=0 ' . $precise . ' AND invite="0"
            AND usr_login!="invite" AND usr_login!="autoregister"';

  return array('sql' => $sql, 'params' => $params);
}

function hd_user($usr, $token, $usrs, $value)
{
  $appbox = appbox::get_instance();
  $session = $appbox->get_session();

  $push_datas = $session->storage()->get('push', array());
  if (isset($push_datas[$token]))
  {
    foreach ($usrs as $u)
    {
      if (isset($push_datas[$token]['usrs'][$u]))
      {
        $push_datas[$token]['usrs'][$u]['HD'] = $value;
      }
    }
    $session->storage()->set('push', $push_datas);
  }
}

function whoCanIPush($usr, $lng, $token, $view, $filters, $page=1, $sort='LA', $perPage='')
{
  $appbox = appbox::get_instance();
  $session = $appbox->get_session();
  $registry = $appbox->get_registry();
  require_once($registry->get('GV_RootPath') . 'lib/classes/deprecated/countries.php');

  $ctry = getCountries($lng);

  $conn = $appbox->get_connection();

  $out = '';

  if ($view == 'current')
    $filters = '';
  $datas = sqlFromFilters($usr, $filters);
  $sql = $datas['sql'];
  $params = $datas['params'];

  $push_datas = $session->storage()->get('push', array());
  if ($view == 'search' && count($push_datas[$token]['usrs']))
  {
    $sql .= ' AND usr.usr_id NOT IN (' . implode(',', array_keys($push_datas[$token]['usrs'])) . ') ';
  }
  if ($view == 'current')
  {
    $sql .= ' AND usr.usr_id IN (' . implode(',', array_keys($push_datas[$token]['usrs'])) . ') ';
  }


  $nPage = $nresult = 0;
  $stmt = $conn->prepare($sql);
  $stmt->execute($params);
  $nresult = $stmt->rowCount();
  $stmt->closeCursor();

  $nPage = ceil($nresult / $perPage);

  if ($page > $nPage)
    $page = $nPage;

  if (!isset($push_datas[$token]))

    return;

  $orderBy = array();

  $sort = $sort != '' ? json_decode(urldecode($sort)) : array();
  $lact = $lsort = $nact = $nsort = $mact = $msort = $sact = $ssort = $jact = $jsort = $aact = $asort = $cact = $csort = $tact = $tsort = '';


  foreach ($sort as $s)
  {
    switch ($s)
    {
      case 'MA';
        $orderBy[] = 'usr_mail ASC';
        $mact = 'active';
        $msort = 'SortUp';
        break;
      case 'MD';
        $orderBy[] = 'usr_mail DESC';
        $mact = 'active';
        $msort = 'SortDown';
        break;
      case 'NA';
        $orderBy[] = 'usr_nomprenom ASC';
        $nact = 'active';
        $nsort = 'SortUp';
        break;
      case 'ND';
        $orderBy[] = 'usr_nomprenom DESC';
        $nact = 'active';
        $nlsort = 'SortDown';
        break;
      case 'LA';
        $orderBy[] = 'usr_login ASC';
        $lact = 'active';
        $lsort = 'SortUp';
        break;
      case 'LD';
        $orderBy[] = 'usr_login DESC';
        $lact = 'active';
        $lsort = 'SortDown';
        break;
      case 'SA';
        $orderBy[] = 'societe ASC';
        $sact = 'active';
        $ssort = 'SortUp';
        break;
      case 'SD';
        $orderBy[] = 'societe DESC';
        $sact = 'active';
        $ssort = 'SortDown';
        break;
      case 'JA';
        $orderBy[] = 'fonction ASC';
        $jact = 'active';
        $jsort = 'SortUp';
        break;
      case 'JD';
        $orderBy[] = 'fonction DESC';
        $jact = 'active';
        $jsort = 'SortDown';
        break;
      case 'AA';
        $orderBy[] = 'activite ASC';
        $aact = 'active';
        $asort = 'SortUp';
        break;
      case 'AD';
        $orderBy[] = 'activite DESC';
        $aact = 'active';
        $asort = 'SortDown';
        break;
      case 'CA';
        $orderBy[] = 'pays ASC';
        $cact = 'active';
        $csort = 'SortUp';
        break;
      case 'CD';
        $orderBy[] = 'pays DESC';
        $cact = 'active';
        $csort = 'SortDown';
        break;
      case 'TA';
        $orderBy[] = 'lastModel ASC';
        $tact = 'active';
        $tsort = 'SortUp';
        break;
      case 'TD';
        $orderBy[] = 'lastModel DESC';
        $tact = 'active';
        $tsort = 'SortDown';
        break;
    }
  }

  if (count($orderBy) > 0)
    $sql .= ' ORDER BY ' . implode(', ', $orderBy) . '';


  $start_offset = ($page - 1) >= 0 ? ($page - 1) : 0;


  $sql .= ' LIMIT ' . ($start_offset * $perPage) . ', ' . $perPage . '';

  $out .= '<div class="pager" id="pager" style="margin: 12px auto 3px; text-align: center;">
    <form>
        <img class="first" onclick="specialsearch(false,1)" src="/skins/icons/first.png"/>
        <img class="prev" ' . (($page - 1) > 0 ? ("onclick='specialsearch(false," . ($page - 1) . ")'") : "") . ' src="/skins/icons/prev.png"/>
        <input type="text" class="pagedisplay" value="' . $page . '/' . $nPage . '"/>
        <img class="next" ' . (($page + 1) > $nPage ? "" : "onclick='specialsearch(false," . ($page + 1) . ")'") . ' src="/skins/icons/next.png"/>
        <img class="last" onclick="specialsearch(false,' . ($nPage) . ')" src="/skins/icons/last.png"/>

';
  $out .= '<select class="pagesize" onclick="setPerPage();" id="pagesizer">
            <option ' . ($perPage == 10 ? 'selected' : '') . ' value="10">10</option>
            <option ' . ($perPage == 20 ? 'selected' : '') . ' value="20">20</option>
            <option ' . ($perPage == 30 ? 'selected' : '') . ' value="30">30</option>
            <option ' . ($perPage == 40 ? 'selected' : '') . ' value="40">40</option>
        </select></form></div>';
  $out .= "<div id='search_list' style='width:100%'>";

  $out .= "<table cellspacing='1' border='0' id='BLABLA' class=\"pushlist tablesorter\">";
  $out .= "<colgroup>";
  $out .= "<col width='11%'>";
  $out .= "<col width='12%'>";
  $out .= "<col width='20%'>";
  $out .= "<col width='12%'>";
  $out .= "<col width='12%'>";
  $out .= "<col width='12%'>";
  $out .= "<col width='10%'>";
  $out .= "<col width='11%'>";
  $out .= "<col width='20px'>";
  $out .= "</colgroup>";
  $out .= "<thead><tr><th colspan='8'  style='background-image:none;text-align:center;'>" . sprintf(_('push:: %d resultats'), $nresult) . " -
                <a href='#' onclick='loadUsers();return false;'>" . _('push:: tous les ajouter') . "</a>  ---
                " . sprintf(_("push:: %s selectionnes"), "<span id='alert_nbuser'>" . count($push_datas[$token]['usrs']) . "</span>") . " -
                <a href='#' onclick='$(\"#saveList, #saveListButton\").toggle();' id='saveListButton'> " . _('push:: enregistrer cette liste') . " </a><span id='saveList' style='display:none;'><input  type='text' id='NEW_LST'/> <img onclick='saveList();return false;' src='/skins/icons/save.png' /> <img src='/skins/icons/delete.gif' onclick='$(\"#saveList, #saveListButton\").toggle();'/> </span> /
                <a href='#' onclick='unloadUsers();'>" . _('push:: tout deselectionner') . "</a> ---
                                        <span " . ($view != 'all' ? 'style="background-color:red;"' : '') . ">" . _('push:: afficher :') . "</span><select  style='width:60px;' onchange='toggleView(this)'>
                                            <option " . ($view == 'all' ? 'selected' : '') . " value='all'>" . _('push:: afficher la recherche') . "</option>
                                            <option " . ($view == 'current' ? 'selected' : '') . " value='current'>" . _('push:: afficher la selection') . "</option>
                                        </select>
                </th></tr><tr>";
  $out .= "<th class='REFL " . $lact . " " . $lsort . "' id='TREFL'>" . _('admin::compte-utilisateur identifiant') . "</th>";
  $out .= "<th class='REFN " . $nact . " " . $nsort . "' id='TREFN'>" . _('admin::compte-utilisateur nom') . '/' . _('admin::compte-utilisateur prenom') . "</th>";
  $out .= "<th class='REFM " . $mact . " " . $msort . "' id='TREFM'>" . _('admin::compte-utilisateur email') . "</th>";
  $out .= "<th class='REFS " . $sact . " " . $ssort . "' id='TREFS'>" . _('admin::compte-utilisateur societe') . "</th>";
  $out .= "<th class='REFJ " . $jact . " " . $jsort . "' id='TREFJ'>" . _('admin::compte-utilisateur poste') . "</th>";
  $out .= "<th class='REFA " . $aact . " " . $asort . "' id='TREFA'>" . _('admin::compte-utilisateur activite') . "</th>";
  $out .= "<th class='REFC " . $cact . " " . $csort . "' id='TREFC'>" . _('admin::compte-utilisateur pays') . "</th>";
  $out .= "<th class='REFT " . $tact . " " . $tsort . "' id='TREFT'>" . _('admin::compte-utilisateur dernier modele applique') . "</th>";
  $out .= "<th><img src='/skins/icons/HD-down.png' title='" . _('push:: donner les droits de telechargement HD') . "'/></th>";


  $out .= "</tr></thead>";
  $out .= "<tbody>";
  $out .= "";


  $out .= "";
  $ilig = 0;

  $stmt = $conn->prepare($sql);
  $stmt->execute($params);
  $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
  $stmt->closeCursor();

  foreach ($rs as $row)
  {

    $sel = $hd_checked = '';
    if (array_key_exists($row["usr_id"], $push_datas[$token]['usrs']))
    {
      $sel = 'selected';
      if ($push_datas[$token]['usrs'][$row["usr_id"]]['HD'] == '1')
        $hd_checked = 'checked';
      if ($view == 'search')
        continue;
    }
    else
    {
      if ($view == 'current')
        continue;
    }
    $out .= "<tr class='" . $sel . "' onclick=\"addUser(event,'" . $row["usr_id"] . "',this);\" s='0' id='USER_" . $row["usr_id"] . "'>";
    $out .= "<td>" . $row["usr_login"] . "</td>";

    $out .= "<td>" . $row["usr_nomprenom"] . "</td>";
    $out .= "<td>" . $row["usr_mail"] . "</td>";
    $out .= "<td>" . $row["societe"] . "</td>";
    $out .= "<td>" . $row["fonction"] . "</td>";
    $out .= "<td>" . $row["activite"] . "</td>";

    $pays = "";
    if (isset($ctry[trim($row["pays"])]))
      $pays = $ctry[trim($row["pays"])];

    $out .= "<td>" . $pays . "</td>";
    $out .= "<td>" . $row["lastModel"] . "</td>";
    $out .= "<td><input " . $hd_checked . " type='checkbox' name='hd_box' value='1' onchange='checkHD(event,this," . $row["usr_id"] . ")'/></td>";
    $out .= "</tr>";
    $ilig++;
  }

  if ($ilig > 11)
  {
    $out .= "<tfoot><tr>";
    $out .= "<th class='REFL " . $lact . " " . $lsort . "' id='BREFL'>" . _('admin::compte-utilisateur identifiant') . "</th>";
    $out .= "<th class='REFN " . $nact . " " . $nsort . "' id='BREFN'>" . _('admin::compte-utilisateur nom') . '/' . _('admin::compte-utilisateur prenom') . "</th>";
    $out .= "<th class='REFM " . $mact . " " . $msort . "' id='BREFM'>" . _('admin::compte-utilisateur email') . "</th>";
    $out .= "<th class='REFS " . $sact . " " . $ssort . "' id='BREFS'>" . _('admin::compte-utilisateur societe') . "</th>";
    $out .= "<th class='REFJ " . $jact . " " . $jsort . "' id='BREFJ'>" . _('admin::compte-utilisateur poste') . "</th>";
    $out .= "<th class='REFA " . $aact . " " . $asort . "' id='BREFA'>" . _('admin::compte-utilisateur activite') . "</th>";
    $out .= "<th class='REFC " . $cact . " " . $csort . "' id='BREFC'>" . _('admin::compte-utilisateur pays') . "</th>";
    $out .= "<th class='REFT " . $tact . " " . $tsort . "' id='BREFT'>" . _('admin::compte-utilisateur dernier modele applique') . "</th>";
    $out .= "<th></th>";
    $out .= "</tr></tfoot>";
  }
  $out .= "</tbody>";
  $out .= "</table></div>" .
          "";

  $out .= "";

  return $out;
}

function saveiList($usr, $lng, $name, $token, $filters)
{
  $registry = registry::get_instance();
  require_once($registry->get('GV_RootPath') . 'lib/classes/deprecated/countries.php');

  $ret = -1;

  $conn = connection::getPDOConnection();

  $ilists = new stdClass();

  $sql = 'SELECT push_list FROM usr WHERE usr_id = :usr_id';

  $stmt = $conn->prepare($sql);
  $stmt->execute(array(':usr_id' => $usr));
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  $stmt->closeCursor();

  if ($row && $row['push_list'] != '')
  {
    $ilists = json_decode($row['push_list']);
  }

  if (($filters = json_decode($filters)) !== false)
  {
    $label = $name;
    $n = 2;
    while (isset($ilists->$label))
    {
      $label = $name . '#' . $n;
      $n++;
    }
    $ilists->$label = $filters;

    $sql = 'UPDATE usr SET push_list = :ilists WHERE usr_id = :usr_id';
    $stmt = $conn->prepare($sql);
    $stmt->execute(array(':ilists' => p4string::jsonencode($ilists), ':usr_id' => $usr));
    $stmt->closeCursor();
    $ret = loadILists($usr, $lng, $label);
  }

  return $ret;
}

function loadILists($usr, $lng, $name='')
{
  $conn = connection::getPDOConnection();

  $lists = array();

  $html = '<option value="" >' . _('choisir') . '</option>';

  $sql = 'SELECT push_list FROM usr WHERE usr_id = :usr_id';
  $stmt = $conn->prepare($sql);
  $stmt->execute(array(':usr_id' => $usr));
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  $stmt->closeCursor();

  if ($row)
  {
    if ($ilists = json_decode($row['push_list']))
    {
      foreach ($ilists as $k => $v)
      {
        $sel = "";
        if ($k == $name)
          $sel = 'selected="selected"';
        $html .= "<option " . $sel . " value='$k'>" . $k . "</option>";
      }
    }
  }

  return $html;
}

function loadIList($name)
{
  $appbox = appbox::get_instance();
  $session = $appbox->get_session();
  $usr = $session->get_usr_id();

  $conn = $appbox->get_connection();

  $sql = 'SELECT push_list FROM usr WHERE usr_id = :usr_id';
  $stmt = $conn->prepare($sql);
  $stmt->execute(array(':usr_id' => $usr));
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  $stmt->closeCursor();

  if ($row)
  {
    if ($ilists = json_decode($row['push_list']))
    {
      if (isset($ilists->$name))
        $ret = $ilists->$name;
      else
        $ret = array(
            'strings' => array()
            , 'countries' => array()
            , 'fonction' => array()
            , 'activite' => array()
            , 'lists' => array()
            , 'societe' => array()
            , 'template' => array()
        );
    }
  }

  return p4string::jsonencode($ret);
}

function saveList($usr, $lng, $name, $token)
{
  $appbox = appbox::get_instance();
  $session = $appbox->get_session();
  $registry = $appbox->get_registry();
  require_once($registry->get('GV_RootPath') . 'lib/classes/deprecated/countries.php');

  $ret = '-1' . 'ses';

  $conn = $appbox->get_connection();

  $label = $name;

  $sql = 'SELECT label FROM usrlist WHERE usr_id = :usr_id AND label = :label';
  $stmt = $conn->prepare($sql);
  $stmt->execute(array(':usr_id' => $usr, ':label' => $label));
  $n = $stmt->rowCount();
  $stmt->closeCursor();

  $m = 2;
  while ($n > 0)
  {
    $label = $name . '#' . $m;
    $stmt = $conn->prepare($sql);
    $stmt->execute(array(':usr_id' => $usr, ':label' => $label));
    $n = $stmt->rowCount();
    $stmt->closeCursor();
    $m++;
  }

  $ret = '-1';

  $push_datas = $session->storage()->get('push', array());

  if (isset($push_datas[$token]) && count($push_datas[$token]['usrs']) > 0)
  {
    $sql = 'INSERT into usrlist (list_id, usr_id, label)
            VALUES (null, :usr_id, :label)';
    $stmt = $conn->prepare($sql);
    $stmt->execute(array(':usr_id' => $usr, ':label' => $label));
    $stmt->closeCursor();

    $list_id = $conn->lastInsertId();

    $sql = 'INSERT INTO usrlistusers (list_id, usr_id)
              VALUES (:list_id,:usr_id)';
    $stmt = $conn->prepare($sql);

    foreach ($push_datas[$token]['usrs'] as $usr_id => $cool)
    {
      $stmt->execute(array(':list_id' => $list_id, ':usr_id' => $usr_id));
    }

    $stmt->closeCursor();
    $ret = loadLists($usr, $lng);
  }

  return $ret;
}

function loadLists($usr, $lng, $name='')
{
  $registry = registry::get_instance();
  require_once($registry->get('GV_RootPath') . 'lib/classes/deprecated/countries.php');

  $conn = connection::getPDOConnection();

  $lists = array();

  $html = '<option value="" >Toutes</option>';
  $sql = 'SELECT l.label, l.list_id, COUNT(u.usr_id) as nusr
          FROM (usr s, usrlist l)
            LEFT JOIN  usrlistusers u
              ON (l.list_id = u.list_id AND u.usr_id = s.usr_id)
          WHERE l.usr_id = :usr_id AND s.usr_login NOT LIKE "(#deleted_%"
          GROUP BY l.label ORDER BY l.label ASC';

  $stmt = $conn->prepare($sql);
  $stmt->execute(array(':usr_id' => $usr));
  $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
  $stmt->closeCursor();

  foreach ($rs as $row)
  {
    $sel = "";
    if ($name != '' && $row['label'] == $name)
      $sel = "selected='selected'";

    $html .= "<option " . $sel . " value='" . $row['list_id'] . "'>" . $row['label'] . " (" . $row['nusr'] . " users)</option>";
  }

  return $html;
}

function deleteList($usr, $lists, $lng)
{
  $registry = registry::get_instance();
  require_once($registry->get('GV_RootPath') . 'lib/classes/deprecated/countries.php');

  $conn = connection::getPDOConnection();
  $lists = json_decode($lists);
  foreach ($lists as $list)
  {
    $sql = "DELETE FROM usrlist WHERE list_id = :list_id AND usr_id = :usr_id";
    $stmt = $conn->prepare($sql);
    $stmt->execute(array(':list_id' => $list, ':usr_id' => $usr));
    $stmt->closeCursor();

    $sql = 'DELETE FROM usrlistusers WHERE list_id = :list_id';
    $stmt = $conn->prepare($sql);
    $stmt->execute(array(':list_id' => $list));
    $stmt->closeCursor();
  }

  return loadLists($usr, $lng);

  return $html;
}

function deleteiList($usr, $name, $lng)
{
  $conn = connection::getPDOConnection();

  $sql = "SELECT push_list FROM usr WHERE usr_id = :usr_id";
  $stmt = $conn->prepare($sql);
  $stmt->execute(array(':usr_id' => $usr));
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  $stmt->closeCursor();

  if ($row)
  {
    $lists = json_decode($row['push_list']);
    if (isset($lists->$name))
    {
      unset($lists->$name);
    }

    $sql = 'UPDATE usr SET push_list = :lists WHERE usr_id = :usr_id';
    $stmt = $conn->prepare($sql);
    $stmt->execute(array(':lists' => p4string::jsonencode($lists), ':usr_id' => $usr));
    $stmt->closeCursor();
  }

  $ret = loadiLists($usr, $lng);

  return $ret;
}

function getUsrInfos($usr, $arrayUsrs)
{
  $conn = connection::getPDOConnection();

  $usrs = array();

  $sql = 'SELECT usr_id,usr_mail, usr_login, usr_password, usr_nom, usr_prenom
          FROM usr WHERE usr_id IN (' . implode(',', $arrayUsrs) . ')';

  $stmt = $conn->prepare($sql);
  $stmt->execute();
  $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
  $stmt->closeCursor();

  foreach ($rs as $row)
  {
    $usrs[$row['usr_id']] = $row;
  }

  return $usrs;
}

function pushIt($usr, $newBask, $parmLST, $users, $mail_content, $lng, $accuse)
{
  $appbox = appbox::get_instance();
  $session = $appbox->get_session();
  $registry = $appbox->get_registry();
  $finalUsers = array();

  $conn = $appbox->get_connection();

  $nbMail = 0;
  $nbchu = 0;
  $my_link = "";

  $usrs = getUsrInfos($usr, array_merge(array_keys($users), array($usr)));

  $me = User_Adapter::getInstance($session->get_usr_id(), $appbox);

  $reading_confirm_to = false;
  if ($accuse == '1')
  {
    $reading_confirm_to = $me->get_email();
  }

  foreach ($users as $oneuser => $rights)
  {
    $new_basket = null;

    try
    {
      $user = User_Adapter::getInstance($oneuser, $appbox);
      $pusher = User_Adapter::getInstance($usr, $appbox);

      $new_basket = basket_adapter::create($appbox, $newBask, $user, '', $pusher);
      $new_basket->set_unread();

      $nbchu++;

      $new_basket->push_list($parmLST, false);

      $finalUsers[] = $user->get_id();

      $canSendHD = sendHdOk($usr, $parmLST);

      foreach ($new_basket->get_elements() as $element)
      {
        $record = $element->get_record();
        if ($rights['canHD'] && in_array($record->get_base_id(), $canSendHD))
          $user->ACL()->grant_hd_on($record, $me, 'push');
        else
          $user->ACL()->grant_preview_on($record, $me, 'push');
      }

      set_time_limit(60);

      $from = trim($me->get_email()) != "" ? $me->get_email() : false;


      $url = $registry->get('GV_ServerName') . 'lightbox/index.php?LOG=' . random::getUrlToken('view', $user->get_id(), null, $new_basket->get_ssel_id());

      if ($me->get_id() == $user->get_id())
        $my_link = $url;

      $name = User_Adapter::getInstance($user->get_id(), $appbox)->get_display_name();

      $params = array(
          'from' => $session->get_usr_id()
          , 'from_email' => $from
          , 'to' => $user->get_id()
          , 'to_email' => $user->get_email()
          , 'to_name' => $name
          , 'url' => $url
          , 'accuse' => $reading_confirm_to
          , 'message' => $mail_content
          , 'ssel_id' => $new_basket->get_ssel_id()
      );


      $evt_mngr = eventsmanager_broker::getInstance($appbox);
      $evt_mngr->trigger('__PUSH_DATAS__', $params);
    }
    catch (Exception $e)
    {

    }
  }

  return array('nbchu' => $nbchu, 'mylink' => $my_link, 'users' => $finalUsers);
}

function pushValidation($usr, $ssel_id, $listUsrs, $time, $mail_content, $accuse)
{
  $appbox = appbox::get_instance();
  $session = $appbox->get_session();
  $registry = $appbox->get_registry();
  $finalUsers = array();

  $my_link = '';

  $me = User_Adapter::getInstance($session->get_usr_id(), $appbox);

  $reading_confirm_to = false;
  if ($accuse == '1')
  {
    $reading_confirm_to = $me->get_email();
  }

  if ($time != 0)
  {
    $expires_obj = new DateTime('+' . (int) $time . ' day' . ((int) $time > 1 ? 's' : ''));
    $expires = $expires_obj;

    if ($time > 1)
      $mail_content .= '<br/><br/><div>' . sprintf(_('Vous avez %d jours pour confirmer votre validation'), $time) . '<div><br/><br/>';
    else
      $mail_content .= '<br/><br/><div>' . _('Vous avez une journee pour confirmer votre validation') . '<div><br/><br/>';
  }
  else
  {
    $expires = null;
  }



  $basket = basket_adapter::getInstance($appbox, $ssel_id, $session->get_usr_id());
  $basket->set_unread();

  foreach ($listUsrs as $oneuser => $rights)
  {
    $user = User_Adapter::getInstance($oneuser, appbox::get_instance());

    if (!$user->get_id())
      continue;

    $from = trim($me->get_email()) != "" ? $me->get_email() : false;

    $message = $mail_content . "<br/>\n<br/>\n";

    $url = $registry->get('GV_ServerName') . 'lightbox/index.php?LOG=' . random::getUrlToken('validate', $user->get_id(), $expires, $ssel_id);

    $name = $user->get_display_name();

    $params = array(
        'from' => $session->get_usr_id()
        , 'from_email' => $from
        , 'to' => $user->get_id()
        , 'to_email' => $user->get_email()
        , 'to_name' => $name
        , 'message' => $mail_content
        , 'url' => $url
        , 'ssel_id' => $ssel_id
        , 'accuse' => $reading_confirm_to
    );

    $evt_mngr = eventsmanager_broker::getInstance($appbox);
    $evt_mngr->trigger('__PUSH_VALIDATION__', $params);

    if ($me->get_id() == $user->get_id())
      $my_link = $url;

    if ($time != 0)
      $message .= '<br/>\n<br/>\n' . sprintf(_('push:: %d jours restent pour finir cette validation'), (int) $time) . "<br/>\n";

    $basket->validation_to_users($user, $rights['canAgree'], $rights['canSeeOther'], $rights['canHD'], $expires);
    $finalUsers[] = $oneuser;
  }

  return array('mylink' => $my_link, 'users' => $finalUsers);
}

?>
