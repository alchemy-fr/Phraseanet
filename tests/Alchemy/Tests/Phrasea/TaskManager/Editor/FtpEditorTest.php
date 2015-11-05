<?php

namespace Alchemy\Tests\Phrasea\TaskManager\Editor;

use Alchemy\Phrasea\TaskManager\Editor\FtpEditor;

/**
 * @group functional
 * @group legacy
 */
class FtpEditorTest extends EditorTestCase
{
    public function provideDataForXMLUpdatesFromForm()
    {
        return [
            ['<?xml version="1.0" encoding="UTF-8"?>
<tasksettings>
<proxy></proxy><proxyport></proxyport><proxyuser></proxyuser><proxypwd></proxypwd></tasksettings>
', '<?xml version="1.0" encoding="UTF-8"?>
<tasksettings>
</tasksettings>', []
            ],
            ['<?xml version="1.0" encoding="UTF-8"?>
<tasksettings>
  <proxy>1234</proxy>
  <proxyport>5678</proxyport>
  <proxyuser>user_of_proxy</proxyuser>
  <proxypwd>proxy_pwd</proxypwd>
</tasksettings>
', '<?xml version="1.0" encoding="UTF-8"?>
<tasksettings><proxy>12</proxy><proxyport>8</proxyport>
</tasksettings>', ['proxy' => 1234, 'proxyport' => 5678, 'proxyuser' => 'user_of_proxy', 'proxypwd' => 'proxy_pwd']
            ],
            ['<?xml version="1.0" encoding="UTF-8"?>
<tasksettings>
  <customtag>value</customtag>
  <proxy></proxy>
  <proxyport></proxyport>
  <proxyuser></proxyuser>
  <proxypwd></proxypwd>
</tasksettings>
', '<?xml version="1.0" encoding="UTF-8"?>
<tasksettings>
  <customtag>value</customtag>
</tasksettings>', []
            ],
        ];
    }

    protected function getEditor()
    {
        return new FtpEditor($this->createTranslatorMock());
    }
}
