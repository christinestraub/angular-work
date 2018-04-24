<?php

require_once __DIR__ . '/../models/tag_model.php';
require_once __DIR__ . '/../models/archive_model.php';
require_once __DIR__ . '/../models/snapshot_model.php';
require_once __DIR__ . '/archive_parser.php';

$settings = require __DIR__ . '/../settings.php';

class Parser
{
    function __construct($settings)
    {
        $this->settings = $settings;
    }

    public function run()
    {
        $client = new \MongoDB\Client("mongodb://localhost:27017");

        $archivePath = $this->settings['archivePath'];
        $tempPath = $this->settings['tempPath'];

        $logger_opt = $this->settings['logger'];

        $logger = new Monolog\Logger($logger_opt['name']);
        $logger->pushProcessor(new Monolog\Processor\UidProcessor());
        $logger->pushHandler(new Monolog\Handler\StreamHandler($logger_opt['path'], $logger_opt['level']));

        $tm = new TagModel();
        $jm = new JobModel();
        $am = new ArchiveModel();
        $sm = new SnapshotModel();
        $parser = new ArchiveParser($archivePath, $logger);

        // create parse job
        try {
            $job = array(
                'job_id' => uniqid(),
                'created_on' => date('Y-m-d H:i:s'),
                'status' => '',
                'message' => ''
            );
            $job = $jm->insert($job);
        } catch (mysqli_sql_exception $e) {
            $logger->debug($e->getMessage());
            return -1;
        }

        $snapFields = $tm->getAll();
        if ($snapFields == null) {
            return -1;
        }

        $archives = $am->getAll();
        if ($archives == null) {
            return -1;
        }

        foreach ($archives as $archive) {
            $workPath = $tempPath . $archive['archive_id'] . "/";
            if (!file_exists($workPath)) {
                mkdir($workPath, 0777, true);
            }

            $result = $parser->parseArchive($archive, $workPath, $snapFields);
            if ($result['code'] != 'success') {
                // mark parse flag
                $archive['status'] = $result['status'];
                $archive['parse_flag'] = 2;
                try {
                    $am->update($archive);
                } catch (mysqli_sql_exception $e) {
                    $logger->error($e->getMessage());
                    echo $e->getMessage() . "\n";
                }
            } else {
                $snapshot = $result['snapshot'];
                $sm->replace($snapshot);
            }

            // remove directory
            // unlink($workPath);
        }

        $job = $jm->update($job);

        return 0;
    }
}

$parser = new Parser($settings['settings']);
$result = $parser->run();
