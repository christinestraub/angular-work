<?php

use Psr\Http\Message\ResponseInterface as Response;

require_once 'http_status_codes.php';
require_once __DIR__ . '/../models/basic_model.php';

define ('SNAPSHOT_ERR_OK', 0);
define ('SNAPSHOT_ARCHIVE_EXISTS', 'File already uploaded');
define ('SNAPSHOT_UPLOAD_ERROR', 'Upload error');
define ('SNAPSHOT_DB_ERROR', 'Database error');
define ('SNAPSHOT_PARAM_ERROR', 'Parameter error');
define ('SNAPSHOT_ARCHIVE_NOT_FOUND', 'The archive not found in the database.');
define ('SNAPSHOT_PARSE_ERROR', 'Parse error');
define ('SNAPSHOT_UPDATE_FAIL', 'The update has failed or has not changed.');

class BasicController
{
    protected $ci;
    protected $logger;
    protected $user;
    protected $settings;
    protected $db;
    protected $token;

    public function __construct(Interop\Container\ContainerInterface $ci)
    {
        $this->logger = $ci->get('logger');
        $this->settings = $ci->get('settings');
        $this->db = $ci->get('db');

        if ($ci->has('jwt')) {
            $this->token = $ci->get('jwt');
            $this->user = $this->db->accounts()[$this->token->id];
        }

        $this->ci = $ci;
    }

    public function apiResponse(Response $response, $data = '', $status = HttpStatusCodes::HTTP_OK)
    {
        if (is_array($data)) {
            return $response->withJson($data, $status);
        } else {
            $response->getBody()->write($data);
            return $response->withStatus($status, strval($data));
        }
    }
}
