<?php
require_once 'basic_controller.php';
require_once 'statistics_controller.php';
require_once __DIR__ . '/../models/archive_model.php';

class StatisticsController extends BasicController {
    private $model;

    function __construct(\Interop\Container\ContainerInterface $ci)
    {
        parent::__construct($ci);
        $this->model = new ArchiveModel($this->db);
    }

    public function uploadsForRegions($request, $response, $args) {
        $result = $this->model->uploadsForRegions();
        return $this->apiResponse($response, $result);
    }

    public function uploadsForCountries($request, $response, $args) {
        $result = $this->model->uploadsForCountries();
        return $this->apiResponse($response, $result);
    }

    public function uploadsForUsers($request, $response, $args) {
        $result = $this->model->uploadsForUsers();
        return $this->apiResponse($response, $result);
    }

    public function cccForRegions($request, $response, $args) {
        $result = $this->model->cccForRegions();
        return $this->apiResponse($response, $result);
    }

    public function cccForCountries($request, $response, $args) {
        $result = $this->model->cccForCountries();
        return $this->apiResponse($response, $result);
    }
}