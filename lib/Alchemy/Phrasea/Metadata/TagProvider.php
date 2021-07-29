<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Metadata;

use PHPExiftool\Driver\TagProvider as ExiftoolTagProvider;

class TagProvider extends ExiftoolTagProvider
{
    public function __construct()
    {
        parent::__construct();

        $this['Phraseanet'] = $this->share(function () {
            return [
                'PdfText'       => new \Alchemy\Phrasea\Metadata\Tag\PdfText(),
                'TfArchivedate' => new \Alchemy\Phrasea\Metadata\Tag\TfArchivedate(),
                'TfAtime'       => new \Alchemy\Phrasea\Metadata\Tag\TfAtime(),
                'TfBasename'    => new \Alchemy\Phrasea\Metadata\Tag\TfBasename(),
                'TfBits'        => new \Alchemy\Phrasea\Metadata\Tag\TfBits(),
                'TfChannels'    => new \Alchemy\Phrasea\Metadata\Tag\TfChannels(),
                'TfCtime'       => new \Alchemy\Phrasea\Metadata\Tag\TfCtime(),
                'TfDirname'     => new \Alchemy\Phrasea\Metadata\Tag\TfDirname(),
                'TfDuration'    => new \Alchemy\Phrasea\Metadata\Tag\TfDuration(),
                'TfEditdate'    => new \Alchemy\Phrasea\Metadata\Tag\TfEditdate(),
                'TfExtension'   => new \Alchemy\Phrasea\Metadata\Tag\TfExtension(),
                'TfFilename'    => new \Alchemy\Phrasea\Metadata\Tag\TfFilename(),
                'TfFilepath'    => new \Alchemy\Phrasea\Metadata\Tag\TfFilepath(),
                'TfHeight'      => new \Alchemy\Phrasea\Metadata\Tag\TfHeight(),
                'TfMimetype'    => new \Alchemy\Phrasea\Metadata\Tag\TfMimetype(),
                'TfMtime'       => new \Alchemy\Phrasea\Metadata\Tag\TfMtime(),
                'TfQuarantine'  => new \Alchemy\Phrasea\Metadata\Tag\TfQuarantine(),
                'TfRecordid'    => new \Alchemy\Phrasea\Metadata\Tag\TfRecordid(),
                'TfSize'        => new \Alchemy\Phrasea\Metadata\Tag\TfSize(),
                'TfWidth'       => new \Alchemy\Phrasea\Metadata\Tag\TfWidth(),
            ];
        });
    }

    public function getAll()
    {
        $all = parent::getAll();
        $all['Phraseanet'] = $this['Phraseanet'];

        return $all;
    }

    public function getLookupTable()
    {
        $table = parent::getLookupTable();

        $table['phraseanet'] = [
            'pdf-text'       => [
                'tagname'   => 'Pdf-Text',
                'classname' => '\\Alchemy\\Phrasea\\Metadata\\Tag\\PdfText',
                'namespace' => 'Phraseanet'],
            'tf-archivedate' => [
                'tagname'   => 'Tf-Archivedate',
                'classname' => '\\Alchemy\\Phrasea\\Metadata\\Tag\\TfArchivedate',
                'namespace' => 'Phraseanet'
            ],
            'tf-atime'       => [
                'tagname'   => 'Tf-Atime',
                'classname' => '\\Alchemy\\Phrasea\\Metadata\\Tag\\TfAtime',
                'namespace' => 'Phraseanet'
            ],
            'tf-basename'    => [
                'tagname'   => 'Tf-Basename',
                'classname' => '\\Alchemy\\Phrasea\\Metadata\\Tag\\TfBasename',
                'namespace' => 'Phraseanet'
            ],
            'tf-bits'        => [
                'tagname'   => 'Tf-Bits',
                'classname' => '\\Alchemy\\Phrasea\\Metadata\\Tag\\TfBits',
                'namespace' => 'Phraseanet'
            ],
            'tf-channels'    => [
                'tagname'   => 'Tf-Channels',
                'classname' => '\\Alchemy\\Phrasea\\Metadata\\Tag\\TfChannels',
                'namespace' => 'Phraseanet'
            ],
            'tf-ctime'      => [
                'tagname'   => 'Tf-Ctime',
                'classname' => '\\Alchemy\\Phrasea\\Metadata\\Tag\\TfCtime',
                'namespace' => 'Phraseanet'
            ],
            'tf-dirname'     => [
                'tagname'   => 'Tf-Dirname',
                'classname' => '\\Alchemy\\Phrasea\\Metadata\\Tag\\TfDirname',
                'namespace' => 'Phraseanet'
            ],
            'tf-duration'    => [
                'tagname'   => 'Tf-Duration',
                'classname' => '\\Alchemy\\Phrasea\\Metadata\\Tag\\TfDuration',
                'namespace' => 'Phraseanet'
            ],
            'tf-editdate'    => [
                'tagname'   => 'Tf-Editdate',
                'classname' => '\\Alchemy\\Phrasea\\Metadata\\Tag\\TfEditdate',
                'namespace' => 'Phraseanet'
            ],
            'tf-extension'   => [
                'tagname'   => 'Tf-Extension',
                'classname' => '\\Alchemy\\Phrasea\\Metadata\\Tag\\TfExtension',
                'namespace' => 'Phraseanet'
            ],
            'tf-filename'    => [
                'tagname'   => 'Tf-Filename',
                'classname' => '\\Alchemy\\Phrasea\\Metadata\\Tag\\TfFilename',
                'namespace' => 'Phraseanet'
            ],
            'tf-filepath'    => [
                'tagname'   => 'Tf-Filepath',
                'classname' => '\\Alchemy\\Phrasea\\Metadata\\Tag\\TfFilepath',
                'namespace' => 'Phraseanet'
            ],
            'tf-height'      => [
                'tagname'   => 'Tf-Height',
                'classname' => '\\Alchemy\\Phrasea\\Metadata\\Tag\\TfHeight',
                'namespace' => 'Phraseanet'
            ],
            'tf-mimetype'    => [
                'tagname'   => 'Tf-Mimetype',
                'classname' => '\\Alchemy\\Phrasea\\Metadata\\Tag\\TfMimetype',
                'namespace' => 'Phraseanet'
            ],
            'tf-mtime'       => [
                'tagname'   => 'Tf-Mtime',
                'classname' => '\\Alchemy\\Phrasea\\Metadata\\Tag\\TfMtime',
                'namespace' => 'Phraseanet'
            ],
            'tf-quarantine'  => [
                'tagname'   => 'Tf-Quarantine',
                'classname' => '\\Alchemy\\Phrasea\\Metadata\\Tag\\TfQuarantine',
                'namespace' => 'Phraseanet'
            ],
            'tf-recordid'    => [
                'tagname'   => 'Tf-Recordid',
                'classname' => '\\Alchemy\\Phrasea\\Metadata\\Tag\\TfRecordid',
                'namespace' => 'Phraseanet'
            ],
            'tf-size'        => [
                'tagname'   => 'Tf-Size',
                'classname' => '\\Alchemy\\Phrasea\\Metadata\\Tag\\TfSize',
                'namespace' => 'Phraseanet'
            ],
            'tf-width'       => [
                'tagname'   => 'Tf-Width',
                'classname' => '\\Alchemy\\Phrasea\\Metadata\\Tag\\TfWidth',
                'namespace' => 'Phraseanet'
            ],
        ];

        return $table;
    }
}
