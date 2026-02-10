<?php
require_once 'config/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Метод не поддерживается']);
    exit;
}

// Получаем данные из формы
$data = json_decode(file_get_contents('php://input'), true);

// Валидация данных
$errors = [];

if (empty($data['fullName'])) {
    $errors[] = 'ФИО обязательно для заполнения';
}

if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Введите корректный email';
}

if (empty($data['phone'])) {
    $errors[] = 'Телефон обязателен для заполнения';
}

if (empty($data['eventDate'])) {
    $errors[] = 'Выберите мероприятие';
}

if (empty($data['participation'])) {
    $errors[] = 'Выберите форму участия';
}

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

try {
    $pdo = connectDB();
    
    // Определяем название мероприятия по дате
    $eventNames = [
        '2024-03-15' => 'Фестиваль "Семейные традиции"',
        '2024-03-22' => 'Семинар "Современная семья"',
        '2024-04-05' => 'Выставка "Семейные ценности"',
        '2024-04-12' => 'Онлайн-марафон "Родительство"'
    ];
    
    $eventName = $eventNames[$data['eventDate']] ?? 'Неизвестное мероприятие';
    
    // Сохраняем в базу данных
    $sql = "INSERT INTO registrations 
            (full_name, email, phone, event_name, event_date, participation, participants, comments) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $data['fullName'],
        $data['email'],
        $data['phone'],
        $eventName,
        $data['eventDate'],
        $data['participation'],
        $data['participants'] ?? 1,
        $data['comments'] ?? ''
    ]);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Регистрация успешно завершена! Мы свяжемся с вами в ближайшее время.'
    ]);
    
} catch (PDOException $e) {
    if ($e->getCode() == 23000) { // Дублирующая запись
        http_response_code(409);
        echo json_encode([
            'success' => false, 
            'message' => 'Вы уже зарегистрированы на это мероприятие'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'message' => 'Ошибка сервера: ' . $e->getMessage()
        ]);
    }
}
?>