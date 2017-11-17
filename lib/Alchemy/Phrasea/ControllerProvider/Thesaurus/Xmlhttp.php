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
use Alchemy\Phrasea\Controller\Thesaurus\ThesaurusXmlHttpController;use Alchemy\Phrasea\ControllerProvider\ControllerProviderTrait;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ServiceProviderInterface;

class Xmlhttp implements ControllerProviderInterface, ServiceProviderInterface
{
    use ControllerProviderTrait;

    public function register(Application $app)
    {
        $app['controller.thesaurus.xmlhttp'] = $app->share(function (PhraseaApplication $app) {
            return (new ThesaurusXmlHttpController($app));
        });
    }

    public function boot(Application $app)
    {
        // no-op
    }

    public function connect(Application $app)
    {
        $controllers = $this->createAuthenticatedCollection($app);
        $firewall = $this->getFirewall($app);

        $requireAccessToThesaurus = function () use ($firewall) {
            $firewall->requireAccessToModule('thesaurus');
        };

        $controllers->match('acceptcandidates.j.php', 'controller.thesaurus.xmlhttp:acceptCandidatesJson')
            ->before($requireAccessToThesaurus);
        $controllers->match('checkcandidatetarget.j.php', 'controller.thesaurus.xmlhttp:checkCandidateTargetJson')
            ->before($requireAccessToThesaurus);
        $controllers->match('getsy_prod.x.php', 'controller.thesaurus.xmlhttp:getSynonymsXml');
        $controllers->match('getterm_prod.h.php', 'controller.thesaurus.xmlhttp:getTermHtml');
        $controllers->match('getterm_prod.x.php', 'controller.thesaurus.xmlhttp:getTermXml');
        $controllers->match('openbranch_prod.j.php', 'controller.thesaurus.xmlhttp:openBranchJson');
        $controllers->match('openbranches_prod.h.php', 'controller.thesaurus.xmlhttp:openBranchesHtml');
        $controllers->match('openbranches_prod.x.php', 'controller.thesaurus.xmlhttp:openBranchesXml');
        $controllers->match('openbranches_prod.j.php', 'controller.thesaurus.xmlhttp:openBranchesJson');
        $controllers->match('replacecandidate.j.php', 'controller.thesaurus.xmlhttp:replaceCandidateJson')
            ->before($requireAccessToThesaurus);
        $controllers->match('search_term_prod.j.php', 'controller.thesaurus.xmlhttp:searchTermJson');

        return $controllers;
    }
}
