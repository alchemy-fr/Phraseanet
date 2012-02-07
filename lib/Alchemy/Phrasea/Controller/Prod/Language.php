<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Prod;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Alchemy\Phrasea\Helper\Record as RecordHelper;

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Language implements ControllerProviderInterface
{

  public function connect(Application $app)
  {
    $controller = new ControllerCollection();

    $controller->get("/", function(Application $app)
            {
              $registry = $app["Core"]->getRegistry();

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
              $out['create'] = _('boutton::creer');
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
              $out['sureToRemoveList'] = _('Are you sure you want to delete this list ?');
              $out['newListName'] = _('New list name ?');
              $out['listNameCannotBeEmpty'] = _('List name can not be empty');
              $out['FeedBackName'] = _('Name');
              $out['FeedBackMessage'] = _('Message');
              $out['FeedBackNoUsersSelected'] = _('No users selected');

              $Serializer = $app['Core']['Serializer'];

              return new Response(
                              $Serializer->serialize($out, 'json')
                              , 200
                              , array('Content-Type' => 'application/json')
              );
            });

    return $controller;
  }

}
