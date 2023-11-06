<?php

namespace Alchemy\Phrasea\WorkerManager\Worker;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Application\Helper\FilesystemAware;
use Alchemy\Phrasea\Core\Configuration\PropertyAccess;
use Alchemy\Phrasea\Model\Entities\Token;
use Alchemy\Phrasea\Model\Entities\WorkerRunningJob;
use Alchemy\Phrasea\Model\Repositories\TokenRepository;
use Alchemy\Phrasea\Model\Repositories\UserRepository;
use Alchemy\Phrasea\Model\Repositories\WorkerRunningJobRepository;
use Alchemy\Phrasea\WorkerManager\Queue\MessagePublisher;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Pusher\Pusher;

class DownloadAsyncWorker implements WorkerInterface
{
    use Application\Helper\NotifierAware;
    use FilesystemAware;

    private $app;

    /** @var  WorkerRunningJobRepository $repoWorkerJob */
    private $repoWorkerJob;
    /**
     * @var PropertyAccess
     */
    private $conf;

    /** @var Pusher|null */
    private $pusher = null;

    /** @var string  */
    private $pusher_channel_name = "";

    public function __construct(Application $app, PropertyAccess $conf)
    {
        $this->app = $app;
        $this->conf = $conf;
    }

    public function process(array $payload)
    {
        $this->repoWorkerJob = $this->getWorkerRunningJobRepository();
        $em = $this->repoWorkerJob->getEntityManager();
        $em->beginTransaction();
        $this->repoWorkerJob->reconnect();
        $date = new \DateTime();

        $message = [
            'message_type'  => MessagePublisher::DOWNLOAD_ASYNC_TYPE,
            'payload'       => $payload
        ];

        try {
            $workerRunningJob = new WorkerRunningJob();
            $workerRunningJob
                ->setWork(MessagePublisher::DOWNLOAD_ASYNC_TYPE)
                ->setPayload($message)
                ->setPublished($date->setTimestamp($payload['published']))
                ->setStatus(WorkerRunningJob::RUNNING)
            ;

            $em->persist($workerRunningJob);

            $em->flush();

            $em->commit();
        } catch (\Exception $e) {
            $em->rollback();
            $workerRunningJob = null;
        }

        $filesystem = $this->getFilesystem();

        $params = unserialize($payload['params']);

        /** @var UserRepository $userRepository */
        $userRepository = $this->app['repo.users'];

        $user = $userRepository->find($payload['userId']);
        $localeEmitter = $user->getLocale();

        /** @var TokenRepository $tokenRepository */
        $tokenRepository = $this->app['repo.tokens'];

        /** @var Token $token */
        $token = $tokenRepository->findValidToken($payload['tokenValue']);

        if($this->conf->get(['download_async', 'enabled'], false)) {
            $options = array(
                'cluster' => 'eu',
                'useTLS' => true
            );
            try {
                $this->pusher = new Pusher(
                    $this->conf->get(['pusher', 'auth_key'], ''),
                    $this->conf->get(['pusher', 'secret'], ''),
                    $this->conf->get(['pusher', 'app_id'], ''),
                    $options
                );
                $this->pusher_channel_name = $token->getValue();
            }
            catch (\Exception $e) {
                // no-op
            }
        }

        $list = unserialize($token->getData());

        $caption_dir = null;
        $spreadsheet = null;

        if($list['include_report']) {
            if (!$caption_dir) {
                // do this only once
                $caption_dir = $this->app['tmp.caption.path'] . '/' . time() . $payload['userId'] . '/';
                $filesystem->mkdir($caption_dir, 0750);
            }
            $spreadsheet = new Spreadsheet();
        }

        $totalSize = 0;

        $worksheet_ref_by_db = [];

        foreach($list['files'] as $k_file => $v_file) {
            $record = null;
            $databox_id = $v_file['databox_id'];
            $record_id = $v_file['record_id'];

            if($spreadsheet) {
                if(!$record) {
                    $record = $this->app->getApplicationBox()->get_databox($databox_id)->get_record($record_id);
                }
                if(!array_key_exists($databox_id, $worksheet_ref_by_db)) {
                    // Create a new worksheet with db name
                    $tab_name = substr($this->app->getApplicationBox()->get_databox($databox_id)->get_dbname(), 0, 31);
                    $ws = new Worksheet($spreadsheet, $tab_name);
                    $spreadsheet->addSheet($ws);
                    if(count($worksheet_ref_by_db) === 0) {
                        // we just added the first ws, we can delete the "default" one
                        $spreadsheet->removeSheetByIndex(0);
                    }

                    $include_businessfields = false;
                    if ($list['include_businessfields'] && $this->app->getAclForUser($user)->has_right_on_base($record->getBaseId(), \ACL::CANMODIFRECORD)) {
                        $include_businessfields = true;
                    }

                    // add fields names as first row
                    $max_col = $col = 1;

                    $ref = $this->cellRefFromColumnAndRow($col, 1);
                    $ws->setCellValue($ref, "[record_id]");
                    $max_col = $col++;

                    $ref = $this->cellRefFromColumnAndRow($col, 1);
                    $ws->setCellValue($ref, "[file]");
                    $max_col = $col++;

                    $field_columns = [];
                    foreach ($record->getDatabox()->get_meta_structure() as $field) {
                        if($include_businessfields || !$field->isBusiness()) {
                            $field_columns[$field->get_name()] = $col;
                            $ref = $this->cellRefFromColumnAndRow($col, 1);
                            $ws->setCellValue($ref, $field->get_name());
                            $max_col = $col++;
                        }
                    }
                    // freeze the title row
                    $ws->freezePane("A2");

                    $worksheet_ref_by_db[$databox_id] = [
                        'worksheet_index' => $spreadsheet->getIndex($ws),
                        'worksheet' => $ws,
                        'row' => 2,
                        'max_col' => $max_col,
                        'max_row' => 1,
                        'field_columns' => $field_columns,
                    ];
                }

                // add a row for the record
                $ws_ref = &$worksheet_ref_by_db[$databox_id];
                /** @var Worksheet $ws */
                $ws = $ws_ref['worksheet'];

                $ref = $this->cellRefFromColumnAndRow(1, $ws_ref['row']);
                $ws->setCellValue($ref, $record_id);

                $ref = $this->cellRefFromColumnAndRow(2, $ws_ref['row']);
                $ws->setCellValue($ref, $v_file['export_name']);

                $max_lines = 0;
                foreach ($record->get_caption()->get_fields([], $include_businessfields) as $field) {
                    if(array_key_exists($field->get_name(), $ws_ref['field_columns'])) {
                        $col = $ws_ref['field_columns'][$field->get_name()];
                        $value = join($field->get_values(), "\n");
                        $ref = $this->cellRefFromColumnAndRow($col, $ws_ref['row']);
                        $ws->setCellValue($ref, $value);
                        // empiric: max number of "lines" in this row
                        if(($n_lines = substr_count($value, "\n") + 1) > $max_lines) {
                            $max_lines = $n_lines;
                        }
                    }
                }
                // empiric: adjust the "height" of the row (@see https://phpspreadsheet.readthedocs.io/en/latest/topics/recipes/)
                $h = 14.5 * min(100, $max_lines) ;
                $ws->getRowDimension($ws_ref['row'])->setRowHeight($h);

                $ws_ref['max_row'] = $ws_ref['row'];
                $ws_ref['row']++;
            }

            foreach($v_file['subdefs'] as $k_subdef => $v_subdef) {
                if($k_subdef === "document" && $v_subdef['to_stamp']) {
                    // we must stamp this document
                    try {
                        if(!$record) {
                            $record = $this->app->getApplicationBox()->get_databox($v_file['databox_id'])->get_record($v_file['record_id']);
                        }
                        $sd = $record->get_subdef($k_subdef);
                        if(!is_null($path = \recordutils_image::stamp($this->app, $sd))) {
                            // stamped !
                            $pi = pathinfo($path);
                            $list['files'][$k_file]['subdefs'][$k_subdef]['path'] = $pi['dirname'];
                            $list['files'][$k_file]['subdefs'][$k_subdef]['file'] = $pi['basename'];
                            $list['files'][$k_file]['subdefs'][$k_subdef]['size'] = filesize($path);
                        }
                    }
                    catch (\Exception $e) {
                        // failed to stamp ? ignore and send the original file
                    }
                }
                if($list['files'][$k_file]['subdefs'][$k_subdef]['size'] > 0) {
                    $totalSize += $list['files'][$k_file]['subdefs'][$k_subdef]['size'];
                    $this->push(
                        'file_ok',
                        [
                            'message'    => "",
                            'databox_id' => $list['files'][$k_file]['databox_id'],
                            'record_id'  => $list['files'][$k_file]['record_id'],
                            'subdef'     => $k_subdef,
                            'size'       => $list['files'][$k_file]['subdefs'][$k_subdef]['size'],
                            'human_size' => $this->getHumanSize($list['files'][$k_file]['subdefs'][$k_subdef]['size']),
                            'total_size' => $totalSize,
                            'human_total_size' => $this->getHumanSize($totalSize),
                        ]
                    );
                }
            }
        }

        // add the captions files if exist
        foreach ($list['captions'] as $v_caption) {
            if (!$caption_dir) {
                // do this only once
                $caption_dir = $this->app['tmp.caption.path'] . '/' . time() . $payload['userId'] . '/';
                $filesystem->mkdir($caption_dir, 0750);
            }

            $subdefName = $v_caption['subdefName'];
            $kFile = $v_caption['fileId'];

            $download_element = new \record_exportElement(
                $this->app,
                $list['files'][$kFile]['databox_id'],
                $list['files'][$kFile]['record_id'],
                $v_caption['elementDirectory'],
                $v_caption['remain_hd'],
                $user
            );

            $file = $list['files'][$kFile]["export_name"]
                . $list['files'][$kFile]["subdefs"][$subdefName]["ajout"] . '.'
                . $list['files'][$kFile]["subdefs"][$subdefName]["exportExt"];

            $desc = $this->app['serializer.caption']->serialize($download_element->get_caption(), $v_caption['serializeMethod'], $v_caption['businessFields']);
            file_put_contents($caption_dir . $file, $desc);

            $list['files'][$kFile]["subdefs"][$subdefName]["path"] = $caption_dir;
            $list['files'][$kFile]["subdefs"][$subdefName]["file"] = $file;
            $list['files'][$kFile]["subdefs"][$subdefName]["size"] = filesize($caption_dir . $file);
            $list['files'][$kFile]["subdefs"][$subdefName]['businessfields'] = $v_caption['businessFields'];

            $totalSize += $list['files'][$kFile]["subdefs"][$subdefName]["size"];
            $this->push(
                'file_ok',
                [
                    'message' => "",
                    'databox_id' => $list['files'][$kFile]['databox_id'],
                    'record_id' => $list['files'][$kFile]['record_id'],
                    'subdef' => $subdefName,
                    'size' => $list['files'][$kFile]["subdefs"][$subdefName]["size"],
                    'human_size' => $this->getHumanSize($list['files'][$kFile]["subdefs"][$subdefName]["size"]),
                    'total_size' => $totalSize,
                    'human_total_size' => $this->getHumanSize($totalSize),
                ]
            );
        }

        if($spreadsheet) {

            $style_title = [
                'font' => [
                    'bold' => true,
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP
                ],
                'borders' => [
                    'bottom' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    ],
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'color' =>  [
                        'argb' => 'FFA0A0A0',
                    ]
                ],
            ];
            $style_values = [
                'alignment' => [
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP
                ],
            ];

            foreach($worksheet_ref_by_db as $databox_id => &$ws_ref) {
                /** @var Worksheet $ws */
                $ws = $ws_ref['worksheet'];
                $range = "A1:" . $this->cellRefFromColumnAndRow($ws_ref['max_col'], 1);
                $ws->getStyle($range)->applyFromArray($style_title);
                $range = "A2:" . $this->cellRefFromColumnAndRow($ws_ref['max_col'], $ws_ref['max_row']);
                $ws->getStyle($range)->applyFromArray($style_values);
                for($col=1; $col<=$ws_ref['max_col']; $col++) {
                    $range = $this->cellRefFromColumnAndRow($col);  // no row in range = whole column (ex. "A")
                    $ws->getColumnDimension($range)->setAutoSize(true);
                }
            };

            $file = 'report.xlsx';

            $writer = new Xlsx($spreadsheet);
            $writer->save($caption_dir . $file);

            unset($writer);
            unset($spreadsheet);
            $spreadsheet = null;

            $list['files']['report'] = [
                "export_name" => 'report',
                'subdefs' => [
                    'report' => [
                        "ajout"       => '',
                        "exportExt"   => 'xlsx',
                        "label"       => '',
                        "path"        => $caption_dir,
                        "file"        => $file,
                        "to_stamp"    => false,
                        "size"        => filesize($caption_dir . $file),
                        "mime"        => '',
                        "folder"      => ''
                    ]
                ]
            ];

            $totalSize += $list['files']['report']["subdefs"]['report']["size"];
        }

