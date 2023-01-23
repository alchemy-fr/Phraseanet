<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\ControllerProvider\Thesaurus;

use Alchemy\Phrasea\Application as PhraseaApplication;
use Alchemy\Phrasea\Controller\Thesaurus\ThesaurusController;
use Alchemy\Phrasea\ControllerProvider\ControllerProviderTrait;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ServiceProviderInterface;

class Thesaurus implements ControllerProviderInterface, ServiceProviderInterface
{
    use ControllerProviderTrait;

    public function register(Application $app)
    {
        $app['controller.thesaurus'] = $app->share(function (PhraseaApplication $app) {
            return (new ThesaurusController($app))
                ->setDispatcher($app['dispatcher'])
            ;
        });
    }

    public function boot(Application $app)
    {
        // no-op
    }

    public function connect(\Silex\Application $app)
    {
        $controllers = $this->createAuthenticatedCollection($app);
        $firewall = $this->getFirewall($app);

        $controllers->before(function () use ($firewall) {
            $firewall->requireAccessToModule('thesaurus');
        });

        $controllers->match('/', 'controller.thesaurus:indexThesaurus')->bind('thesaurus');
        $controllers->match('accept.php', 'controller.thesaurus:accept');
        $controllers->match('export_text.php', 'controller.thesaurus:exportText');
        $controllers->match('export_text_dlg.php', 'controller.thesaurus:exportTextDialog');
        $controllers->match('export_topics.php', 'controller.thesaurus:exportTopics');
        $controllers->match('export_topics_dlg.php', 'controller.thesaurus:exportTopicsDialog');
        $controllers->match('import.php', 'controller.thesaurus:import');
        $controllers->match('import_dlg.php', 'controller.thesaurus:importDialog');
        $controllers->match('linkfield.php', 'controller.thesaurus:linkFieldStep1');
        $controllers->match('linkfield2.php', 'controller.thesaurus:linkFieldStep2');
        $controllers->match('linkfield3.php', 'controller.thesaurus:linkFieldStep3');
        $controllers->match('loadth.php', 'controller.thesaurus:loadThesaurus')->bind('thesaurus_loadth');
        $controllers->match('newterm.php', 'controller.thesaurus:newTerm');
        $controllers->match('properties.php', 'controller.thesaurus:properties');
        $controllers->match('thesaurus.php', 'controller.thesaurus:thesaurus')->bind('thesaurus_thesaurus');
        $controllers->match('populate', 'controller.thesaurus:populate')->bind('thesaurus_populate');

        $controllers->match('xmlhttp/accept.x.php', 'controller.thesaurus:acceptXml');
        $controllers->match('xmlhttp/acceptcandidates.x.php', 'controller.thesaurus:acceptCandidatesXml');
        $controllers->match('xmlhttp/changesylng.x.php', 'controller.thesaurus:changeSynonymLanguageXml');
        $controllers->match('xmlhttp/changesypos.x.php', 'controller.thesaurus:changeSynonymPositionXml');
        $controllers->match('xmlhttp/delsy.x.php', 'controller.thesaurus:removeSynonymXml');
        $controllers->match('xmlhttp/delts.x.php', 'controller.thesaurus:removeSpecificTermXml');
        $controllers->match('xmlhttp/getsy.x.php', 'controller.thesaurus:getSynonymXml');
        $controllers->match('xmlhttp/getterm.x.php', 'controller.thesaurus:getTermXml');
        $controllers->match('xmlhttp/killterm.x.php', 'controller.thesaurus:killTermXml');
        $controllers->match('xmlhttp/newsy.x.php', 'controller.thesaurus:newSynonymXml');
        $controllers->match('xmlhttp/newts.x.php', 'controller.thesaurus:newSpecificTermXml');
        $controllers->match('xmlhttp/openbranches.x.php', 'controller.thesaurus:openBranchesXml');
        $controllers->match('xmlhttp/openbranches.j.php', 'controller.thesaurus:openBranchesJson');
        $controllers->match('xmlhttp/reject.x.php', 'controller.thesaurus:RejectXml');
        $controllers->match('xmlhttp/searchcandidate.x.php', 'controller.thesaurus:searchCandidateXml');

        return $controllers;
    }
}
