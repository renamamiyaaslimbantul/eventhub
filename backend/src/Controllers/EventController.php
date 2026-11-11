<?php
declare(strict_types=1);

namespace EventHub\Controllers;

use EventHub\Models\EventModel;
use EventHub\Helpers\InputSanitizer;
use EventHub\Helpers\Validator;
use EventHub\Helpers\ResponseHelper;

/**
 * EventController — CRUD event.
 * Write operations (store/update/destroy) hanya untuk admin.
 */
final class EventController
{
    private EventModel $eventModel;

    public function __construct()
    {
        $this->eventModel = new EventModel();
    }

    /**
     * GET /api/events — publik, filter ?category= & ?search=
     */
    public function index(): void
    {
        $category = $_GET['category'] ?? null;
        $search   = $_GET['search']   ?? null;

        if ($category !== null && !in_array($category, ['Webinar', 'Workshop', 'Seminar'], true)) {
            ResponseHelper::badRequest('Invalid category. Must be: Webinar, Workshop, Seminar');
        }

        $events = $this->eventModel->getAll(
            ($category !== null && $category !== '') ? $category : null,
            ($search   !== null && $search   !== '') ? $search   : null
        );

        ResponseHelper::success($events, 'Events retrieved successfully');
    }

    /**
     * GET /api/events/{id} — publik
     */
    public function show(int $id): void
    {
        $event = $this->eventModel->findById($id);

        if ($event === null) {
            ResponseHelper::notFound('Event not found');
        }

        ResponseHelper::success($event, 'Event detail');
    }

    /**
     * POST /api/events — privat, admin only
     */
    public function store(): void
    {
        $body        = json_decode(file_get_contents('php://input'), true) ?? [];
        $payload     = $_REQUEST['__jwt_payload'];
        $organizerId = (int)$payload['data']['id'];

        $data = [
            'title'       => InputSanitizer::sanitizeString($body['title']       ?? null),
            'description' => InputSanitizer::sanitizeString($body['description'] ?? null),
            'category'    => InputSanitizer::sanitizeString($body['category']    ?? null),
            'event_date'  => $body['event_date'] ?? null,
            'location'    => InputSanitizer::sanitizeString($body['location']    ?? null),
            'max_seats'   => $body['max_seats']  ?? null,
            'poster_url'  => InputSanitizer::sanitizeUrl($body['poster_url'] ?? null),
        ];

        $errors = Validator::validate($data, [
            'title'       => 'required|min:5|max:255',
            'description' => 'required|min:10',
            'category'    => 'required|enum:Webinar,Workshop,Seminar',
            'event_date'  => 'required|date:Y-m-d',
            'location'    => 'required|min:3|max:255',
            'max_seats'   => 'required|integer',
        ]);

        if ($errors !== null) {
            ResponseHelper::unprocessable('Validation failed', $errors);
        }

        $eventDate = \DateTime::createFromFormat('Y-m-d', $data['event_date']);
        $today     = new \DateTime('today');
        if (!$eventDate || $eventDate < $today) {
            ResponseHelper::badRequest('event_date must be today or in the future (Y-m-d)');
        }

        if ((int)$data['max_seats'] < 1) {
            ResponseHelper::badRequest('max_seats must be at least 1');
        }

        $data['max_seats'] = (int)$data['max_seats'];

        try {
            $eventId = $this->eventModel->create($data + ['organizer_id' => $organizerId]);
            $event   = $this->eventModel->findById($eventId);
            ResponseHelper::created($event, 'Event created successfully');
        } catch (\Throwable) {
            ResponseHelper::serverError('Failed to create event');
        }
    }

    /**
     * PUT /api/events/{id} — privat, admin only
     */
    public function update(int $id): void
    {
        $event = $this->eventModel->findById($id);
        if ($event === null) {
            ResponseHelper::notFound('Event not found');
        }

        $body = json_decode(file_get_contents('php://input'), true) ?? [];

        // Ambil field yang diizinkan saja, sanitasi per tipe
        $data = [];
        if (array_key_exists('title', $body))       $data['title']       = InputSanitizer::sanitizeString($body['title']);
        if (array_key_exists('description', $body)) $data['description'] = InputSanitizer::sanitizeString($body['description']);
        if (array_key_exists('category', $body))    $data['category']    = InputSanitizer::sanitizeString($body['category']);
        if (array_key_exists('event_date', $body))  $data['event_date']  = $body['event_date'];
        if (array_key_exists('location', $body))    $data['location']    = InputSanitizer::sanitizeString($body['location']);
        if (array_key_exists('max_seats', $body))   $data['max_seats']   = (int)$body['max_seats'];
        if (array_key_exists('poster_url', $body))  $data['poster_url']  = InputSanitizer::sanitizeUrl($body['poster_url']);

        $rules = [];
        if (isset($data['title']))      $rules['title']      = 'min:5|max:255';
        if (isset($data['category']))   $rules['category']   = 'enum:Webinar,Workshop,Seminar';
        if (isset($data['event_date'])) $rules['event_date'] = 'date:Y-m-d';
        if (isset($data['max_seats']))  $rules['max_seats']  = 'integer';
        if (isset($data['location']))   $rules['location']   = 'min:3|max:255';

        if ($rules !== []) {
            $errors = Validator::validate($data, $rules);
            if ($errors !== null) {
                ResponseHelper::unprocessable('Validation failed', $errors);
            }
        }

        if ($data === []) {
            ResponseHelper::badRequest('No fields to update');
        }

        $ok = $this->eventModel->update($id, $data);
        if (!$ok) {
            ResponseHelper::serverError('Failed to update event');
        }

        $updated = $this->eventModel->findById($id);
        ResponseHelper::success($updated, 'Event updated successfully');
    }

    /**
     * DELETE /api/events/{id} — privat, admin only
     */
    public function destroy(int $id): void
    {
        $event = $this->eventModel->findById($id);
        if ($event === null) {
            ResponseHelper::notFound('Event not found');
        }

        $ok = $this->eventModel->delete($id);
        if (!$ok) {
            ResponseHelper::serverError('Failed to delete event');
        }

        ResponseHelper::success(null, 'Event deleted successfully');
    }
}
