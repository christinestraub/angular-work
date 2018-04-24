<?php

/**
 * background worker to parse the archive files and store it the mongo DB
 */
require  __DIR__ . '/../../../vendor/autoload.php';
require_once 'snapshot_parser.php';
require_once 'errorlog_parser.php';

class ArchiveParser {
    protected $archivePath;
    protected $workPath;
    protected $logger;
    private $parsers;

    function __construct($archivePath, $logger)
    {
        $this->archivePath = $archivePath;
        $this->logger = $logger;

        $this->parsers = [
            SNAPSHOT_CSV => new SnapshotParser(SNAPSHOT_CSV),
            ERRLOG_TXT => new ErrorLogParser(ERRLOG_TXT)
        ];
    }

    function parseArchive($archive, $workPath, $snapFields) {
        $result = array();
        $result['code'] = 'success';

        $archiveName = $this->archivePath . $archive['file_name'];
        if (!file_exists($archiveName)) {
            $result['code'] = 'fail';
            $result['status'] = 'FILE_NOT_EXISTS';
            return $result;
        }

        $za = new ZipArchive();
        if (!$za->open($archiveName)) {
            $result['code'] = 'open failed';
            return $result;
        };

        $fields = array();
        $paths = ['.', 'MPU_A', 'MPU_B'];
        foreach($snapFields as $field) {
            $parser = $this->parsers[$field['file_name']];
            if ($parser == null) {
                // there are no appropriate parser
                // echo 'there are no parser associated with '.$field['file_name'];
                continue;
            }

            foreach($paths as $path) {
                if ($path == '.') {
                    $archiveName = $field['file_name'];
                } else if ($path != '') {
                    $archiveName = $path."/".$field['file_name'];
                } else {
                    continue;
                }

                if (!$za->statName($archiveName)) {
                    continue;
                } else if (!$za->extractTo($workPath, $archiveName)) {
                    continue;
                } else {
                    $fullPath = $workPath.$archiveName;
                    $tags = json_decode($field['fields']);
                    $tagValues = $parser->parse($fullPath, $tags);
                }

                if (count($tagValues)> 0) {
                    array_push($fields, array (
                        'path' => $path,
                        'file' => $field['file_name'],
                        'tags' => $tagValues
                    ));
                } else {
                    array_push($fields, array (
                        'path' => $path,
                        'file' => $field['file_name'],
                        'tags' => [ 'tag' => 'null', 'val' => 'null' ]
                    ));
                }
            }
        }

        $snapshot = array(
            'archive' => $archive['file_name'],
            'archive-id' => $archive['uuid'],
            'created-on' => date('Y-m-d H:i:s'),
            'fields' => $fields
        );

        $za->close();

        $result['snapshot'] = $snapshot;

        return $result;
    }
}
