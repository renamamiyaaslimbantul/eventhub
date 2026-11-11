<?php

declare(strict_types=1);

/**
 * EventHub — Database Seeder
 * Menyisipkan data awal: 1 admin, 3 participant, 3 event contoh.
 * Idempotent: aman dijalankan berkali-kali (skip jika data sudah ada).
 *
 * Jalankan setelah import skema:
 *   mysql -u root -p < database/migrations/001_init.sql
 *   php database/seeders/seed.php
 */

require __DIR__ . '/../../vendor/autoload.php';

if (file_exists(__DIR__ . '/../../.env') && class_exists('Dotenv\Dotenv')) {
    Dotenv\Dotenv::createImmutable(__DIR__ . '/../..')->safeLoad();
}

use EventHub\Database;

$db = Database::getInstance();

// ----------------------------------------------------------
// 1. Users — password di-hash dengan bcrypt (password_hash)
// ----------------------------------------------------------
$users = [
    ['Bagas Admin',                 'admin@eventhub.ac.id',  'admin123',      'admin'],
    ['Muhammad Abyan',              'abyan@student.ac.id',   'participant123', 'participant'],
    ['Delwyn Putra Darrenakhira',   'delwyn@student.ac.id',  'participant123', 'participant'],
    ['Apriliansyah Mahardika',      'april@student.ac.id',   'participant123', 'participant'],
];

$adminId = 1;
foreach ($users as [$name, $email, $plainPassword, $role]) {
    $stmt = $db->prepare('SELECT id FROM users WHERE email = :email');
    $stmt->execute(['email' => $email]);
    $existing = $stmt->fetch();

    if ($existing) {
        echo "[skip] user sudah ada: {$email}\n";
        if ($role === 'admin') {
            $adminId = (int)$existing['id'];
        }
        continue;
    }

    $stmt = $db->prepare('INSERT INTO users (name, email, password, role) VALUES (:name, :email, :password, :role)');
    $stmt->execute([
        'name'     => $name,
        'email'    => $email,
        'password' => password_hash($plainPassword, PASSWORD_BCRYPT, ['cost' => 12]),
        'role'     => $role,
    ]);
    echo "[ok] user dibuat: {$email} (role: {$role}, password: {$plainPassword})\n";
}

// ----------------------------------------------------------
// 2. Events — organizer = admin
// Tanggal dihitung relatif terhadap hari ini agar selalu di masa depan
// (event di masa lalu akan ditolak saat registrasi oleh aplikasi).
// ----------------------------------------------------------
$events = [
    [
        'Workshop Dasar-Dasar Machine Learning dengan Python',
        'Workshop intensif 3 jam tentang konsep machine learning menggunakan Python dan scikit-learn. Peserta akan membuat model prediksi sederhana dari nol.',
        'Workshop',
        '+30 days',
        'Ruang Lab Komputer FTIR Lt. 3, Universitas XYZ',
        40,
    ],
    [
        'Seminar Karir di Era Digital 2026',
        'Seminar yang membahas peluang dan tantangan karir di era digital, mengundang pembicara dari perusahaan teknologi ternama.',
        'Seminar',
        '+45 days',
        'Aula Utama Gedung Rektorat, Universitas XYZ',
        200,
    ],
    [
        'Webinar: Cybersecurity untuk Mahasiswa',
        'Webinar online membahas dasar-dasar keamanan siber, phishing awareness, dan cara melindungi data pribadi di dunia digital.',
        'Webinar',
        '+60 days',
        'Online (Zoom Meeting)',
        500,
    ],
];

foreach ($events as [$title, $description, $category, $eventDate, $location, $maxSeats]) {
    $stmt = $db->prepare('SELECT id FROM events WHERE title = :title');
    $stmt->execute(['title' => $title]);
    if ($stmt->fetch()) {
        echo "[skip] event sudah ada: {$title}\n";
        continue;
    }

    $stmt = $db->prepare(
        'INSERT INTO events (organizer_id, title, description, category, event_date, location, max_seats)
         VALUES (:organizer_id, :title, :description, :category, :event_date, :location, :max_seats)'
    );
    $stmt->execute([
        'organizer_id' => $adminId,
        'title'        => $title,
        'description'  => $description,
        'category'     => $category,
        'event_date'   => (new DateTime($eventDate))->format('Y-m-d'),
        'location'     => $location,
        'max_seats'    => $maxSeats,
    ]);
    echo "[ok] event dibuat: {$title}\n";
}

echo "\nSeed selesai. Login admin: admin@eventhub.ac.id / admin123\n";
