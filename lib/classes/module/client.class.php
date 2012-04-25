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
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class module_client
{

    public function getLanguage($lng)
    {
        $registry = registry::get_instance();
        $out = array();
        $out['createWinInvite'] = _('paniers:: Quel nom souhaitez vous donner a votre panier ?');
        $out['chuNameEmpty'] = _('paniers:: Quel nom souhaitez vous donner a votre panier ?');
        $out['noDLok'] = _('export:: aucun document n\'est disponible au telechargement');
        $out['confirmRedirectAuth'] = _('invite:: Redirection vers la zone d\'authentification, cliquez sur OK pour continuer ou annulez');
        $out['serverName'] = $registry->get('GV_ServerName');
        $out['serverError'] = _('phraseanet::erreur: Une erreur est survenue, si ce probleme persiste, contactez le support technique');
        $out['serverTimeout'] = _('phraseanet::erreur: La connection au serveur Phraseanet semble etre indisponible');
        $out['serverDisconnected'] = _('phraseanet::erreur: Votre session est fermee, veuillez vous re-authentifier');
        $out['confirmDelBasket'] = _('paniers::Vous etes sur le point de supprimer ce panier. Cette action est irreversible. Souhaitez-vous continuer ?');
        $out['annuler'] = _('boutton::annuler');
        $out['fermer'] = _('boutton::fermer');
        $out['renewRss'] = _('boutton::renouveller');

        return p4string::jsonencode($out);
    }
}

