<?php
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

use Ramsey\Uuid\Uuid;
use Firebase\JWT\JWT;
use Tuupola\Base62;

require_once 'basic_controller.php';
require_once __DIR__ . '/../models/account_model.php';

class AuthController extends BasicController {
    private $model;
    private $ldap_host;

    function __construct(\Interop\Container\ContainerInterface $ci)
    {
        parent::__construct($ci);

        $this->model = new AccountModel($this->db);

        $this->ldap_host = $this->settings['LDAP_HOST'];
    }

    function createJWT($user) {
/*
        $valid_scopes = [
            "todo.create",
            "todo.read",
            "todo.update",
            "todo.delete",
            "todo.list",
            "todo.all"
        ];

        $scopes = array_filter($requested_scopes, function ($needle) use ($valid_scopes) {
            return in_array($needle, $valid_scopes);
        });
*/
        $scopes = [
            "todo.create",
            "todo.read",
            "todo.update",
            "todo.delete",
            "todo.list",
            "todo.all"
        ];

        $now = new DateTime();
        $future = new DateTime("now +2 hours");
        $jti = Base62::encode(random_bytes(16));
        $payload = [
            "iat" => $now->getTimeStamp(),
            "exp" => $future->getTimeStamp(),
            "jti" => $jti,
            "id" => $user['id'],
            "name" => $user['username'],
            "uuid" => $user['uuid'],
            "role" => $user['role'],
            "scope" => $scopes,
        ];

        $secret = getenv("JWT_SECRET");
        $token = JWT::encode($payload, $secret, "HS256");

        return $token;
    }

    public function basic(Request $request, Response $response, $args) {
//        $email = $_SERVER['PHP_AUTH_USER'];
//        $password = $_SERVER['PHP_AUTH_PW'];
    }

    public function local(Request $request, Response $response, $args) {
        $credential = $request->getParsedBody();
        $email = $credential['email'];
        $password = $credential['password'];

        if($user = $this->model->authenticate($email, $password)) {
            $token = $this->createJWT($user);
            $data["status"] = "ok";
            $data["token"] = $token;

            return $response->withStatus(201)
                ->withHeader("Content-Type", "application/json")
                ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        } else {
            return $this->apiResponse($response,
                [ 'message' => 'Something went wrong, please try again'],
                HttpStatusCodes::HTTP_NOT_FOUND);
        }
    }

    public function ldap(Request $request, Response $response, $args) {
        $credential = $request->getParsedBody();
        $username = $credential['email'];
        $password = $credential['password'];

        $ldap = ldap_connect($this->ldap_host);
        if ($bind = ldap_bind($ldap, $username, $password)) {
            $user = $this->model->find('username', $username);
            if (!$user) {
                // ii we have not any user associated with the LDAP credential,
                // we can add new user
                $role = 'user';
                $user = $this->model->addLDAPUser($username, $password, $role);
            }

            $token = $this->createJWT($user);
            $data["status"] = "ok";
            $data["token"] = $token;

            return $response->withStatus(201)
                ->withHeader("Content-Type", "application/json")
                ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        } else {
            return $this->apiResponse($response,
                [ 'message' => 'Something went wrong, please try again'],
                HttpStatusCodes::HTTP_NOT_FOUND);
            return $this->apiResponse($response, '', HttpStatusCodes::HTTP_UNAUTHORIZED);
        }
    }

    public function me(Request $request, Response $response, $args) {
        return $this->apiResponse($response, $this->model->get($this->token->id));
    }
}