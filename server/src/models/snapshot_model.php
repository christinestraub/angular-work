<?php

require_once 'basic_model.php';

function iterator_to_array_deep(\Traversable $iterator, $use_keys = true) {
    $array = array();
    foreach ($iterator as $key => $value) {
        if ($value instanceof \Iterator) {
            $value = iterator_to_array_deep($value, $use_keys);
        }
        if ($use_keys) {
            $array[$key] = $value;
        } else {
            $array[] = $value;
        }
    }
    return $array;
}

/**
 * Class SnapshotModel
 *
 * {
 *     '_id':
 *     'report-name':
 *     'parsed-on':
 *     '/snapshot_csv': {
 *          '@GV'
 *      }
 * }
 *
 *
 */
class SnapshotModel {
    private $client;
    public $error = null;

    function __construct()
    {
        $this->client = new \MongoDB\Client("mongodb://localhost:27017");
    }

    function raster($snapshot) {
        $result = array();
        $fields = $snapshot['fields'];
        foreach($fields as $field) {
            $tags = $field['tags'];
            foreach($tags as $tag) {
                $item = array();
                $item['archive'] = $snapshot['archive'];
                $item['archive-id'] = $snapshot['archive-id'];
                $item['ccc'] = $snapshot['ccc'];
                $item['path'] = $field['path'];
                $item['file'] = $field['file'];
                $item['tag'] = $tag['tag'];
                $item['val'] = $tag['val'];
                array_push($result, $item);
            }
        }
        return $result;
    }

    function populate($snapshots) {
        $results = array();
        foreach($snapshots as $snapshot) {
            $fields = $snapshot['fields'];
            foreach($fields as $field) {
                $tags = $field['tags'];
                foreach($tags as $tag) {
                    $item = array();
                    $item['ccc'] = $snapshot['ccc'];
                    $item['archive'] = $snapshot['archive'];
                    $item['archive-id'] = $snapshot['archive-id'];
                    $item['created-on'] = $snapshot['created-on'];
                    $item['path'] = $field['path'];
                    $item['file'] = $field['file'];
                    $item['tag'] = $tag['tag'];
                    $item['val'] = $tag['val'];
                    array_push($results, $item);
                }
            }
        }

        return $results;
    }

    function populate2($snapshots) {
        foreach($snapshots as $snapshot) {
            $fields = $snapshot['fields'];
            $flds = array();
            for ($i = 0; $i < count($fields); $i++) {
                $field = $fields[$i];
                $tags = $field['tags'];
                $t = array();
                for ($j = 0; $j < count($tags); $j++) {
                    array_push($t, [
                        'tag' => $tags[$j]['tag'],
                        'value' => $tags[$j]['val']
                    ]);
                }
                $field['tags'] = $t;
                array_push($flds, $field);
            }
            $snapshot['fields'] = $flds;
        }

        return $snapshots[0];
    }

    public function getAll() {
        $cursor = $this->client->demo->snapshot->find();
        $snapshots = iterator_to_array_deep($cursor);
        return $this->populate($snapshots);
    }

    public function replace($snapshot) {
        try {
            $result = $this->client->demo->snapshot->findOneAndReplace(
                [ 'archive' => $snapshot['archive'] ],
                $snapshot,
                [ 'upsert' => true, 'returnDocument' => MongoDB\Operation\FindOneAndUpdate::RETURN_DOCUMENT_AFTER ]
            );

            // update index collection for searching
            $raster = $this->raster($snapshot);
            foreach($raster as $doc) {
                $a = array();
                foreach (['archive', 'path', 'file', 'tag'] as $f) {
                    array_push($a, $doc[$f]);
                }
                $data = join($a, '/');
                $doc['hash'] = hash('md5', $data, false);
                $result = $this->client->demo->raster->findOneAndReplace(
                    [ 'hash' => $doc['hash'] ],
                    $doc,
                    [ 'upsert' => true, 'returnDocument' => MongoDB\Operation\FindOneAndUpdate::RETURN_DOCUMENT_AFTER ]
                );
            }

            if ($result) {
                return true;
            }
        } catch (\MongoDB\Exception\InvalidArgumentException $e) {
            $this->error = $e;
        }

        return false;
    }

    public function getSnapshot($archiveId) {
        try {
            $collection = $this->client->demo->snapshot;
            $cursor = $collection->find([ 'archive-id' => $archiveId ]);
            $snapshots = iterator_to_array_deep($cursor);
            return $this->populate2($snapshots);
        } catch (\MongoDB\Exception\InvalidArgumentException $e) {
            $this->error = $e;
        }

        return [];
    }

    public function find($filter, $options) {
        try {
            $cursor = $this->client->demo->raster->find($filter, $options);
            // sort data by uploaded_on field
            $snapshots = iterator_to_array_deep($cursor);
            return $snapshots;
        } catch (\MongoDB\Exception\InvalidArgumentException $e) {
            $this->error = $e;
        }

        return [];
    }

    /**
     * @param $archive
     * @return bool
     */
    public function delete($archive) {
        try {
            $result = $this->client->demo->snapshot->deleteMany(
                [ 'archive-id' => $archive['uuid'] ]
            );
            if ($result == null) {
                // there are no document matched
            }
            // remove raster data too
            $result = $this->client->demo->raster->deleteMany(
                [ 'archive-id' => $archive['uuid'] ]
            );
            if ($result == null) {
                // there are no document matched
            }
            return true;
        } catch (\MongoDB\Exception\InvalidArgumentException $e) {
            $this->error = $e;
        }

        return false;
    }
}