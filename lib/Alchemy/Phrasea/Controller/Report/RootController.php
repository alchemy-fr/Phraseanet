<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Phrasea\Controller\Report;

use Alchemy\Phrasea\Controller\Controller;
use Alchemy\Phrasea\Media\Subdef\Subdef;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class RootController extends Controller
{
    public function indexAction()
    {
        return $this->app->redirectPath('report_dashboard');
    }

    /**
     * Display dashboard information
     *
     * @param  Request $request
     * @return JsonResponse
     */
    public function getDashboard(Request $request)
    {
        if ('json' === $request->getRequestFormat()) {
            \Session_Logger::updateClientInfos($this->app, 4);

            $dashboard = new \module_report_dashboard($this->app, $this->getAuthenticatedUser());

            $dmin = $request->query->get('dmin');
            $dmax = $request->query->get('dmax');

            if ($dmin && $dmax) {
                $dashboard->setDate($dmin, $dmax);
            }

            $dashboard->execute();

            return $this->app->json(['html' => $this->render('report/ajax_dashboard_content_child.html.twig', [
                'dashboard' => $dashboard
            ])]);
        }

        $granted = [];
        $availableSubdefName = [];

        $acl = $this->getAclForUser();
        foreach ($acl->get_granted_base([\ACL::CANREPORT]) as $collection) {
            $sbas_id = $collection->get_sbas_id();
            if (!isset($granted[$sbas_id])) {
                $granted[$sbas_id] = [
                    'id'          => $sbas_id,
                    'name'        => $collection->get_databox()->get_viewname(),
                    'collections' => [],
                    'metas'       => []
                ];

                foreach ($collection->get_databox()->get_meta_structure() as $meta) {
                    // skip the fields that can't be reported
                    if (!$meta->is_report() || ($meta->isBusiness() && !$acl->can_see_business_fields($collection->get_databox()))) {
                        continue;
                    }
                    $granted[$sbas_id]['metas'][] = $meta->get_name();
                }
            }
            $granted[$sbas_id]['collections'][] = [
                'id'      => $collection->get_coll_id(),
                'base_id' => $collection->get_base_id(),
                'name'    => $collection->get_name(),
            ];

            if (!isset($availableSubdefName[$sbas_id])) {
                foreach ($collection->get_databox()->get_subdef_structure() as $subdefGroup) {
                    /** @var \databox_subdef $subdef */
                    foreach ($subdefGroup as $subdef) {
                        $availableSubdefName[$sbas_id][] = $subdef->get_name();
                    }
                }

                $availableSubdefName[$sbas_id] = array_unique($availableSubdefName[$sbas_id]);
            }
        }

        $conf = $this->getConf();

        return $this->render('report/report_layout_child.html.twig', [
            'ajax_dash'     => true,
            'dashboard'     => null,
            'granted_bases' => $granted,
            'availableSubdefName' => $availableSubdefName,
            'home_title'    => $conf->get(['registry', 'general', 'title']),
            'module'        => 'report',
            'module_name'   => 'Report',
            'anonymous'     => $conf->get(['registry', 'modules', 'anonymous-report']),
            'g_anal'        => $conf->get(['registry', 'general', 'analytics']),
            'ajax'          => false,
            'ajax_chart'    => false
        ]);
    }
}
