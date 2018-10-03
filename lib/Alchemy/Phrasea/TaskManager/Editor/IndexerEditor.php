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
use Alchemy\Phrasea\Core\Configuration\PropertyAccess;
class IndexerEditor extends AbstractEditor
{
    /**
     * {@inheritdoc}
     */
    public function getTemplatePath()
    {
        return 'admin/task-manager/task-editor/indexer.html.twig';
    }
    /**
     * {@inheritdoc}
     */
    public function getDefaultPeriod()
    {
        return 10;
    }
    /**
     * {@inheritdoc}
     */
    public function getDefaultSettings(PropertyAccess $config = null)
    {
        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<tasksettings></tasksettings>
XML;
    }
    /**
     * {@inheritdoc}
     */
    protected function getFormProperties()
    {
        return [];
    }
}