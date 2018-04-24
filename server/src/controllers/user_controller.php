<?php
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

use Ramsey\Uuid\Uuid;
use Firebase\JWT\JWT;
use Tuupola\Base62;

require_once 'basic_controller.php';
require_once __DIR__ . '/../models/account_model.php';

class UserController extends BasicController {
    private $model;

    function __construct(\Interop\Container\ContainerInterface $ci)
    {
        parent::__construct($ci);

        $this->model = new AccountModel($this->db);
    }

    public function index(Request $request, Response $response, $args) {
        $users = $this->model->getAll();
        return $this->apiResponse($response, $users);
    }
}
