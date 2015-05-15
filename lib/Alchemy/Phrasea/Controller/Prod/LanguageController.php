<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Phrasea\Controller\Prod;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Translation\TranslatorInterface;

class LanguageController
{
    /** @var TranslatorInterface */
    private $translator;
    private $serverName;
    
    public function __construct(TranslatorInterface $translator, $serverName)
    {
        $this->translator = $translator;
        $this->serverName = $serverName;
    }
    
    public function getTranslationsAction()
    {
        $translatableKeys = [
            'thesaurusBasesChanged'   => 'prod::recherche: Attention : la liste des bases selectionnees pour la recherche a ete changee.',
            'confirmDel'              => 'paniers::Vous etes sur le point de supprimer ce panier. Cette action est irreversible. Souhaitez-vous continuer ?',
            'serverError'             => 'phraseanet::erreur: Une erreur est survenue, si ce probleme persiste, contactez le support technique',
            'serverTimeout'           => 'phraseanet::erreur: La connection au serveur Phraseanet semble etre indisponible',
            'serverDisconnected'      => 'phraseanet::erreur: Votre session est fermee, veuillez vous re-authentifier',
            'hideMessage'             => 'phraseanet::Ne plus afficher ce message',
            'confirmGroup'            => 'Supprimer egalement les documents rattaches a ces regroupements',
            'confirmDelete'           => 'reponses:: Ces enregistrements vont etre definitivement supprimes et ne pourront etre recuperes. Etes vous sur ?',
            'cancel'                  => 'boutton::annuler',
            'deleteTitle'             => 'boutton::supprimer',
            'deleteRecords'           => 'Delete records',
            'edit_hetero'             => 'prod::editing valeurs heterogenes, choisir \'remplacer\', \'ajouter\' ou \'annuler\'',
            'confirm_abandon'         => 'prod::editing::annulation: abandonner les modification ?',
            'loading'                 => 'phraseanet::chargement',
            'valider'                 => 'boutton::valider',
            'annuler'                 => 'boutton::annuler',
            'create'                  => 'boutton::creer',
            'rechercher'              => 'boutton::rechercher',
            'renewRss'                => 'boutton::renouveller',
            'candeletesome'           => 'Vous n\'avez pas les droits pour supprimer certains documents',
            'candeletedocuments'      => 'Vous n\'avez pas les droits pour supprimer ces documents',
            'needTitle'               => 'Vous devez donner un titre',
            'newPreset'               => 'Nouveau modele',
            'fermer'                  => 'boutton::fermer',
            'feed_require_fields'     => 'Vous n\'avez pas rempli tous les champ requis',
            'feed_require_feed'       => 'Vous n\'avez pas selectionne de fil de publication',
            'removeTitle'             => 'panier::Supression d\'un element d\'un reportage',
            'confirmRemoveReg'        => 'panier::Attention, vous etes sur le point de supprimer un element du reportage. Merci de confirmer votre action.',
            'advsearch_title'         => 'phraseanet::recherche avancee',
            'bask_rename'             => 'panier:: renommer le panier',
            'reg_wrong_sbas'          => 'panier:: Un reportage ne peux recevoir que des elements provenants de la base ou il est enregistre',
            'error'                   => 'phraseanet:: Erreur',
            'warningDenyCgus'         => 'cgus :: Attention, si vous refuser les CGUs de cette base, vous n\'y aures plus acces',
            'cgusRelog'               => 'cgus :: Vous devez vous reauthentifier pour que vos parametres soient pris en compte.',
            'editDelMulti'            => 'edit:: Supprimer %s du champ dans les records selectionnes',
            'editAddMulti'            => 'edit:: Ajouter %s au champ courrant pour les records selectionnes',
            'editDelSimple'           => 'edit:: Supprimer %s du champ courrant',
            'editAddSimple'           => 'edit:: Ajouter %s au champ courrant',
            'cantDeletePublicOne'     => 'panier:: vous ne pouvez pas supprimer un panier public',
            'wrongsbas'               => 'panier:: Un reportage ne peux recevoir que des elements provenants de la base ou il est enregistre',
            'max_record_selected'     => 'Vous ne pouvez pas selectionner plus de 800 enregistrements',
            'confirmRedirectAuth'     => 'invite:: Redirection vers la zone d\'authentification, cliquez sur OK pour continuer ou annulez',
            'error_test_publi'        => 'Erreur : soit les parametres sont incorrects, soit le serveur distant ne repond pas',
            'test_publi_ok'           => 'Les parametres sont corrects, le serveur distant est operationnel',
            'some_not_published'      => 'Certaines publications n\'ont pu etre effectuees, verifiez vos parametres',
            'error_not_published'     => 'Aucune publication effectuee, verifiez vos parametres',
            'warning_delete_publi'    => 'Attention, en supprimant ce preregalge, vous ne pourrez plus modifier ou supprimer de publications prealablement effectues avec celui-ci',
            'some_required_fields'    => 'edit::certains documents possedent des champs requis non remplis. Merci de les remplir pour valider votre editing',
            'nodocselected'           => 'Aucun document selectionne',
            'sureToRemoveList'        => 'Are you sure you want to delete this list ?',
            'newListName'             => 'New list name ?',
            'listNameCannotBeEmpty'   => 'List name can not be empty',
            'FeedBackName'            => 'Name',
            'FeedBackMessage'         => 'Message',
            'FeedBackDuration'        => 'Time for feedback (days)',
            'FeedBackNameMandatory'   => 'Please provide a name for this selection.',
            'send'                    => 'Send',
            'Recept'                  => 'Accuse de reception',
            'nFieldsChanged'          => '%d fields have been updated',
            'FeedBackNoUsersSelected' => 'No users selected',
            'errorFileApi'            => 'An error occurred reading this file',
            'errorFileApiTooBig'      => 'This file is too big',
            'selectOneRecord'         => 'Please select one record',
            'onlyOneRecord'           => 'You can choose only one record',
            'errorAjaxRequest'        => 'An error occured, please retry',
            'fileBeingDownloaded'     => 'Some files are being downloaded',
            'warning'                 => 'Attention',
            'browserFeatureSupport'   => 'This feature is not supported by your browser',
            'noActiveBasket'          => 'No active basket',
            'pushUserCanDownload'     => 'User can download HD',
            'feedbackCanContribute'   => 'User contribute to the feedback',
            'feedbackCanSeeOthers'    => 'User can see others choices',
            'forceSendDocument'       => 'Force sending of the document ?',
            'export'                  => 'Export',
            'share'                   => 'Share',
            'move'                    => 'Move',
            'push'                    => 'Push',
            'feedback'                => 'Feedback',
            'toolbox'                 => 'Tool box',
            'print'                   => 'Print',
            'attention'               => 'Attention !',
        ];

        $out = array_merge(
            ['serverName' => $this->serverName],
            array_map(function ($key) {
                return $this->translator->trans($key);
            }, $translatableKeys)
        );

        return new JsonResponse($out);
    }
}
