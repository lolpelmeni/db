<?php
require_once 'config/db.php';

$pdo = connectDB();

// Создание базы данных, если не существует
try {
    $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE " . DB_NAME);
    createTables($pdo);
    
    echo "<h2>Установка успешно завершена!</h2>";
    echo "<p>База данных создана. Дефолтный администратор:</p>";
    echo "<p><strong>Логин:</strong> admin</p>";
    echo "<p><strong>Пароль:</strong> admin123</p>";
    echo "<p><a href='admin_login.php'>Перейти в админ-панель</a></p>";
    echo "<p><strong>ВАЖНО:</strong> Удалите этот файл (install.php) после установки!</p>";
    
} catch (PDOException $e) {
    die("Ошибка при установке: " . $e->getMessage());
}
?>