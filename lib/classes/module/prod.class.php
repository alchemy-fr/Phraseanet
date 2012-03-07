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
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class module_prod
{

  public function get_random()
  {
    return md5(time() . mt_rand(100000, 999999));
  }

  public function get_search_datas()
  {
    $search_datas = array(
        'bases' => array(),
        'dates' => array(),
        'fields' => array()
    );

    $bases = $fields = $dates = array();
    $appbox = appbox::get_instance();
    $session = $appbox->get_session();
    $user = User_Adapter::getInstance($session->get_usr_id(), $appbox);
    $searchSet = $user->getPrefs('search');

    foreach ($user->ACL()->get_granted_sbas() as $databox)
    {
      $sbas_id = $databox->get_sbas_id();

      $bases[$sbas_id] = array(
          'thesaurus' => (trim($databox->get_thesaurus()) != ""),
          'cterms' => false,
          'collections' => array(),
          'sbas_id' => $sbas_id
      );

      foreach ($user->ACL()->get_granted_base(array(), array($databox->get_sbas_id())) as $coll)
      {
        $selected = ($searchSet &&
                isset($searchSet->bases) &&
                isset($searchSet->bases->$sbas_id)) ? (in_array($coll->get_base_id(), $searchSet->bases->$sbas_id)) : true;
        $bases[$sbas_id]['collections'][] =
                array(
                    'selected' => $selected,
                    'base_id' => $coll->get_base_id()
        );
      }

      $meta_struct = $databox->get_meta_structure();
      foreach ($meta_struct as $meta)
      {
        if (!$meta->is_indexable())
          continue;
        $id = $meta->get_id();
        $name = $meta->get_name();
        if ($meta->get_type() == 'date')
        {
          if (isset($dates[$name]))
            $dates[$name]['sbas'][] = $sbas_id;
          else
            $dates[$name] = array('sbas' => array($sbas_id), 'fieldname' => $name);
        }

        if (isset($fields[$name]))
        {
          $fields[$name]['sbas'][] = $sbas_id;
        }
        else
        {
          $fields[$name] = array(
              'sbas' => array($sbas_id)
              , 'fieldname' => $name
              , 'type' => $meta->get_type()
              , 'id' => $id
          );
        }
      }

      if (!$bases[$sbas_id]['thesaurus'])
        continue;
      if (!$user->ACL()->has_right_on_sbas($sbas_id, 'bas_modif_th'))
        continue;

      if (simplexml_load_string($databox->get_cterms()))
      {
        $bases[$sbas_id]['cterms'] = true;
      }
    }

    $search_datas['fields'] = $fields;
    $search_datas['dates'] = $dates;
    $search_datas['bases'] = $bases;

    return $search_datas;
  }

  function getLanguage($lng = false)
  {
    $appbox = appbox::get_instance();
    $session = $appbox->get_session();
    $lng = $lng ? $lng : Session_Handler::get_locale();
    $registry = $appbox->get_registry();

    $out = array();
    $out['thesaurusBasesChanged'] = _('prod::recherche: Attention : la liste des bases selectionnees pour la recherche a ete changee.');
    $out['confirmDel'] = _('paniers::Vous etes sur le point de supprimer ce panier. Cette action est irreversible. Souhaitez-vous continuer ?');
    $out['serverError'] = _('phraseanet::erreur: Une erreur est survenue, si ce probleme persiste, contactez le support technique');
    $out['serverName'] = $registry->get('GV_ServerName');
    $out['serverTimeout'] = _('phraseanet::erreur: La connection au serveur Phraseanet semble etre indisponible');
    $out['serverDisconnected'] = _('phraseanet::erreur: Votre session est fermee, veuillez vous re-authentifier');
    $out['hideMessage'] = _('phraseanet::Ne plus afficher ce message');
    $out['confirmGroup'] = _('Supprimer egalement les documents rattaches a ces regroupements');
    $out['confirmDelete'] = _('reponses:: Ces enregistrements vont etre definitivement supprimes et ne pourront etre recuperes. Etes vous sur ?');
    $out['cancel'] = _('boutton::annuler');
    $out['deleteTitle'] = _('boutton::supprimer');
    $out['edit_hetero'] = _('prod::editing valeurs heterogenes, choisir \'remplacer\', \'ajouter\' ou \'annuler\'');
    $out['confirm_abandon'] = _('prod::editing::annulation: abandonner les modification ?');
    $out['loading'] = _('phraseanet::chargement');
    $out['valider'] = _('boutton::valider');
    $out['annuler'] = _('boutton::annuler');
    $out['rechercher'] = _('boutton::rechercher');
    $out['renewRss'] = _('boutton::renouveller');
    $out['candeletesome'] = _('Vous n\'avez pas les droits pour supprimer certains documents');
    $out['candeletedocuments'] = _('Vous n\'avez pas les droits pour supprimer ces documents');
    $out['needTitle'] = _('Vous devez donner un titre');
    $out['newPreset'] = _('Nouveau modele');
    $out['fermer'] = _('boutton::fermer');
    $out['feed_require_fields'] = _('Vous n\'avez pas rempli tous les champ requis');
    $out['feed_require_feed'] = _('Vous n\'avez pas selectionne de fil de publication');
    $out['removeTitle'] = _('panier::Supression d\'un element d\'un reportage');
    $out['confirmRemoveReg'] = _('panier::Attention, vous etes sur le point de supprimer un element du reportage. Merci de confirmer votre action.');
    $out['advsearch_title'] = _('phraseanet::recherche avancee');
    $out['bask_rename'] = _('panier:: renommer le panier');
    $out['reg_wrong_sbas'] = _('panier:: Un reportage ne peux recevoir que des elements provenants de la base ou il est enregistre');
    $out['error'] = _('phraseanet:: Erreur');
    $out['warningDenyCgus'] = _('cgus :: Attention, si vous refuser les CGUs de cette base, vous n\'y aures plus acces');
    $out['cgusRelog'] = _('cgus :: Vous devez vous reauthentifier pour que vos parametres soient pris en compte.');
    $out['editDelMulti'] = _('edit:: Supprimer %s du champ dans les records selectionnes');
    $out['editAddMulti'] = _('edit:: Ajouter %s au champ courrant pour les records selectionnes');
    $out['editDelSimple'] = _('edit:: Supprimer %s du champ courrant');
    $out['editAddSimple'] = _('edit:: Ajouter %s au champ courrant');
    $out['cantDeletePublicOne'] = _('panier:: vous ne pouvez pas supprimer un panier public');
    $out['wrongsbas'] = _('panier:: Un reportage ne peux recevoir que des elements provenants de la base ou il est enregistre');
    $out['max_record_selected'] = _('Vous ne pouvez pas selectionner plus de 400 enregistrements');
    $out['confirmRedirectAuth'] = _('invite:: Redirection vers la zone d\'authentification, cliquez sur OK pour continuer ou annulez');
    $out['error_test_publi'] = _('Erreur : soit les parametres sont incorrects, soit le serveur distant ne repond pas');
    $out['test_publi_ok'] = _('Les parametres sont corrects, le serveur distant est operationnel');
    $out['some_not_published'] = _('Certaines publications n\'ont pu etre effectuees, verifiez vos parametres');
    $out['error_not_published'] = _('Aucune publication effectuee, verifiez vos parametres');
    $out['warning_delete_publi'] = _('Attention, en supprimant ce preregalge, vous ne pourrez plus modifier ou supprimer de publications prealablement effectues avec celui-ci');
    $out['some_required_fields'] = _('edit::certains documents possedent des champs requis non remplis. Merci de les remplir pour valider votre editing');
    $out['nodocselected'] = _('Aucun document selectionne');

    return p4string::jsonencode($out);
  }

}
