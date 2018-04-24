<?php
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

require_once 'basic_controller.php';
require_once 'http_status_codes.php';

class UtilController extends BasicController
{

    function __construct(\Interop\Container\ContainerInterface $ci)
    {
        parent::__construct($ci);
    }

    function phpInfo()
    {
       phpinfo();
    }
}
