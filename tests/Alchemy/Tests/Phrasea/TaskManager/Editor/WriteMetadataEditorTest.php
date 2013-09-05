<?php

namespace Alchemy\Tests\Phrasea\TaskManager\Editor;

use Alchemy\Phrasea\TaskManager\Editor\WriteMetadataEditor;

class WriteMetadataEditorTest extends EditorTestCase
{
    public function provideDataForXMLUpdatesFromForm()
    {
        return array(
            array('<?xml version="1.0" encoding="UTF-8"?>
<tasksettings>
<cleardoc>0</cleardoc></tasksettings>
', '<?xml version="1.0" encoding="UTF-8"?>
<tasksettings>
</tasksettings>', array()
            ),
            array('<?xml version="1.0" encoding="UTF-8"?>
<tasksettings>
  <cleardoc>1</cleardoc>
</tasksettings>
', '<?xml version="1.0" encoding="UTF-8"?>
<tasksettings>
    <cleardoc>0</cleardoc>
</tasksettings>', array('cleardoc' => 1)
            ),
            array('<?xml version="1.0" encoding="UTF-8"?>
<tasksettings>
  <customtag>value</customtag>
  <cleardoc>0</cleardoc>
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
        return new WriteMetadataEditor();
    }
}
