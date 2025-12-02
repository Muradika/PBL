<?php
session_start();
include '../database/pengumuman.php';

// Pastikan user sudah login (sesuaikan dengan sistem login Anda)
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';
$announcement_id = (int) ($_POST['announcement_id'] ?? 0);

if ($announcement_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid announcement ID']);
    exit;
}

if ($action === 'add') {
    // Tambah ke favorites
    $stmt = $conn->prepare("INSERT INTO favorites (user_id, announcement_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE id=id");
    $stmt->bind_param("si", $user_id, $announcement_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Added to favorites', 'action' => 'added']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add']);
    }
    $stmt->close();

} elseif ($action === 'remove') {
    // Hapus dari favorites
    $stmt = $conn->prepare("DELETE FROM favorites WHERE user_id = ? AND announcement_id = ?");
    $stmt->bind_param("si", $user_id, $announcement_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Removed from favorites', 'action' => 'removed']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to remove']);
    }
    $stmt->close();

} elseif ($action === 'check') {
    // Cek apakah sudah di-favorite
    $stmt = $conn->prepare("SELECT id FROM favorites WHERE user_id = ? AND announcement_id = ?");
    $stmt->bind_param("si", $user_id, $announcement_id);
    $stmt->execute();
    $result = $stmt->get_result();

    echo json_encode(['success' => true, 'is_favorited' => $result->num_rows > 0]);
    $stmt->close();
}

$conn->close();
?>