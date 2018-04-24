<?php
require_once 'basic_controller.php';

class ThingsController extends BasicController {

    public function index($request, $response, $args) {
        $things = array('name' => 'things');
        $response->getBody()->write(json_encode($things));
        return $response->withHeader('Content-type', 'application/json');
    }
    public function show($request, $response, $args) {

    }
    public function create($request, $response, $args) {

    }
    public function update($request, $response, $args) {

    }
    public function destroy($request, $response, $args) {

    }
}