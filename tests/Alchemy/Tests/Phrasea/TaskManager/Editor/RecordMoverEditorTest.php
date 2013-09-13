<?php

namespace Alchemy\Tests\Phrasea\TaskManager\Editor;

use Alchemy\Phrasea\TaskManager\Editor\RecordMoverEditor;
use Symfony\Component\HttpFoundation\Request;

class RecordMoverEditorTest extends EditorTestCase
{
    public function provideDataForXMLUpdatesFromForm()
    {
        return array(
            array('<?xml version="1.0" encoding="UTF-8"?>
<tasksettings>
<logsql>0</logsql></tasksettings>
', '<?xml version="1.0" encoding="UTF-8"?>
<tasksettings>
</tasksettings>', array()
            ),
            array('<?xml version="1.0" encoding="UTF-8"?>
<tasksettings>
  <logsql>1</logsql>
</tasksettings>
', '<?xml version="1.0" encoding="UTF-8"?>
<tasksettings><logsql>0</logsql>
</tasksettings>', array('logsql' => 1)
            ),
            array('<?xml version="1.0" encoding="UTF-8"?>
<tasksettings>
  <customtag>value</customtag>
  <logsql>0</logsql>
</tasksettings>
', '<?xml version="1.0" encoding="UTF-8"?>
<tasksettings>
  <customtag>value</customtag>
</tasksettings>', array()
            ),
        );
    }

    protected function getEditor()
    {
        return new RecordMoverEditor();
    }

    /**
     * @dataProvider provideFacilityActions
     */
    public function testFacilityWithActions($action)
    {
        $databox = null;
        foreach (self::$DI['app']['phraseanet.appbox']->get_databoxes() as $box) {
            $databox = $box;
            break;
        }
        if (null === $databox) {
            $this->markTestSkipped('Unable to get a databox');
        }

        $request = new Request(array(), array(
            'ACT' => $action,
            'xml' => '<?xml version="1.0" encoding="UTF-8"?>
                <tasksettings>
                    <logsql>0</logsql>
                    <tasks>
                        <task active="1" name="confidentiel" action="update" sbas_id="'.$databox->get_sbas_id().'">
                            <from>
                                <date direction="before" field="FIN_COPYRIGHT"/>
                            </from>
                            <to>
                                <status mask="x1xxxx"/>
                            </to>
                        </task>
                    </tasks>
                </tasksettings>',
        ));

        $editor = $this->getEditor();
        $response = $editor->facility(self::$DI['app'], $request);
        $this->assertEquals('application/json', $response->headers->get('content-type'));
    }

    public function provideFacilityActions()
    {
        return array(array('CALCTEST'), array('PLAYTEST'), array('CALCSQL'));
    }
}
