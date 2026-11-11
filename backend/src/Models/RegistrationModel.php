<?php
declare(strict_types=1);

namespace EventHub\Models;

use PDO;
use PDOStatement;
use EventHub\Database;

/**
 * Model Registration — akses tabel registrations via PDO prepared statements.
 */
final class RegistrationModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Pendaftaran baru.
     * @return array{id:int, status:string}|false
     */
    public function create(int $eventId, int $userId): array|false
    {
        $stmt = $this->db->prepare(
            'INSERT INTO registrations (event_id, user_id, status) VALUES (:event_id, :user_id, \'registered\')'
        );
        $stmt->bindValue(':event_id', $eventId, PDO::PARAM_INT);
        $stmt->bindValue(':user_id',  $userId,  PDO::PARAM_INT);
        $ok = $stmt->execute();
        if (!$ok) return false;

        return [
            'id'     => (int)$this->db->lastInsertId(),
            'status' => 'registered',
        ];
    }

    /**
     * Cek apakah user sudah terdaftar (belum canceled) di event.
     */
    public function isRegistered(int $eventId, int $userId): bool
    {
        $stmt = $this->db->prepare(
            "SELECT 1 FROM registrations WHERE event_id = :event_id AND user_id = :user_id AND status != 'canceled'"
        );
        $stmt->execute(['event_id' => $eventId, 'user_id' => $userId]);
        return $stmt->fetch() !== false;
    }

    /**
     * Ambil semua participant sebuah event.
     */
    public function getParticipantsByEvent(int $eventId): array
    {
        $stmt = $this->db->prepare(
            "SELECT r.id, r.status, r.registered_at,
                    u.id AS user_id, u.name, u.email
             FROM registrations r
             JOIN users u ON u.id = r.user_id
             WHERE r.event_id = :event_id
             ORDER BY r.registered_at ASC"
        );
        $stmt->execute(['event_id' => $eventId]);
        return $stmt->fetchAll();
    }

    /**
     * Ambil registration by ID.
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM registrations WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Update status registration.
     */
    public function updateStatus(int $id, string $status): bool
    {
        $allowed = ['registered', 'attended', 'canceled'];
        if (!in_array($status, $allowed, true)) return false;

        $stmt = $this->db->prepare('UPDATE registrations SET status = :status WHERE id = :id');
        $stmt->bindValue(':status', $status, PDO::PARAM_STR);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Hapus registration.
     */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM registrations WHERE id = :id');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Ambil semua registrasi seorang user.
     */
}
