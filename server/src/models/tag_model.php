<?php

require_once 'basic_model.php';

class TagModel extends BasicModel {

    function __construct($db)
    {
        parent::__construct($db);
        $this->tableName = 'tags';
        $this->fields = array(
            'id',
            'file_name',
            'fields'
        );
    }

    public function restore() {
        $json_file = __DIR__ . '/default_tags.json';

        $str = file_get_contents($json_file);
        $data = json_decode($str, true);

        $files = $data['files'];
        foreach($files as $file) {
            $file_name = $file['file_name'];
            $fields = $file['fields'];
            $file['fields'] = json_encode($fields);
            $result = $this->find("file_name", $file_name);
            if ($result) {
                $file['id'] = $result['id'];
                $result = $this->update($file);
            } else {
                $result = $this->insert($file);
            }
            if (!$result) {
                return false;
            }
        }

        return $files;
    }
}
