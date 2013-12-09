<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Prod;

use Silex\Application;
use Silex\ControllerProviderInterface;

class Language implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $app['controller.prod.language'] = $this;

        $controller = $app['controllers_factory'];

        $controller->get("/", function (Application $app) {

            $out = [];
            $out['thesaurusBasesChanged'] = $app->trans('prod::recherche: Attention : la liste des bases selectionnees pour la recherche a ete changee.');
            $out['confirmDel'] = $app->trans('paniers::Vous etes sur le point de supprimer ce panier. Cette action est irreversible. Souhaitez-vous continuer ?');
            $out['serverError'] = $app->trans('phraseanet::erreur: Une erreur est survenue, si ce probleme persiste, contactez le support technique');
            $out['serverName'] = $app['phraseanet.registry']->get('GV_ServerName');
            $out['serverTimeout'] = $app->trans('phraseanet::erreur: La connection au serveur Phraseanet semble etre indisponible');
            $out['serverDisconnected'] = $app->trans('phraseanet::erreur: Votre session est fermee, veuillez vous re-authentifier');
            $out['hideMessage'] = $app->trans('phraseanet::Ne plus afficher ce message');
            $out['confirmGroup'] = $app->trans('Supprimer egalement les documents rattaches a ces regroupements');
            $out['confirmDelete'] = $app->trans('reponses:: Ces enregistrements vont etre definitivement supprimes et ne pourront etre recuperes. Etes vous sur ?');
            $out['cancel'] = $app->trans('boutton::annuler');
            $out['deleteTitle'] = $app->trans('boutton::supprimer');
            $out['deleteRecords'] = $app->trans('Delete records');
            $out['edit_hetero'] = $app->trans('prod::editing valeurs heterogenes, choisir \'remplacer\', \'ajouter\' ou \'annuler\'');
            $out['confirm_abandon'] = $app->trans('prod::editing::annulation: abandonner les modification ?');
            $out['loading'] = $app->trans('phraseanet::chargement');
            $out['valider'] = $app->trans('boutton::valider');
            $out['annuler'] = $app->trans('boutton::annuler');
            $out['create'] = $app->trans('boutton::creer');
            $out['rechercher'] = $app->trans('boutton::rechercher');
            $out['renewRss'] = $app->trans('boutton::renouveller');
            $out['candeletesome'] = $app->trans('Vous n\'avez pas les droits pour supprimer certains documents');
            $out['candeletedocuments'] = $app->trans('Vous n\'avez pas les droits pour supprimer ces documents');
            $out['needTitle'] = $app->trans('Vous devez donner un titre');
            $out['newPreset'] = $app->trans('Nouveau modele');
            $out['fermer'] = $app->trans('boutton::fermer');
            $out['feed_require_fields'] = $app->trans('Vous n\'avez pas rempli tous les champ requis');
            $out['feed_require_feed'] = $app->trans('Vous n\'avez pas selectionne de fil de publication');
            $out['removeTitle'] = $app->trans('panier::Supression d\'un element d\'un reportage');
            $out['confirmRemoveReg'] = $app->trans('panier::Attention, vous etes sur le point de supprimer un element du reportage. Merci de confirmer votre action.');
            $out['advsearch_title'] = $app->trans('phraseanet::recherche avancee');
            $out['bask_rename'] = $app->trans('panier:: renommer le panier');
            $out['reg_wrong_sbas'] = $app->trans('panier:: Un reportage ne peux recevoir que des elements provenants de la base ou il est enregistre');
            $out['error'] = $app->trans('phraseanet:: Erreur');
            $out['warningDenyCgus'] = $app->trans('cgus :: Attention, si vous refuser les CGUs de cette base, vous n\'y aures plus acces');
            $out['cgusRelog'] = $app->trans('cgus :: Vous devez vous reauthentifier pour que vos parametres soient pris en compte.');
            $out['editDelMulti'] = $app->trans('edit:: Supprimer %s du champ dans les records selectionnes');
            $out['editAddMulti'] = $app->trans('edit:: Ajouter %s au champ courrant pour les records selectionnes');
            $out['editDelSimple'] = $app->trans('edit:: Supprimer %s du champ courrant');
            $out['editAddSimple'] = $app->trans('edit:: Ajouter %s au champ courrant');
            $out['cantDeletePublicOne'] = $app->trans('panier:: vous ne pouvez pas supprimer un panier public');
            $out['wrongsbas'] = $app->trans('panier:: Un reportage ne peux recevoir que des elements provenants de la base ou il est enregistre');
            $out['max_record_selected'] = $app->trans('Vous ne pouvez pas selectionner plus de 800 enregistrements');
            $out['confirmRedirectAuth'] = $app->trans('invite:: Redirection vers la zone d\'authentification, cliquez sur OK pour continuer ou annulez');
            $out['error_test_publi'] = $app->trans('Erreur : soit les parametres sont incorrects, soit le serveur distant ne repond pas');
            $out['test_publi_ok'] = $app->trans('Les parametres sont corrects, le serveur distant est operationnel');
            $out['some_not_published'] = $app->trans('Certaines publications n\'ont pu etre effectuees, verifiez vos parametres');
            $out['error_not_published'] = $app->trans('Aucune publication effectuee, verifiez vos parametres');
            $out['warning_delete_publi'] = $app->trans('Attention, en supprimant ce preregalge, vous ne pourrez plus modifier ou supprimer de publications prealablement effectues avec celui-ci');
            $out['some_required_fields'] = $app->trans('edit::certains documents possedent des champs requis non remplis. Merci de les remplir pour valider votre editing');
            $out['nodocselected'] = $app->trans('Aucun document selectionne');
            $out['sureToRemoveList'] = $app->trans('Are you sure you want to delete this list ?');
            $out['newListName'] = $app->trans('New list name ?');
            $out['listNameCannotBeEmpty'] = $app->trans('List name can not be empty');
            $out['FeedBackName'] = $app->trans('Name');
            $out['FeedBackMessage'] = $app->trans('Message');
            $out['FeedBackDuration'] = $app->trans('Time for feedback (days)');
            $out['FeedBackNameMandatory'] = $app->trans('Please provide a name for this selection.');
            $out['send'] = $app->trans('Send');
            $out['Recept'] = $app->trans('Accuse de reception');
            $out['nFieldsChanged'] = $app->trans('%d fields have been updated');
            $out['FeedBackNoUsersSelected'] = $app->trans('No users selected');
            $out['errorFileApi'] = $app->trans('An error occurred reading this file');
            $out['errorFileApiTooBig'] = $app->trans('This file is too big');
            $out['selectOneRecord'] = $app->trans('Please select one record');
            $out['onlyOneRecord'] = $app->trans('You can choose only one record');
            $out['errorAjaxRequest'] = $app->trans('An error occured, please retry');
            $out['fileBeingDownloaded'] = $app->trans('Some files are being downloaded');
            $out['warning'] = $app->trans('Attention');
            $out['browserFeatureSupport'] = $app->trans('This feature is not supported by your browser');
            $out['noActiveBasket'] = $app->trans('No active basket');
            $out['pushUserCanDownload'] = $app->trans('User can download HD');
            $out['feedbackCanContribute'] = $app->trans('User contribute to the feedback');
            $out['feedbackCanSeeOthers'] = $app->trans('User can see others choices');
            $out['forceSendDocument'] = $app->trans('Force sending of the document ?');
            $out['export'] = $app->trans('Export');
            $out['share'] = $app->trans('Share');
            $out['move'] = $app->trans('Move');
            $out['push'] = $app->trans('Push');
            $out['feedback'] = $app->trans('Feedback');
            $out['toolbox'] = $app->trans('Tool box');
            $out['print'] = $app->trans('Print');
            $out['attention'] = $app->trans('Attention !');

            return $app->json($out);
        });

        return $controller;
    }
}
