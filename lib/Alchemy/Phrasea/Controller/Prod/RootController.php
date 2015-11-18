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

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Controller\Controller;
use Alchemy\Phrasea\Core\Configuration\DisplaySettingService;
use Alchemy\Phrasea\Exception\SessionNotFound;
use Alchemy\Phrasea\Feed\Aggregate;
use Alchemy\Phrasea\Helper;
use Alchemy\Phrasea\Model\Entities\UserSetting;
use Alchemy\Phrasea\Model\Repositories\FeedRepository;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpFoundation\Request;

class RootController extends Controller
{
    use Application\Helper\FirewallAware;

    public function assertAuthenticated(Request $request)
    {
        if (!$this->getAuthenticator()->isAuthenticated() && null !== $request->query->get('nolog')) {
            return $this->app->redirectPath('login_authenticate_as_guest');
        }

        if (null !== $response = $this->getFirewall()->requireAuthentication()) {
            return $response;
        }

        return null;
    }

    public function indexAction(Request $request) {
        try {
            \Session_Logger::updateClientInfos($this->app, 1);
        } catch (SessionNotFound $e) {
            return $this->app->redirectPath('logout');
        }

        $cssPath = $this->app['root.path'] . '/www/assets/prod/skins';

        $css = [];

        $finder = new Finder();
        /** @var SplFileInfo[] $iterator */
        $iterator = $finder
            ->directories()
            ->depth(0)
            ->filter(function (\SplFileInfo $fileinfo) {
                return ctype_xdigit($fileinfo->getBasename());
            })
            ->in($cssPath);

        foreach ($iterator as $dir) {
            $baseName = $dir->getBaseName();
            $css[$baseName] = $baseName;
        }

        $user = $this->getAuthenticatedUser();
        $cssfile = $this->getSettings()->getUserSetting($user, 'css');

        if (!$cssfile && isset($css['000000'])) {
            $cssfile = '000000';
        }

        $feeds = $this->getFeedRepository()->getAllForUser($this->getAclForUser());
        $aggregate = Aggregate::createFromUser($this->app, $user);

        $thjslist = "";

        $queries_topics = '';

        $conf = $this->getConf();
        if ($conf->get(['registry', 'classic', 'render-topics']) == 'popups') {
            $queries_topics = \queries::dropdown_topics($this->app['translator'], $this->app['locale']);
        } elseif ($conf->get(['registry', 'classic', 'render-topics']) == 'tree') {
            $queries_topics = \queries::tree_topics($this->app['locale']);
        }

        $sbas = $bas2sbas = [];

        foreach ($this->getApplicationBox()->get_databoxes() as $databox) {
            $sbas_id = $databox->get_sbas_id();

            $sbas['s' . $sbas_id] = [
                'sbid'   => $sbas_id,
                'seeker' => null,
            ];

            foreach ($databox->get_collections() as $coll) {
                $bas2sbas['b' . $coll->get_base_id()] = [
                    'sbid'  => $sbas_id,
                    'ckobj' => ['checked'    => false],
                    'waschecked' => false,
                ];
            }
        }

        $helper = new Helper\Prod($this->app, $request);

        /** @var \Closure $filter */
        $filter = $this->app['plugin.filter_by_authorization'];

        $plugins = [
            'workzone' => $filter('workzone'),
            'actionbar' => $filter('actionbar'),
        ];

        return $this->render('prod/index.html.twig', [
            'module_name'          => 'Production',
            'WorkZone'             => new Helper\WorkZone($this->app, $request),
            'module_prod'          => $helper,
            'search_datas'         => $helper->get_search_datas(),
            'cssfile'              => $cssfile,
            'module'               => 'prod',
            'events'               => $this->app['events-manager'],
            'GV_defaultQuery_type' => $conf->get(['registry', 'searchengine', 'default-query-type']),
            'GV_multiAndReport'    => $conf->get(['registry', 'modules', 'stories']),
            'GV_thesaurus'         => $conf->get(['registry', 'modules', 'thesaurus']),
            'cgus_agreement'       => \databox_cgu::askAgreement($this->app),
            'css'                  => $css,
            'feeds'                => $feeds,
            'aggregate'            => $aggregate,
            'GV_google_api'        => $conf->get(['registry', 'webservices', 'google-charts-enabled']),
            'queries_topics'       => $queries_topics,
            'search_status'        => \databox_status::getSearchStatus($this->app),
            'queries_history'      => \queries::history($this->app, $user->getId()),
            'thesau_js_list'       => $thjslist,
            'thesau_json_sbas'     => json_encode($sbas),
            'thesau_json_bas2sbas' => json_encode($bas2sbas),
            'thesau_languages'     => $this->app['locales.available'],
            'plugins'              => $plugins,
        ]);
    }
    /**
     * @return DisplaySettingService
     */
    private function getSettings()
    {
        return $this->app['settings'];
    }

    /**
     * @return FeedRepository
     */
    private function getFeedRepository()
    {
        return $this->app['repo.feeds'];
    }
}
