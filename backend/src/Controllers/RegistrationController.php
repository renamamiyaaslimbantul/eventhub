<?php
declare(strict_types=1);

namespace EventHub\Controllers;

use EventHub\Models\EventModel;
use EventHub\Models\RegistrationModel;
use EventHub\Helpers\Validator;
use EventHub\Helpers\ResponseHelper;

/**
 * RegistrationController — pendaftaran event & manajemen peserta.
 */
final class RegistrationController
{
    private EventModel        $eventModel;
    private RegistrationModel $regModel;

    public function __construct()
    {
        $this->eventModel = new EventModel();
        $this->regModel   = new RegistrationModel();
    }

    /**
     * POST /api/events/{id}/register — privat, participant
     */
    public function register(int $eventId): void
    {
        $payload = $_REQUEST['__jwt_payload'];
        $userId  = (int)$payload['data']['id'];
        $role    = $payload['data']['role'];

        if ($role !== 'participant') {
            ResponseHelper::forbidden('Only participants can register for events');
        }

        $event = $this->eventModel->findById($eventId);
        if ($event === null) {
            ResponseHelper::notFound('Event not found');
        }

        $eventDate = \DateTime::createFromFormat('Y-m-d', $event['event_date']);
        $now = new \DateTime('today');
        if (!$eventDate || $eventDate < $now) {
            ResponseHelper::badRequest('Cannot register for past events');
        }

        if ($this->regModel->isRegistered($eventId, $userId)) {
            ResponseHelper::badRequest('You are already registered for this event');
        }

        $registered = $this->eventModel->getCurrentRegistrations($eventId);
        $maxSeats   = (int)$event['max_seats'];
        if ($registered >= $maxSeats) {
            ResponseHelper::badRequest('Event is fully booked');
        }

        try {
            $result = $this->regModel->create($eventId, $userId);
        } catch (\PDOException $e) {
            // Race condition: unique(user_id, event_id) — user daftar bersamaan
            if ((int)$e->getCode() === 23000 || str_contains($e->getMessage(), 'unique_user_event')) {
                ResponseHelper::badRequest('You are already registered for this event');
            }
            ResponseHelper::serverError('Registration failed');
        }

        if ($result === false) {
            ResponseHelper::serverError('Registration failed');
        }

        ResponseHelper::created([
            'registration_id' => $result['id'],
            'event_id'        => $eventId,
            'status'          => $result['status'],
            'spots_remaining' => $maxSeats - $registered - 1,
        ], 'Registered successfully');
    }

    /**
     * GET /api/events/{id}/participants — privat, admin only
     */
    public function participants(int $eventId): void
    {
        $event = $this->eventModel->findById($eventId);
        if ($event === null) {
            ResponseHelper::notFound('Event not found');
        }

        $participants = $this->regModel->getParticipantsByEvent($eventId);
        $registered   = $this->eventModel->getCurrentRegistrations($eventId);

        ResponseHelper::success([
            'event'            => $event,
            'total_registered' => $registered,
            'max_seats'        => (int)$event['max_seats'],
            'participants'     => $participants,
        ], 'Participants retrieved');
    }

    /**
     * PATCH /api/registrations/{id}/status — privat, admin only
     */
    public function updateStatus(int $id): void
    {
        $body   = json_decode(file_get_contents('php://input'), true) ?? [];
        $status = $body['status'] ?? null;

        $errors = Validator::validate(compact('status'), [
            'status' => 'required|enum:registered,attended,canceled',
        ]);

        if ($errors !== null) {
            ResponseHelper::unprocessable('Validation failed', $errors);
        }

        $reg = $this->regModel->findById($id);
        if ($reg === null) {
            ResponseHelper::notFound('Registration not found');
        }

        $ok = $this->regModel->updateStatus($id, $status);
        if (!$ok) {
            ResponseHelper::serverError('Failed to update status');
        }

        $updated = $this->regModel->findById($id);
        ResponseHelper::success($updated, 'Status updated successfully');
    }

    /**
     * DELETE /api/registrations/{id} — privat, participant (miliknya) / admin
     */
    public function destroy(int $id): void
    {
        $payload = $_REQUEST['__jwt_payload'];
        $userId  = (int)$payload['data']['id'];
        $role    = $payload['data']['role'];

        $reg = $this->regModel->findById($id);
        if ($reg === null) {
            ResponseHelper::notFound('Registration not found');
        }

        if ($role === 'participant' && (int)$reg['user_id'] !== $userId) {
            ResponseHelper::forbidden('You can only cancel your own registration');
        }

        $ok = $this->regModel->delete($id);
        if (!$ok) {
            ResponseHelper::serverError('Failed to cancel registration');
        }

        ResponseHelper::success(null, 'Registration canceled successfully');
    }
}
