<?php
declare(strict_types=1);

namespace EventHub\Models;

use PDO;
use PDOStatement;
use EventHub\Database;

/**
 * Model Event — akses tabel events via PDO prepared statements.
 */
final class EventModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Ambil semua event, opsi filter category & search.
     */
    public function getAll(?string $category = null, ?string $search = null): array
    {
        $sql = 'SELECT e.*, u.name AS organizer_name,
                (SELECT COUNT(*) FROM registrations r WHERE r.event_id = e.id AND r.status = \'registered\') AS registered_count
                FROM events e
                JOIN users u ON u.id = e.organizer_id';

        $params = [];
        $conditions = [];

        if ($category !== null && $category !== '') {
            $conditions[] = 'e.category = :category';
            $params['category'] = $category;
        }

        if ($search !== null && $search !== '') {
            $conditions[] = '(e.title LIKE :search OR e.description LIKE :search OR e.location LIKE :search)';
            $params['search'] = "%{$search}%";
        }

        if ($conditions !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $sql .= ' ORDER BY e.event_date ASC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT e.*, u.name AS organizer_name,
             (SELECT COUNT(*) FROM registrations r WHERE r.event_id = e.id AND r.status = \'registered\') AS registered_count
             FROM events e JOIN users u ON u.id = e.organizer_id WHERE e.id = :id'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO events (organizer_id, title, description, category, event_date, location, max_seats, poster_url)
             VALUES (:organizer_id, :title, :description, :category, :event_date, :location, :max_seats, :poster_url)'
        );
        $this->bindValues($stmt, [
            'organizer_id' => $data['organizer_id'],
            'title'        => $data['title'],
            'description'  => $data['description'],
            'category'     => $data['category'],
            'event_date'   => $data['event_date'],
            'location'     => $data['location'],
            'max_seats'    => (int)$data['max_seats'],
            'poster_url'   => $data['poster_url'] ?? null,
        ]);
        $stmt->execute();
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $sets = [];
        $params = [];

        $fillable = ['title','description','category','event_date','location','max_seats','poster_url'];
        foreach ($fillable as $field) {
            if (array_key_exists($field, $data)) {
                $sets[] = "{$field} = :{$field}";
                $params[$field] = $data[$field];
            }
        }

        if ($sets === []) return false;

        $stmt = $this->db->prepare("UPDATE events SET " . implode(', ', $sets) . " WHERE id = :id");
        $params['id'] = $id;
        $this->bindValues($stmt, $params);
        return $stmt->execute();
    }

    /**
     * Bind dengan tipe eksplisit — null => PARAM_NULL, int => PARAM_INT.
     * Cegah bug binding null jadi string kosong.
     */
    private function bindValues(PDOStatement $stmt, array $params): void
    {
        foreach ($params as $key => $value) {
            $type = PDO::PARAM_STR;
            if ($value === null) {
                $type = PDO::PARAM_NULL;
            } elseif (is_int($value)) {
                $type = PDO::PARAM_INT;
            }
            $stmt->bindValue(':' . $key, $value, $type);
        }
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM events WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    public function getCurrentRegistrations(int $eventId): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM registrations WHERE event_id = :event_id AND status = 'registered'"
        );
        $stmt->bindValue(':event_id', $eventId, PDO::PARAM_INT);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }
}
