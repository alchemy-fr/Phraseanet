<?php

class exiftool
{

    public static function get_fields($filename, $fields)
    {
        $system = system_server::get_platform();
        $registry = registry::get_instance();

        $ret = array();

        if (in_array($system, array('DARWIN', 'LINUX'))) {
            $cmd = __DIR__ . '/../../vendor/phpexiftool/exiftool/exiftool '
                . escapeshellarg($filename) ;
        } else {
            if (chdir($registry->get('GV_RootPath') . 'tmp/')) {
                $cmd = 'start /B /LOW ' . __DIR__ . '/../../vendor/phpexiftool/exiftool/exiftool.exe '
                    . escapeshellarg($filename) . '';
            }
        }
        if ($cmd) {
            $s = @shell_exec($cmd);
            if (trim($s) != '') {
                $lines = explode("\n", $s);

                foreach ($lines as $line) {
                    $cells = explode(':', $line);

                    if (count($cells) < 2)
                        continue;

                    $cell_1 = trim(array_shift($cells));
                    $cell_2 = trim(implode(':', $cells));

                    if (in_array($cell_1, $fields))
                        $ret[$cell_1] = $cell_2;
                }
            }
        }

        foreach ($fields as $field) {
            if ( ! isset($ret[$field]))
                $ret[$field] = false;
        }

        return $ret;
    }
    const EXTRACT_XML_RDF = 0;
    const EXTRACT_TEXT = 1;

    protected static $extracts = array();

    public static function extract_metadatas(system_file $file, $extract_type = null)
    {

        if (isset(self::$extracts[$file->getPathname()]) && isset(self::$extracts[$file->getPathname()][$extract_type])) {
            return self::$extracts[$file->getPathname()][$extract_type];
        }

        $registry = registry::get_instance();
        $system = system_server::get_platform();

        $options = '';
        switch ($extract_type) {
            case self::EXTRACT_TEXT:
            default:

                break;
            case self::EXTRACT_XML_RDF:
                $options .= ' -X -n -fast ';
                break;
        }



        if (in_array($system, array('DARWIN', 'LINUX'))) {
            $cmd = __DIR__ . '/../../vendor/phpexiftool/exiftool/exiftool '
                . $options . escapeshellarg($file->getPathname()) . '';
        } else {
            if (chdir($registry->get('GV_RootPath') . 'tmp/')) {
                $cmd = 'start /B /LOW ' . __DIR__ . '/../../vendor/phpexiftool/exiftool/exiftool.exe'
                    . ' ' . $options . escapeshellarg($file->getPathname()) . '';
            }
        }

        $s = shell_exec($cmd);

        if ($s) {
            self::$extracts[$file->getPathname()][$extract_type] = $s;
        }

        return self::$extracts[$file->getPathname()][$extract_type];
    }

    public static function flush_extracts(system_file $file)
    {
        if (isset(self::$extracts[$file->getPathname()]))
            unset(self::$extracts[$file->getPathname()]);

        return;
    }
}
