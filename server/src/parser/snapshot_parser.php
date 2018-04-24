<?php
require_once 'basic_parser.php';

define ('CODE_VERSION_TAG', '/(?P<prefix>\w+\.\w+\.\w+\.CodeVersion)\.[A|B|C|D]/');

class SnapshotParser extends BasicParser {

    /***
     * legacy parsing function, deprecated
     *
     * @param $filePath
     * @param $tags
     * @return array
     */
    public function parse_deprecated($filePath, $tags) {
        $tagValues = array();
        $tagsCount = count($tags);

        $handle = fopen($filePath, "r");
        if (!isset($handle)) {
            return $tagValues;
        }

        while (($line = fgets($handle)) !== false) {

            $csv = str_getcsv($line);
            if (!isset($csv)) continue;

            foreach ($tags as $tag)  {
                if ($csv[0] == $tag) {
                    array_push($tagValues, array(
                        'tag' => $tag,
                        'val' => $csv[1]
                    ));
                }
            }

            if ($tagsCount == count($tagValues)) {
                break;
            }
        }

        fclose($handle);

        return $tagValues;
    }

    /***
     * parse snapshot.csv
     *
     * @param $filePath
     * @param $tagPatterns
     * @return array
     */
    public function parse($filePath, $tagPatterns) {
        $tagValues = [];
        $codeVersionTags = [];
        $matchedTags = [];

        // convert csv to json and get Tag
        $jsonObj = $this->toJSON($filePath, $tagPatterns, $matchedTags, $codeVersionTags);

        foreach ($matchedTags as $tag)  {
            $node = $this->get_node($jsonObj, $tag);
            if ($node != null) {
                array_push($tagValues, array(
                    'tag' => $tag,
                    'val' => $node
                ));
            }
        }

        foreach ($codeVersionTags as $key) {
            $node = $this->get_node($jsonObj, $key);
            if ($node != null) {
                array_push($tagValues, array(
                    'tag' => $key,
                    'val' => implode('.', $node)
                ));
            }
        }

        return $tagValues;
    }

    /**
     *
     * @param $json_obj
     * @param $key
     * @param $value
     */
    function set_node(&$json_obj, $key, $value) {
        $keys = explode('.', $key);

        $last_key = array_pop($keys);

        while ($arr_key = array_shift($keys)) {
            if (!array_key_exists($arr_key, $json_obj)) {
                $json_obj[$arr_key] = array();
            }
            $json_obj = &$json_obj[$arr_key];
        }

        $json_obj[$last_key] = $value;
    }

    /**
     * find the key in the json obj
     * @param $json_obj
     * @param $key
     * @return null
     */
    function get_node(&$json_obj, $key) {
        $keys = explode('.', $key);

        $last_key = array_pop($keys);

        while ($arr_key = array_shift($keys)) {
            if (!array_key_exists($arr_key, $json_obj)) {
                return null;
            }
            $json_obj = &$json_obj[$arr_key];
        }

        return $json_obj[$last_key];
    }

    /**
     * load snapshot.csv file and convert to JSON array
     *
     * @param $filePath
     * @param $tagPatterns
     * @param $matchedTags
     * @param $codeVersionTags
     * @return array
     */
    public function toJSON($filePath, $tagPatterns, &$matchedTags, &$codeVersionTags) {
        $jsonObj = array();

        $handle = fopen($filePath, "r");
        if (!isset($handle)) {
            return $jsonObj;
        }

        while (($line = fgets($handle)) !== false) {

            // skip remark line
            if (preg_match('(//.*)', $line)) {
                continue;
            }

            // split csv line by comma
            $csv = str_getcsv($line);
            if (!isset($csv)) continue;
            $this->set_node($jsonObj, $csv[0], $csv[1]);

            // evaluate pattern
            foreach ($tagPatterns as $pattern) {
                $pat = '/'.$pattern.'/';
                if (preg_match($pat, $csv[0], $match)) {
                    $matchedTags[] = $csv[0];
                }
            }

            // get version
            $this->findCodeVersionTag($csv[0], $codeVersionTags);
        }

        fclose($handle);

        return $jsonObj;
    }

    /**
     * @param $tag
     * @param $codeVersionTags
     */
    function findCodeVersionTag($tag, &$codeVersionTags) {
        if (preg_match(CODE_VERSION_TAG, $tag, $match)) {
            $prefix = $match['prefix'];
            if (!array_key_exists($prefix, $codeVersionTags)) {
                $codeVersionTags[] = $prefix;
            }
        }
    }
}
