<?php
require_once __DIR__ . '/_bootstrap.php';
header('Content-Type: application/json');
require_once __DIR__ . '/../auth_guard.php';
require_api_auth(['hr', 'department_head', 'employee']);
require_once '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$data = json_decode(file_get_contents('php://input'), true);
	$user_id = $data['user_id'] ?? null;
	$profile_picture = $data['profile_picture'] ?? null;
	if ($user_id && $profile_picture) {
		$stmt = $pdo->prepare('UPDATE users SET profile_picture = ? WHERE id = ?');
		$stmt->execute([$profile_picture, $user_id]);
		echo json_encode(['success' => true]);
		exit;
	}
	echo json_encode(['success' => false, 'error' => 'Missing data']);
	exit;
}

$user_id = $_GET['user_id'] ?? null;
if ($user_id) {
	$stmt = $pdo->prepare('SELECT firstname, lastname, mi, position, profile_picture FROM users WHERE id = ?');
	$stmt->execute([$user_id]);
	$res = $stmt->fetch(PDO::FETCH_ASSOC);
	echo json_encode($res ?: []);
	exit;
}

$email = $_GET['email'] ?? '';
if ($email) {
	try {
		$stmt = $pdo->prepare('SELECT department, lastname AS "lastName", firstname AS "firstName", middlename AS "middleName", position, salary FROM employees WHERE email=?');
		$stmt->execute([$email]);
		$res = $stmt->fetch(PDO::FETCH_ASSOC);
		echo json_encode($res ?: []);
	} catch (PDOException $e) {
		http_response_code(500); echo json_encode(['error'=>'DB connection failed']);
	}
	exit;
}
echo json_encode([]);
