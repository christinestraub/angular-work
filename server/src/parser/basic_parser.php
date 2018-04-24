<?php

define ('ERRLOG_TXT', 'errlog.txt');
define ('SNAPSHOT_CSV', 'snapshot.csv');

abstract class BasicParser {
    protected $targetFile;

    function __construct($targetFile)
    {
        $this->targetFile = $targetFile;
    }

    abstract function parse($filePath, $tags);
}
