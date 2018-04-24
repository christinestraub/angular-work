<?php
use Aws\Common\Aws;

require_once __DIR__ . '/../models/basic_model.php';

class BucketModel extends BasicModel {
    private $archivePath;

    function __construct($db, $archivePath)
    {
        parent::__construct($db);
        $this->tableName = 'buckets';
        $this->fields = [];

        $this->archivePath = $archivePath;
    }

    public function uploadFile($archive) {
        $aws = Aws::factory('');

        $s3 = $aws->get('S3');

        $bucket = $archive['uuid'];
        $s3->createBucket(['Bucket' => $bucket]);
        $s3->waitUntil('BucketExists', ['Bucket' => $bucket]);

        $pathToFile = $this->archivePath . $archive['file_name'];
        $result = $s3->putObject([
            'Bucket' => $bucket,
            'Key' => $archive['uuid'],
            'SourceFile' => $pathToFile,
            'MetaData' => array(
                'FileName' => $archive['file_name']
            )
        ]);

        $s3->waitUntil('ObjectExists', [
            'Bucket' => $bucket,
            'Key' => $archive['uuid'],
        ]);
    }

    public function downloadFile($archive) {

    }
}
