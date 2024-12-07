<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

$routes->get('/', 'UserController::login'); // Halaman login
$routes->get('/login', 'UserController::login'); // Login form
$routes->post('/login/process', 'UserController::loginProcess'); // Proses login
$routes->get('/logout', 'UserController::logout'); // Proses logout
$routes->get('/register', 'UserController::register'); // Register form
$routes->post('/register/process', 'UserController::registerProcess'); // Proses register
$routes->get('/home', 'HomeController::index'); // Halaman home setelah login
$routes->post('/home/generatePdf', 'HomeController::generatePdf'); // Generate PDF
$routes->get('verify', 'UserController::verify');
$routes->get('history', 'HomeController::history');
$routes->post('offers/create', 'UserController::createOffer');
