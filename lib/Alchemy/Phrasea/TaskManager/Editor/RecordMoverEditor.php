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
        $job = new RecordMoverJob($this->translator);
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
        THIS IS AN EXAMPLE OF A SIMPLE WORKFLOW
        Fix with your settings (fields names, base/collections id's, status-bits) before try
    -->

    <!-- ********* un-comment to see the tasks **********

    <tasks>

        <comment> keep offline (sb4 = 1) all docs before their "go online" date </comment>

        <task active="1" name="stay offline" action="update" sbas_id="1">
            <from>
                <date direction="before" field="GO_ONLINE"/>
            </from>
            <to>
                <status mask="x1xxxx"/>
            </to>
        </task>


        <comment> Put online (sb4 = 0) all docs from 'public' collection and between the online date and the date of archiving </comment>

        <task active="1" name="go online" action="update" sbas_id="1">
            <from>
                <comment> 5, 6, 7 are "public" collections </comment>
                <coll compare="=" id="5,6,7"/>
                <date direction="after" field="GO_ONLINE"/>
                <date direction="before" field="TO_ARCHIVE"/>
            </from>
            <to>
                <status mask="x0xxxx"/>
            </to>
        </task>


        <comment> Warn 10 days before archiving (raise sb5) </comment>

        <task active="1" name="almost the end" action="update" sbas_id="1">
            <from>
                <coll compare="=" id="5,6,7"/>
                <date direction="after" field="TO_ARCHIVE" delta="-10"/>
            </from>
            <to>
                <status mask="1xxxxx"/>
            </to>
        </task>


        <comment> Move to 'archive' collection </comment>

        <task active="1" name="archivage" action="update" sbas_id="1">
            <from>
                <coll compare="=" id="5,6,7"/>
                <date direction="after" field="TO_ARCHIVE" />
            </from>
            <to>
                <comment> reset status of archived documents </comment>
                <status mask="00xxxx"/>
                <comment> 666 is the "archive" collection </comment>
                <coll id="666" />
            </to>
        </task>


        <comment> Delete the archived documents that are in the 'archive' collection from one year </comment>

        <task active="1" name="trash" action="delete" sbas_id="1">
            <from>
                <coll compare="=" id="666"/>
                <date direction="after" field="TO_ARCHIVE" delta="+365" />
            </from>
        </task>
    </tasks>

    ****************************************** -->
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
