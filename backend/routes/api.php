<?php

declare(strict_types=1);

/**
 * Routes API EventHub — definisi semua endpoint.
 */

use EventHub\Core\Router;
use EventHub\Controllers\AuthController;
use EventHub\Controllers\EventController;
use EventHub\Controllers\RegistrationController;
use EventHub\Middlewares\JwtMiddleware;
use EventHub\Middlewares\RoleMiddleware;

$router = new Router();

$auth   = new AuthController();
$events = new EventController();
$regist = new RegistrationController();

// ----------------------------------------------------------
// AUTH
// ----------------------------------------------------------
$router->post('/api/auth/register', fn() => $auth->register());
$router->post('/api/auth/login',    fn() => $auth->login());
$router->get('/api/auth/me', function () use ($auth) {
    JwtMiddleware::verify();
    $auth->me();
});

// ----------------------------------------------------------
// EVENTS (read — publik)
// ----------------------------------------------------------
$router->get('/api/events',      fn() => $events->index());
$router->get('/api/events/{id}', fn(string $id) => $events->show((int)$id));

// ----------------------------------------------------------
// EVENTS (write — privat, admin)
// ----------------------------------------------------------
$router->post('/api/events', function () use ($events) {
    JwtMiddleware::verify();
    RoleMiddleware::enforce(['admin']);
    $events->store();
});

$router->put('/api/events/{id}', function (string $id) use ($events) {
    JwtMiddleware::verify();
    RoleMiddleware::enforce(['admin']);
    $events->update((int)$id);
});

$router->delete('/api/events/{id}', function (string $id) use ($events) {
    JwtMiddleware::verify();
    RoleMiddleware::enforce(['admin']);
    $events->destroy((int)$id);
});

// ----------------------------------------------------------
// REGISTRATIONS
// ----------------------------------------------------------
// Daftar event — privat, participant
$router->post('/api/events/{id}/register', function (string $id) use ($regist) {
    JwtMiddleware::verify();
    $regist->register((int)$id);
});

// List peserta — privat, admin
$router->get('/api/events/{id}/participants', function (string $id) use ($regist) {
    JwtMiddleware::verify();
    RoleMiddleware::enforce(['admin']);
    $regist->participants((int)$id);
});

// Update status — privat, admin
$router->patch('/api/registrations/{id}/status', function (string $id) use ($regist) {
    JwtMiddleware::verify();
    RoleMiddleware::enforce(['admin']);
    $regist->updateStatus((int)$id);
});

// Cancel registration — privat, participant (milik sendiri) / admin
$router->delete('/api/registrations/{id}', function (string $id) use ($regist) {
    JwtMiddleware::verify();
    $regist->destroy((int)$id);
});

return $router;
