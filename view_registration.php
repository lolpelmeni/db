<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    die('Доступ запрещен');
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('Неверный ID');
}

$pdo = connectDB();
$stmt = $pdo->prepare("SELECT * FROM registrations WHERE id = ?");
$stmt->execute([$_GET['id']]);
$registration = $stmt->fetch();

if (!$registration) {
    die('Регистрация не найдена');
}
?>

<div class="registration-details">
    <p><strong>ID:</strong> <?php echo $registration['id']; ?></p>
    <p><strong>ФИО:</strong> <?php echo htmlspecialchars($registration['full_name']); ?></p>
    <p><strong>Email:</strong> <?php echo htmlspecialchars($registration['email']); ?></p>
    <p><strong>Телефон:</strong> <?php echo htmlspecialchars($registration['phone']); ?></p>
    <p><strong>Мероприятие:</strong> <?php echo htmlspecialchars($registration['event_name']); ?></p>
    <p><strong>Дата мероприятия:</strong> <?php echo date('d.m.Y', strtotime($registration['event_date'])); ?></p>
    <p><strong>Форма участия:</strong> <?php echo $registration['participation'] == 'online' ? 'Онлайн' : 'Очно'; ?></p>
    <p><strong>Количество участников:</strong> <?php echo $registration['participants']; ?></p>
    <p><strong>Комментарий:</strong> <?php echo nl2br(htmlspecialchars($registration['comments'])); ?></p>
    <p><strong>Дата регистрации:</strong> <?php echo date('d.m.Y H:i', strtotime($registration['registration_date'])); ?></p>
</div>