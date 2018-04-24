<?php
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

require_once 'basic_controller.php';
require_once 'http_status_codes.php';
require_once __DIR__ . '/../models/archive_model.php';

class HistoryController extends BasicController {
    private $model;

    function __construct(\Interop\Container\ContainerInterface $ci)
    {
        parent::__construct($ci);
        $this->model = new ArchiveModel($this->db);
    }

    public function getAll(Request $request, Response $response, $args) {
        $archives = $this->model->getAll();
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

    public function get(Request $request, Response $response, $args) {
        $uuid = $args['uuid'];

        $archive = $this->model->getByUUID($uuid);
        if ($archive == null) {
            return $this->apiResponse($response, SNAPSHOT_PARAM_ERROR, HttpStatusCodes::HTTP_BAD_REQUEST);
        }

        $snapshotModel = new SnapshotModel();

        $snapshot = $snapshotModel->getSnapshot($uuid);

        $archive['snapshot'] = $snapshot;

        return $this->apiResponse($response, $archive);
    }

    public function delete(Request $request, Response $response, $args) {
        $uuid = $args['uuid'];

        $archive = $this->model->getByUUID($uuid);
        if ($archive == null) {
            return $this->apiResponse($response, SNAPSHOT_PARAM_ERROR, HttpStatusCodes::HTTP_BAD_REQUEST);
        }

        $result = $this->model->delete($archive['id']);
        if (!$result) {
            return $this->apiResponse($response, SNAPSHOT_PARAM_ERROR, HttpStatusCodes::HTTP_INTERNAL_SERVER_ERROR);
        }

        // remove the file
        $filePath = $this->settings['archivePath'].$archive['file_name'];
        if (file_exists($filePath)) {
            if (!unlink($filePath)) {
                $this->logger->debug('could not delete the file ' . $filePath);
            };
        }

        return $this->apiResponse($response, $archive);
    }
}
