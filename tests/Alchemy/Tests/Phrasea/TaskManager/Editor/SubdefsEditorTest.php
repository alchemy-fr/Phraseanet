<?php

namespace Alchemy\Tests\Phrasea\TaskManager\Editor;

use Alchemy\Phrasea\TaskManager\Editor\SubdefsEditor;

class SubdefsEditorTest extends EditorTestCase
{
    public function provideDataForXMLUpdatesFromForm()
    {
        return array(
            array('<?xml version="1.0" encoding="UTF-8"?>
<tasksettings>
<embedded>0</embedded></tasksettings>
', '<?xml version="1.0" encoding="UTF-8"?>
<tasksettings>
</tasksettings>', array()
            ),
            array('<?xml version="1.0" encoding="UTF-8"?>
<tasksettings>
  <embedded>1</embedded>
</tasksettings>
', '<?xml version="1.0" encoding="UTF-8"?>
<tasksettings>
    <embedded>0</embedded>
</tasksettings>', array('embedded' => 1)
            ),
            array('<?xml version="1.0" encoding="UTF-8"?>
<tasksettings>
  <customtag>value</customtag>
  <embedded>0</embedded>
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
        return new SubdefsEditor();
    }
}
