<?php
require_once 'route.php';
require_once 'api.php';

$router = new Router();

// Define CRUD routes
$router->get('/users/getAllUser/', 'getAllUsers');   // Read
$router->post('/users/createUser/', 'createUser');   // Create
$router->put('/users/updateUser/', 'updateUser');   // Update (using POST for simplicity)
$router->delete('/users/deleteUser/', 'deleteUser');   // Delete (using POST for simplicity)

// Dispatch the request

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
