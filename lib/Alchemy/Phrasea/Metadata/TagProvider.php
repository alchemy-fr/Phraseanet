<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
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

        $this['Phraseanet'] = $this->share(function(){
            return array(
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
            );
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

        $table['phraseanet'] = array(
            'pdftext'       => array(
                'tagname'   => 'PdfText',
                'classname' => '\\Alchemy\\Phrasea\\Metadata\\Tag\\PdfText',
                'namespace' => 'Phraseanet'),
            'tfarchivedate' => array(
                'tagname'   => 'TfArchivedate',
                'classname' => '\\Alchemy\\Phrasea\\Metadata\\Tag\\TfArchivedate',
                'namespace' => 'Phraseanet'
            ),
            'tfatime'       => array(
                'tagname'   => 'TfAtime',
                'classname' => '\\Alchemy\\Phrasea\\Metadata\\Tag\\TfAtime',
                'namespace' => 'Phraseanet'
            ),
            'tfbasename'    => array(
                'tagname'   => 'TfBasename',
                'classname' => '\\Alchemy\\Phrasea\\Metadata\\Tag\\TfBasename',
                'namespace' => 'Phraseanet'
            ),
            'tfbits'        => array(
                'tagname'   => 'TfBits',
                'classname' => '\\Alchemy\\Phrasea\\Metadata\\Tag\\TfBits',
                'namespace' => 'Phraseanet'
            ),
            'tfchannels'    => array(
                'tagname'   => 'TfChannels',
                'classname' => '\\Alchemy\\Phrasea\\Metadata\\Tag\\TfChannels',
                'namespace' => 'Phraseanet'
            ),
            'tTfCtime'      => array(
                'tagname'   => 'TfCtime',
                'classname' => '\\Alchemy\\Phrasea\\Metadata\\Tag\\TfCtime',
                'namespace' => 'Phraseanet'
            ),
            'tfdirname'     => array(
                'tagname'   => 'TfDirname',
                'classname' => '\\Alchemy\\Phrasea\\Metadata\\Tag\\TfDirname',
                'namespace' => 'Phraseanet'
            ),
            'tfduration'    => array(
                'tagname'   => 'TfDuration',
                'classname' => '\\Alchemy\\Phrasea\\Metadata\\Tag\\TfDuration',
                'namespace' => 'Phraseanet'
            ),
            'tfeditdate'    => array(
                'tagname'   => 'TfEditdate',
                'classname' => '\\Alchemy\\Phrasea\\Metadata\\Tag\\TfEditdate',
                'namespace' => 'Phraseanet'
            ),
            'tfextension'   => array(
                'tagname'   => 'TfExtension',
                'classname' => '\\Alchemy\\Phrasea\\Metadata\\Tag\\TfExtension',
                'namespace' => 'Phraseanet'
            ),
            'tffilename'    => array(
                'tagname'   => 'TfFilename',
                'classname' => '\\Alchemy\\Phrasea\\Metadata\\Tag\\TfFilename',
                'namespace' => 'Phraseanet'
            ),
            'tffilepath'    => array(
                'tagname'   => 'TfFilepath',
                'classname' => '\\Alchemy\\Phrasea\\Metadata\\Tag\\TfFilepath',
                'namespace' => 'Phraseanet'
            ),
            'tfheight'      => array(
                'tagname'   => 'TfHeight',
                'classname' => '\\Alchemy\\Phrasea\\Metadata\\Tag\\TfHeight',
                'namespace' => 'Phraseanet'
            ),
            'tfmimetype'    => array(
                'tagname'   => 'TfMimetype',
                'classname' => '\\Alchemy\\Phrasea\\Metadata\\Tag\\TfMimetype',
                'namespace' => 'Phraseanet'
            ),
            'tfmtime'       => array(
                'tagname'   => 'TfMtime',
                'classname' => '\\Alchemy\\Phrasea\\Metadata\\Tag\\TfMtime',
                'namespace' => 'Phraseanet'
            ),
            'tfquarantine'  => array(
                'tagname'   => 'TfQuarantine',
                'classname' => '\\Alchemy\\Phrasea\\Metadata\\Tag\\TfQuarantine',
                'namespace' => 'Phraseanet'
            ),
            'tfrecordid'    => array(
                'tagname'   => 'TfRecordid',
                'classname' => '\\Alchemy\\Phrasea\\Metadata\\Tag\\TfRecordid',
                'namespace' => 'Phraseanet'
            ),
            'tfsize'        => array(
                'tagname'   => 'TfSize',
                'classname' => '\\Alchemy\\Phrasea\\Metadata\\Tag\\TfSize',
                'namespace' => 'Phraseanet'
            ),
            'tfwidth'       => array(
                'tagname'   => 'TfWidth',
                'classname' => '\\Alchemy\\Phrasea\\Metadata\\Tag\\TfWidth',
                'namespace' => 'Phraseanet'
            ),
        );

        return $table;
    }
}
