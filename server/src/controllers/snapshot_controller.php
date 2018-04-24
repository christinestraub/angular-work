<?php
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

require_once 'basic_controller.php';
require_once 'http_status_codes.php';
require_once __DIR__ . '/../models/snapshot_model.php';
require_once __DIR__ . '/../models/archive_model.php';

class SnapshotController extends BasicController {
    private $model;
    private $archiveModel;

    function __construct(\Interop\Container\ContainerInterface $ci)
    {
        parent::__construct($ci);
        $this->model = new SnapshotModel();
        $this->archiveModel = new ArchiveModel($this->db);
    }

    public function getList(Request $request, Response $response, $args) {
        if ($this->token->role == 'admin') {
            return $this->apiResponse($response, $this->model->getAll());
        } else {
            return $this->apiResponse($response, $this->model->getList($this->token->id));
        }
    }

    public function getAll(Request $request, Response $response, $args) {
        return $this->apiResponse($response, $this->model->getAll());
    }

    public function getPage(Request $request, Response $response, $args)
    {
        $filter = array();

        $query = $request->getQueryParams();
        foreach(['archive', 'path', 'file', 'tag', 'val', 'ccc', 'chassis'] as $f ) {
            if (isset($query[$f]))
                $filter[$f] = new \MongoDB\BSON\Regex($query[$f], 'i');
        }

        $start = (int)$args['start'];
        $limit = (int)$args['limit'];

        $options = ['skip' => $start, 'limit' => $limit ];

        $result = $this->model->find($filter, $options);

        // we add the CCC#, region and country
        // and filter the user
        foreach($result as $item) {
            $archive = $this->archiveModel->find('file_name', $item['archive']);
            if (!$archive) {
                // there are no associated archive with this value
                $item['project_number'] = '';
                $item['region'] = '';
                $item['country'] = '';
                $item['uploaded_on'] = '';
            } else {
                $item['project_number'] = $archive['project_number'];
                $item['region'] = $archive['region'];
                $item['country'] = $archive['country'];
                $item['uploaded_on'] = $archive['uploaded_on'];
            }
        }

        return $this->apiResponse($response, $result);
    }
}
