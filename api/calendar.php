<?php
require_once "db.php";
header("Content-Type: application/json");

$headers = getallheaders();
$authHeader = $headers["Authorization"] ?? "";
$token = str_replace("Bearer ", "", $authHeader);

if (!$token) {
    echo json_encode(["success" => false, "message" => "No token provided"]);
    exit;
}

// فحص هويّة المستخدم (User أو Admin)
$stmt = $pdo->prepare("SELECT id FROM users WHERE session_token = ? LIMIT 1");
$stmt->execute([$token]);
$user = $stmt->fetch();

$adminId = null;
if (!$user) {
    $stmt = $pdo->prepare("SELECT id FROM admins WHERE session_token = ? LIMIT 1");
    $stmt->execute([$token]);
    $admin = $stmt->fetch();
    if (!$admin) {
        echo json_encode(["success" => false, "message" => "Unauthorized"]);
        exit;
    }
    $adminId = $admin['id'];
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// ---------------- USER ACTIONS ----------------

// 1. تحديد وقفل وقت/يوم غير متاح
if ($action === 'set_unavailability' && $user) {
    $data = json_decode(file_get_contents("php://input"), true);
    $date = $data['date'] ?? '';
    $startTime = $data['start_time'] ?? '00:00:00';
    $endTime = $data['end_time'] ?? '23:59:59';

    if (!$date) {
        echo json_encode(["success" => false, "message" => "Date required"]);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO user_unavailability (user_id, date, start_time, end_time) VALUES (?, ?, ?, ?)");
    $stmt->execute([$user['id'], $date, $startTime, $endTime]);

    echo json_encode(["success" => true, "message" => "Time slot blocked successfully"]);
    exit;
}

// 2. تحديث حالة الموعد (قبول / رفض) من اليوزر
if ($action === 'respond_appointment' && $user) {
    $data = json_decode(file_get_contents("php://input"), true);
    $appointmentId = $data['id'] ?? 0;
    $status = $data['status'] ?? '';

    if (!in_array($status, ['approved', 'rejected'])) {
        echo json_encode(["success" => false, "message" => "Invalid status"]);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE appointments SET status = ? WHERE id = ? AND user_id = ?");
    $stmt->execute([$status, $appointmentId, $user['id']]);

    echo json_encode(["success" => true, "message" => "Appointment status updated"]);
    exit;
}

// 3. جلب بيانات التقويم والمواعيد لليوزر
if ($action === 'get_user_calendar' && $user) {
    $stmt = $pdo->prepare("SELECT * FROM user_unavailability WHERE user_id = ? ORDER BY date DESC");
    $stmt->execute([$user['id']]);
    $blocked = $stmt->fetchAll();

    $stmt = $pdo->prepare("SELECT * FROM appointments WHERE user_id = ? ORDER BY date DESC, time DESC");
    $stmt->execute([$user['id']]);
    $appointments = $stmt->fetchAll();

    echo json_encode([
        "success" => true,
        "blocked" => $blocked,
        "appointments" => $appointments
    ]);
    exit;
}

// ---------------- ADMIN ACTIONS ----------------

// 4. حجز موعد جديد للمستخدم بواسطة الأدمن
if ($action === 'create_appointment' && $adminId) {
    $data = json_decode(file_get_contents("php://input"), true);
    $targetUserId = $data['user_id'] ?? 0;
    $title = $data['title'] ?? 'Meeting';
    $date = $data['date'] ?? '';
    $time = $data['time'] ?? '';

    // التحقق من أن الوقت غير مغلق من المستخدم
    $stmt = $pdo->prepare("SELECT id FROM user_unavailability WHERE user_id = ? AND date = ? AND (? BETWEEN start_time AND end_time)");
    $stmt->execute([$targetUserId, $date, $time]);
    if ($stmt->fetch()) {
        echo json_encode(["success" => false, "message" => "User is unavailable at this date/time!"]);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO appointments (user_id, title, date, time, status) VALUES (?, ?, ?, ?, 'pending')");
    $stmt->execute([$targetUserId, $title, $date, $time]);

    echo json_encode(["success" => true, "message" => "Appointment request sent to user"]);
    exit;
}

// 5. جلب بيانات التقويم الخاصة بمستخدم معين للأدمن
if ($action === 'get_admin_calendar_data' && $adminId) {
    $targetUserId = $_GET['user_id'] ?? 0;

    $stmt = $pdo->prepare("SELECT * FROM user_unavailability WHERE user_id = ?");
    $stmt->execute([$targetUserId]);
    $blocked = $stmt->fetchAll();

    $stmt = $pdo->prepare("SELECT * FROM appointments WHERE user_id = ?");
    $stmt->execute([$targetUserId]);
    $appointments = $stmt->fetchAll();

    echo json_encode([
        "success" => true,
        "blocked" => $blocked,
        "appointments" => $appointments
    ]);
    exit;
}

echo json_encode(["success" => false, "message" => "Invalid action"]);