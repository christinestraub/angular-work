<?php
require_once 'basic_parser.php';

class ErrorLogParser extends BasicParser {

    public function parse($filePath, $tags) {
        $tagValues = array();

        $jsonObj = $this->toJSON($filePath);
        foreach ($jsonObj as $key => $value)  {
            array_push($tagValues, array(
                'tag' => $key,
                'val' => sprintf('%s - {Rev}[%s] - SN: %s', $key, $value['Rev'], $value['SN'])
            ));
        }

        return $tagValues;
    }

    public function toJSON($filePath) {
        $tagValues = array();

        $handle = fopen($filePath, "r");
        if (!$handle) {
            return $tagValues;
        }

        while (($line = fgets($handle)) !== false) {

            if (preg_match('/(IOC bus [\d] slot [\d])/', $line, $ioc_bus, PREG_OFFSET_CAPTURE)) {
                $param_array = array();
                $line = fgets($handle);
                if (preg_match_all('/(?P<param>(\{([\w\-]+)\}\[([\w\.\s\-]+)\]\:))/', $line, $matches,
                    PREG_PATTERN_ORDER)) {
                    foreach ($matches['param'] as $param) {
                        if (preg_match('/\{(?P<tag>[\w\-]+)\}\[(?P<value>[\w\.\s\-]+)\]\:/', $param, $match)) {
                            $param_array[$match['tag']] = $match['value'];
                        }
                    }
                    $tagValues[$ioc_bus[0][0]] = $param_array;
                }
            }
        }

        fclose($handle);

        return $tagValues;
    }
}

