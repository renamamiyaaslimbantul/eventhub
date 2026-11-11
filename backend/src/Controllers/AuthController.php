<?php
declare(strict_types=1);

namespace EventHub\Controllers;

use EventHub\Models\UserModel;
use EventHub\Services\JwtService;
use EventHub\Helpers\InputSanitizer;
use EventHub\Helpers\Validator;
use EventHub\Helpers\ResponseHelper;

/**
 * AuthController — register, login, me.
 */
final class AuthController
{
    private UserModel $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    /**
     * POST /api/auth/register
     */
    public function register(): void
    {
        $body = json_decode(file_get_contents('php://input'), true) ?? [];

        $data = [
            'name'     => InputSanitizer::sanitizeString($body['name']     ?? null),
            'email'    => InputSanitizer::sanitizeEmail($body['email']     ?? null),
            'password' => $body['password'] ?? null,
            'role'     => InputSanitizer::sanitizeString($body['role']     ?? 'participant'),
        ];

        $errors = Validator::validate($data, [
            'name'     => 'required|min:3|max:100',
            'email'    => 'required|email|max:255',
            'password' => 'required|min:6|max:72',
            'role'     => 'enum:admin,participant',
        ]);

        if ($errors !== null) {
            ResponseHelper::unprocessable('Validation failed', $errors);
        }

        if ($this->userModel->emailExists($data['email'])) {
            ResponseHelper::badRequest('Email already registered', ['email' => 'This email is already taken.']);
        }

        $hashed = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);

        try {
            $userId = $this->userModel->create([
                'name'     => $data['name'],
                'email'    => $data['email'],
                'password' => $hashed,
                'role'     => $data['role'],
            ]);
        } catch (\Throwable) {
            ResponseHelper::serverError('Registration failed');
        }

        $user  = $this->userModel->findById($userId);
        $token = (new JwtService())->generate($user);

        ResponseHelper::created([
            'user'  => $this->sanitizeUser($user),
            'token' => $token,
        ], 'Registration successful');
    }

    /**
     * POST /api/auth/login
     */
    public function login(): void
    {
        $body = json_decode(file_get_contents('php://input'), true) ?? [];

        $email    = InputSanitizer::sanitizeEmail($body['email']    ?? null);
        $password = $body['password'] ?? null;

        $errors = Validator::validate(compact('email', 'password'), [
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        if ($errors !== null) {
            ResponseHelper::unprocessable('Validation failed', $errors);
        }

        $user = $this->userModel->findByEmail($email);

        if ($user === null || !password_verify($password, $user['password'])) {
            ResponseHelper::unauthorized('Invalid email or password');
        }

        $token = (new JwtService())->generate($user);

        ResponseHelper::success([
            'user'  => $this->sanitizeUser($user),
            'token' => $token,
        ], 'Login successful');
    }

    /**
     * GET /api/auth/me
     */
    public function me(): void
    {
        $payload = $_REQUEST['__jwt_payload'] ?? null;
        $userId  = $payload['data']['id'] ?? null;

        if ($userId === null) {
            ResponseHelper::unauthorized();
        }

        $user = $this->userModel->findById((int)$userId);

        if ($user === null) {
            ResponseHelper::notFound('User not found');
        }

        ResponseHelper::success($this->sanitizeUser($user), 'User profile');
    }

    /**
     * Jangan pernah kembalikan password hash ke client.
     */
    private function sanitizeUser(array $user): array
    {
        unset($user['password']);
        return $user;
    }
}
