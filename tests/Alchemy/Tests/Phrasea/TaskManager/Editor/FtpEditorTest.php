<?php

namespace Alchemy\Tests\Phrasea\TaskManager\Editor;

use Alchemy\Phrasea\TaskManager\Editor\FtpEditor;

class FtpEditorTest extends EditorTestCase
{
    public function provideDataForXMLUpdatesFromForm()
    {
        return array(
            array('<?xml version="1.0" encoding="UTF-8"?>
<tasksettings>
<proxy></proxy><proxyport></proxyport></tasksettings>
', '<?xml version="1.0" encoding="UTF-8"?>
<tasksettings>
</tasksettings>', array()
            ),
            array('<?xml version="1.0" encoding="UTF-8"?>
<tasksettings>
  <proxy>1234</proxy>
  <proxyport>5678</proxyport>
</tasksettings>
', '<?xml version="1.0" encoding="UTF-8"?>
<tasksettings><proxy>12</proxy><proxyport>8</proxyport>
</tasksettings>', array('proxy' => 1234, 'proxyport' => 5678)
            ),
            array('<?xml version="1.0" encoding="UTF-8"?>
<tasksettings>
  <customtag>value</customtag>
  <proxy></proxy>
  <proxyport></proxyport>
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
        return new FtpEditor();
    }
}
