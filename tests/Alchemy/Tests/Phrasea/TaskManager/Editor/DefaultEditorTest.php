<?php

namespace Alchemy\Tests\Phrasea\TaskManager\Editor;

use Alchemy\Phrasea\TaskManager\Editor\DefaultEditor;

class DefaultEditorTest extends EditorTestCase
{
    public function provideDataForXMLUpdatesFromForm()
    {
        return array(
            array('<?xml version="1.0" encoding="UTF-8"?>
<tasksettings>
</tasksettings>
', '<?xml version="1.0" encoding="UTF-8"?>
<tasksettings>
</tasksettings>', array()
            ),
            array('<?xml version="1.0" encoding="UTF-8"?>
<tasksettings>
  <customtag>value</customtag>
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
        return new DefaultEditor();
    }
}
