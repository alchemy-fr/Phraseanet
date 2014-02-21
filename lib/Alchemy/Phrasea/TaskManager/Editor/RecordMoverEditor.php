<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\TaskManager\Editor;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Core\Configuration\PropertyAccess;
use Alchemy\Phrasea\TaskManager\Job\RecordMoverJob;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RecordMoverEditor extends AbstractEditor
{
    /**
     * {@inheritdoc}
     */
    public function getTemplatePath()
    {
        return 'admin/task-manager/task-editor/record-mover.html.twig';
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultPeriod()
    {
        return 900;
    }

    public function facility(Application $app, Request $request)
    {
        $ret = ['tasks' => []];
        $job = new RecordMoverJob(null, null, $this->translator);
        switch ($request->get('ACT')) {
            case 'CALCTEST':
                $sxml = simplexml_load_string($request->get('xml'));
                if (isset($sxml->tasks->task)) {
                    foreach ($sxml->tasks->task as $sxtask) {
                        $ret['tasks'][] = $job->calcSQL($app, $sxtask, false);
                    }
                }
                break;
            case 'PLAYTEST':
                $sxml = simplexml_load_string($request->get('xml'));
                if (isset($sxml->tasks->task)) {
                    foreach ($sxml->tasks->task as $sxtask) {
                        $ret['tasks'][] = $job->calcSQL($app, $sxtask, true);
                    }
                }
                break;
            case 'CALCSQL':
                $sxml = simplexml_load_string($request->get('xml'));
                if (isset($sxml->tasks->task)) {
                    foreach ($sxml->tasks->task as $sxtask) {
                        $ret['tasks'][] = $job->calcSQL($app, $sxtask, false);
                    }
                }
                break;
            default:
                throw new NotFoundHttpException('Route not found.');
        }

        return $app->json($ret);
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultSettings(PropertyAccess $config = null)
    {
        return <<<EOF
<?xml version="1.0" encoding="UTF-8"?>
<tasksettings>
    <logsql>0</logsql>
    <!--
    <tasks>
        //Maintain offline (sb4 = 1) all docs under copyright
        <task active="1" name="confidentiel" action="update" sbas_id="1">
            <from>
                <date direction="before" field="FIN_COPYRIGHT"/>
            </from>
            <to>
                <status mask="x1xxxx"/>
            </to>
        </task>
        //Put online (sb4 = 0) all docs from 'public' collection and between the copyright date and the date of filing
        <task active="1" name="visible" action="update" sbas_id="1">
            <from>
                <coll compare="=" id="5"/>
                <date direction="after" field="FIN_COPYRIGHT"/>
                <date direction="before" field="ARCHIVAGE"/>
            </from>
            <to>
                <status mask="x0xxxx"/>
            </to>
        </task>
        // Warn 10 days before archiving (raise sb5)
        <task active="1" name="bientôt la fin" action="update" sbas_id="1">
            <from>
                <coll compare="=" id="5"/>
                <date direction="after" field="ARCHIVAGE" delta="-10"/>
            </from>
            <to>
                <status mask="1xxxxx"/>
            </to>
        </task>
        //Move to 'archive' collection
        <task active="1" name="archivage" action="update" sbas_id="1">
            <from>
                <coll compare="=" id="5"/>
                <date direction="after" field="ARCHIVAGE" />
            </from>
            <to>
                <status mask="00xxxx"/>   on nettoie les status pour la forme
                <coll id="666" />
            </to>
        </task>
        //Purge the archived documents from one year that are in the 'archive' collection
        <task active="1" name="archivage" action="delete" sbas_id="1">
            <from>
                <coll compare="=" id="666"/>
                <date direction="after" field="ARCHIVAGE" delta="+365" />
            </from>
        </task>
    </tasks>
    -->
</tasksettings>
EOF;
    }

    /**
     * {@inheritdoc}
     */
    protected function getFormProperties()
    {
        return ['logsql' => static::FORM_TYPE_BOOLEAN];
    }
}
