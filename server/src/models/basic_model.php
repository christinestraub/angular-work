<?php
require_once 'dbconn.php';
require_once __DIR__ . '/../libs/NotORM.php';

class BasicModel {
    protected $conn;
    protected $tableName;
    protected $fields;
    public    $db;

    public function __construct($db = null)
    {
        $this->conn = connect_db();
        $this->db = $db;
    }

    public function __destruct()
    {
        if (isset($this->conn))
            $this->conn->close();
    }

    public static function getRegions() {
        $json_file = __DIR__ . '/countries.json';

        $str = file_get_contents($json_file);
        $countries = json_decode($str, true);

        $continents = $countries['continents'];
        $regions = array();
        $temp = array();

        foreach($continents as $code => $name) {
            $temp[$code] = [ 'name' => $name, 'code' => $code, 'countries' => [] ];
        }

        foreach($countries['countries'] as $code => $country) {
            array_push($temp[$country['continent']]['countries'],
                [ 'name' => $country['name'], 'code' => $code ]);
        }

        foreach($temp as $code => $region) {
            array_push($regions, $region);
        }

        return $regions;
    }

    public function entity($result = null) {
        $item = array();
        if ($result == null) {
            foreach($this->fields as $field) {
                $item[$field] = '';
            }
        } else {
            foreach($this->fields as $field) {
                if (isset($result[$field]))
                    $item[$field] = $result[$field];
            }
        }
        return $item;
    }

    public function getAll() {
        $result = array();
        foreach ($this->db->{$this->tableName}() as $record) {
            $result[] = $this->entity($record);
        }
        return $result;
    }

    public function get($id) {
        return $this->entity($this->db->{$this->tableName}[$id]);
    }

    public function getByUUID($uuid) {
        $result = $this->db->{$this->tableName}->where('uuid', $uuid);
        if ($record = $result->fetch()) {
            return $this->entity($record);
        } else {
            return false;
        }
    }

    public function find($field, $value) {
        $result = $this->db->{$this->tableName}->where($field, $value);
        if ($record = $result->fetch()) {
            return $this->entity($record);
        } else {
            return false;
        }
    }

    public function insert($entity) {
        $result = $this->db->{$this->tableName}->insert($this->entity($entity));
        $result['id'] = $this->db->{$this->tableName}->insert_id();
        return $result;
    }

    public function update($entity) {
        $result = $this->db->{$this->tableName}->where("id", $entity['id']);
        if ($result->fetch()) {
            return $result->update($this->entity($entity));
        } else {
            return false;
        }
    }

    public function delete($id) {
        $result = $this->db->{$this->tableName}->where("id", $id);
        if ($record = $result->fetch()) {
            return $result->delete();
        } else {
            return false;
        }
    }
}