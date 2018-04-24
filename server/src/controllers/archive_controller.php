<?php
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

require_once 'basic_controller.php';
require_once 'http_status_codes.php';
require_once __DIR__ . '/../models/archive_model.php';
require_once __DIR__ . '/../models/snapshot_model.php';
require_once __DIR__ . '/../models/tag_model.php';
require_once __DIR__ . '/../parser/archive_parser.php';
require_once __DIR__ . '/../models/bucket_model.php';

define('GV_PROJECT', '@GV.Project');
define('GV_CHASSIS', '@GV.ProjectInfo.Chassis');
define('GV_PROJECT_ID', '@GV.ProjectInfo.Project.Project_ID');

class ArchiveController extends BasicController {
    private $archivePath;
    private $tempPath;
    private $model;

    function __construct(\Interop\Container\ContainerInterface $ci)
    {
        parent::__construct($ci);
        $this->model = new ArchiveModel($this->db);

        $this->archivePath = $this->settings['archivePath'];
        $this->tempPath = $this->settings['tempPath'];
    }

    /**
     * delete record from table and delete the file from disk, tag data also will be removed
     *
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return mixed
     */
    public function deleteRecycled(Request $request, Response $response, $args) {
        $id = $args['id'];
        $archive = $this->model->get($id);
        if ($archive == null) {
            return $this->apiResponse($response, SNAPSHOT_ARCHIVE_NOT_FOUND, HttpStatusCodes::HTTP_BAD_REQUEST);
        }

        $result = $this->model->delete($id);
        if (!$result) {
            return $this->apiResponse($response, SNAPSHOT_DB_ERROR, HttpStatusCodes::HTTP_INTERNAL_SERVER_ERROR);
        }

        // delete the file from disk
        $filePath = $this->archivePath.$archive['file_name'];
        if (file_exists($filePath)) {
            if (!unlink($filePath)) {
                $this->logger->debug('could not delete the file ' . $filePath);
            };
        }

        // delete tag data
        // insert the snapshot to mongodb
        $snapshotModel = new SnapshotModel();

        if (!$snapshotModel->delete($archive)) {
            return $this->apiResponse($response, SNAPSHOT_PARSE_ERROR, HttpStatusCodes::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->apiResponse($response, $archive);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param $args
     */
    public function deleteOld(Request $request, Response $response, $args) {
        $id = $args['id'];
        $month = $args['month'];
        $archive = $this->model->get($id);
        $deleted = [];

        if ($month > 0) {
            $archives = $this->model->getOlder($archive, $month);
        } else {
            $archives = $this->model->getGroups($archive);
        }

        /**
         * The condition for delete should be: delete all files uploaded more than XX months ago AND newer ones do exist
         * (it should not delet the most recent one even if it is XX month old - that part is still not working:
         * it deletes when count is 0/1 and 1/1).
         */
        $recentCount = $this->model->getRecentCount($archive, $month);
        if ($recentCount == 0) {
            // if we haven't anyone in recent list, then should leave latest in olders
            $latest = array_pop($archives);
            if ($latest == null) {
                return $this->apiResponse($response, $deleted);
            }
        }

        foreach($archives as $archive) {
            // delete the file from disk
            $filePath = $this->archivePath.$archive['file_name'];
            if (file_exists($filePath)) {
                if (!unlink($filePath)) {
                    $this->logger->debug('could not delete the file ' . $filePath);
                };
            }

            // delete tag data
            // insert the snapshot to mongodb
            $snapshotModel = new SnapshotModel();

            if (!$snapshotModel->delete($archive)) {
                return $this->apiResponse($response, SNAPSHOT_DB_ERROR, HttpStatusCodes::HTTP_INTERNAL_SERVER_ERROR);
            }

            $result = $this->model->delete($id);
            if (!$result) {
                return $this->apiResponse($response, SNAPSHOT_DB_ERROR, HttpStatusCodes::HTTP_INTERNAL_SERVER_ERROR);
            }

            $deleted[] = $archive;
        }

        return $this->apiResponse($response, $deleted);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return mixed
     */
    public function download(Request $request, Response $response, $args) {
//        $user_agent = $_SERVER['HTTP_USER_AGENT'];
//        $isIE = preg_match('/Trident/', $user_agent) || (strpos($user_agent, 'Trident/7.0; rv:11.0') !== false);

        $uuid = $args['uuid'];

        $archive = $this->model->getByUUID($uuid);
        if ($archive == null) {
            return $this->apiResponse($response, SNAPSHOT_PARAM_ERROR, HttpStatusCodes::HTTP_BAD_REQUEST);
        }

        $fullPath = $this->archivePath.$archive['file_name'];
        if ($fd = fopen ($fullPath, "r")) {
            $file_size = filesize($fullPath);
            $path_parts = pathinfo($fullPath);
            readfile($fullPath);
            fclose ($fd);
            return $response->withHeader('Content-Description', 'File Transfer')
                ->withHeader('Content-Type', 'application/octet-stream')
                ->withHeader('Content-Disposition', 'attachment; filename="'.$path_parts["basename"].'"')
                ->withHeader('x-filename', $path_parts["basename"])
                ->withHeader('Expires', '0')
                ->withHeader('Cache-Control', 'must-revalidate')
                ->withHeader('Pragma', 'public')
                ->withHeader('Content-Length', $file_size);
        }
        return $this->apiResponse($response, SNAPSHOT_ARCHIVE_EXISTS, HttpStatusCodes::HTTP_NOT_FOUND);
    }

    /**
     * @param $snapshot
     * @param $tagName
     * @return string
     */
    static function findTag($snapshot, $tagName)
    {
        $fields = $snapshot['fields'];
        foreach($fields as $field) {
            $tags = $field['tags'];
            foreach($tags as $tag) {
                if ($tag['tag'] == $tagName)
                    return $tag['val'];
            }
        }

        return '';
    }

    public function get(Request $request, Response $response, $args) {
        $uuid = $args['uuid'];

        $archive = $this->model->getByUUID($uuid);
        if ($archive == null) {
            return $this->apiResponse($response, SNAPSHOT_PARAM_ERROR, HttpStatusCodes::HTTP_BAD_REQUEST);
        }

        $archive['snapshot'] = $this->getSnapshot($uuid);

        return $this->apiResponse($response, $archive);
    }

    public function getAll(Request $request, Response $response, $args) {
        $archives = $this->model->getAll();
        return $this->apiResponse($response, $archives);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return mixed
     */
    public function getLatest(Request $request, Response $response, $args) {
        $archives = $this->model->getLatest();
        return $this->apiResponse($response, $archives);
    }

    public function getList(Request $request, Response $response, $args) {
        if ($this->token->role == 'admin') {
            $archives = $this->model->getAll();
        } else {
            $archives = $this->model->getList($this->token->id);
        }
        return $this->apiResponse($response, $archives);
    }

    /**
     * get old archives with CCC#(project_number) and last uploaded time
     * will be sorted with project_id/project_number
     * format: /ap/archives/old?month=3
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return mixed
     */
    public function getOld(Request $request, Response $response, $args) {
        $query = $request->getQueryParams();
        if (!isset($query['month'])) {
            return $this->apiResponse($response, SNAPSHOT_PARAM_ERROR, HttpStatusCodes::HTTP_BAD_REQUEST);
        }

        $month = $query['month'];

        $archives = $this->model->getOld($month);

        return $this->apiResponse($response, $archives);
    }

    function getSnapshot($uuid) {
        $snapshotModel = new SnapshotModel();
        return $snapshotModel->getSnapshot($uuid);
    }

    /**
     * get all recycled records from table
     *
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return mixed
     */
    public function getRecycled(Request $request, Response $response, $args) {
        $archives = $this->model->getRecycled();
        return $this->apiResponse($response, $archives);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return mixed
     */
    public function insert(Request $request, Response $response, $args) {
        $archive = $request->getParsedBody();
        $snapshot = json_decode($archive['snapshot'], true);

        $archive = $this->model->entity($archive);

        $result = $this->model->insert($archive);
        if ($result == null) {
            return $this->apiResponse($response, SNAPSHOT_DB_ERROR, HttpStatusCodes::HTTP_INTERNAL_SERVER_ERROR);
        }

        // insert the snapshot to mongodb
        $snapshotModel = new SnapshotModel();

        $snapshot['ccc'] = $archive['project_number'];

        $snapshotModel->replace($snapshot);

        $archive['id'] = $result['id'];

        return $this->apiResponse($response, $archive);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return mixed
     */
    public function parse(Request $request, Response $response, $args) {
        $uuid = $args['uuid'];

        $archive = $this->model->getByUUID($uuid);
        if ($archive == null) {
            return $this->apiResponse($response, SNAPSHOT_PARAM_ERROR, HttpStatusCodes::HTTP_BAD_REQUEST);
        }

        // parse archive
        $snapshot = [];
        $result = $this->parseArchive($archive);
        if ($result['code'] != 'success') {
            // mark parse flag
            $archive['status'] = $result['status'];
            $archive['snapshot'] = '';
            $archive['parse_flag'] = 2;
        } else {
            $snapshot = $result['snapshot'];

            $archive['project'] = $this->findTag($snapshot, GV_PROJECT);
            $archive['chassis'] = $this->findTag($snapshot, GV_CHASSIS);;
            $archive['project_id'] = $this->findTag($snapshot, GV_PROJECT_ID);
            $archive['parse_flag'] = 0;
        }

        // insert the snapshot to mongodb
        $snapshotModel = new SnapshotModel();

        $snapshot['ccc'] = $archive['project_number'];

        if (!$snapshotModel->replace($snapshot)) {
            return $this->apiResponse($response, SNAPSHOT_PARSE_ERROR, HttpStatusCodes::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->apiResponse($response, $archive);
    }

    /**
     * @param $archive
     * @return array
     */
    function parseArchive($archive) {
        $workPath = $this->tempPath . $archive['uuid'] . "/";
        if (!file_exists($workPath)) {
            mkdir($workPath, 0777, true);
        }

        $tags = (new TagModel($this->db))->getAll();
        $parser = new ArchiveParser($this->archivePath, $this->logger);
        return $parser->parseArchive($archive, $workPath, $tags);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return mixed
     */
    public function previous(Request $request, Response $response, $args) {
        $archive = $request->getParsedBody();
        $previous = $this->model->previous($archive);
        if ($previous['uuid'] != '') {
            $previous['snapshot'] = $this->getSnapshot($previous['uuid']);
        }
        return $this->apiResponse($response, $previous);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return mixed
     */
    public function regions(Request $request, Response $response, $args) {
        return $this->apiResponse($response, BasicModel::getRegions());
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return mixed
     */
    public function putRecycled(Request $request, Response $response, $args) {
        $archive = $request->getParsedBody();
        $archive = $this->model->putRecycled($archive['uuid']);
        if (!$archive) {
            return $this->apiResponse($response, SNAPSHOT_PARSE_ERROR, HttpStatusCodes::HTTP_INTERNAL_SERVER_ERROR);
        }
        return $this->apiResponse($response, $archive);
    }

    /**
     * mark the record as deleted and keep file on the disk
     *
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return mixed
     */
    public function remove(Request $request, Response $response, $args) {
        $uuid = $args['uuid'];

        $archive = $this->model->getByUUID($uuid);
        if ($archive == null) {
            return $this->apiResponse($response, SNAPSHOT_PARAM_ERROR, HttpStatusCodes::HTTP_BAD_REQUEST);
        }

        $result = $this->model->remove($archive['id']);
        if (!$result) {
            return $this->apiResponse($response, SNAPSHOT_PARAM_ERROR, HttpStatusCodes::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->apiResponse($response, $archive);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return mixed
     */
    public function restoreRecycled(Request $request, Response $response, $args) {
        $archive = $request->getParsedBody();
        $archive = $this->model->restoreRecycled($archive['uuid']);
        if ($archive == null) {
            return $this->apiResponse($response, SNAPSHOT_PARAM_ERROR, HttpStatusCodes::HTTP_BAD_REQUEST);
        }

        return $this->apiResponse($response, $archive);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return mixed
     */
    public function update(Request $request, Response $response, $args) {
        $archive = $request->getParsedBody();

        if (!$this->model->update($archive)) {
            return $this->apiResponse($response, SNAPSHOT_UPDATE_FAIL, HttpStatusCodes::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->apiResponse($response, $archive);
    }

    public function upload(Request $request, Response $response, $args) {
        $files = $request->getUploadedFiles();
        $file = $files['file'];
        $file_name = $file->getClientFileName();

        // TODO: check if the file with same name already uploaded.
        $result = $this->model->find('file_name', $file_name);
        if ($result) {
            return $this->apiResponse($response, SNAPSHOT_ARCHIVE_EXISTS, HttpStatusCodes::HTTP_BAD_REQUEST);
        }

        if ($file->getError() === UPLOAD_ERR_OK) {
            $target_name = $this->archivePath.$file_name;
            $file->moveTo($target_name);
        } else {
            return $this->apiResponse($response,
                SNAPSHOT_UPLOAD_ERROR ." ".$file->getError(),
                HttpStatusCodes::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        // create archive instance
        $archive = $this->model->entity();
        $archive['user_id'] = $this->user['id'];
        $archive['user_uuid'] = $this->user['uuid'];
        $archive['user_name'] = $this->user['username'];
        $archive['file_name'] = $file->getClientFileName();
        $archive['uuid'] = uniqid();
        $archive['uploaded_on'] = gmdate('Y-m-d H:i:s');

        // parse archive
        $snapshot = [];
        $result = $this->parseArchive($archive);
        if ($result['code'] != 'success') {
            // mark parse flag
            $archive['status'] = $result['status'];
            $archive['snapshot'] = '';
            $archive['parse_flag'] = 2;
        } else {
            $snapshot = $result['snapshot'];

            // $snapshotModel->replace($snapshot);
            $archive['project'] = $this->findTag($snapshot, GV_PROJECT);
            $archive['chassis'] = $this->findTag($snapshot, GV_CHASSIS);;
            $archive['project_id'] = $this->findTag($snapshot, GV_PROJECT_ID);
            $archive['parse_flag'] = 0;
        }

        // check project
        $oldArchive = $this->model->findProject($archive['project'], $archive['chassis'], $archive['project_id']);
        if (!$oldArchive) {
            $archive['status'] = ARCHIVE_NO_CCC;
        } else {
            $archive['status'] = ARCHIVE_CCC;
            $archive['project_number'] = $oldArchive['project_number'];
            $archive['region'] = $oldArchive['region'];
            $archive['country'] = $oldArchive['country'];
            $snapshot['ccc'] = $archive['project_number'];
        }

        $archive['snapshot'] = json_encode($snapshot);
        return $this->apiResponse($response, $archive);
    }

    function uploadToS3($archive) {
        /*
        // upload the archive to AWS S3
        $bucketModel = new BucketModel($this->db, $this->archivePath);
        if ($bucketModel->uploadFile($archive)) {

        } else {

        }
        */
    }
}