        $this->repoWorkerJob->reconnect();
        //zip documents
        \set_export::build_zip(
            $this->app,
            $token,
            $list,
            $this->app['tmp.download.path'].'/'. $token->getValue() . '.zip'
        );

        if ($workerRunningJob != null) {
            $this->repoWorkerJob->reconnect();
            $workerRunningJob
                ->setStatus(WorkerRunningJob::FINISHED)
                ->setFinished(new \DateTime('now'))
            ;

            $em->persist($workerRunningJob);

            $em->flush();
        }

        sleep(1);

        $this->push('zip_ready', ['message' => ""]);

    }

    private function push(string $event, $data)
    {
        if($this->pusher) {
            $r = $this->pusher->trigger(
                $this->pusher_channel_name,
                $event,
                $data
            );
        }
    }

    // todo : this Ko;Mo;Go code already exists in phraseanet (download)
    private function getHumanSize(int $size)
    {
        $unit = 'octets';
        $units = ['Go', 'Mo', 'Ko'];
        $format = "%d %s";
        while ($size > 1024 && !empty($units)) {
            $unit = array_pop($units);
            $size /= 1024.0;
            $format = "%.02f %s";
        }
        return sprintf($format, $size, $unit);
    }


    /**
     * @return WorkerRunningJobRepository
     */
    private function getWorkerRunningJobRepository()
    {
        return $this->app['repo.worker-running-job'];
    }

    private function cellRefFromColumnAndRow(int $col, int $row = null)
    {
        $r =  Coordinate::stringFromColumnIndex($col);
        if($row !== null) {
            $r .= $row;
        }

        return $r;
    }
}
