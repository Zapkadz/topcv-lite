<?php
ob_start(); // Ngăn chặn ký tự lạ lọt vào JSON
include '../config/db.php'; 

header('Content-Type: application/json');

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    try {
        $sql = "SELECT j.*, c.name as company_name, c.logo 
                FROM jobs j 
                JOIN companies c ON j.company_id = c.id 
                WHERE j.id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$id]);
        $job = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($job) {
            echo json_encode($job);
        } else {
            echo json_encode(['error' => 'Không tìm thấy tin']);
        }
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
}
exit();