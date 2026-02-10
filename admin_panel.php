<?php
session_start();
require_once 'config/db.php';

// Проверка авторизации
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit;
}

$pdo = connectDB();

// Получение статистики
$total_registrations = $pdo->query("SELECT COUNT(*) as count FROM registrations")->fetch()['count'];
$today_registrations = $pdo->query("SELECT COUNT(*) as count FROM registrations 
                                   WHERE DATE(registration_date) = CURDATE()")->fetch()['count'];
$upcoming_events = $pdo->query("SELECT COUNT(DISTINCT event_date) as count FROM registrations 
                               WHERE event_date >= CURDATE()")->fetch()['count'];

// Поиск и фильтрация
$search = $_GET['search'] ?? '';
$event_date = $_GET['event_date'] ?? '';

$sql = "SELECT * FROM registrations WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (full_name LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param]);
}

if (!empty($event_date)) {
    $sql .= " AND event_date = ?";
    $params[] = $event_date;
}

$sql .= " ORDER BY registration_date DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$registrations = $stmt->fetchAll();

// Получение уникальных дат мероприятий для фильтра
$event_dates = $pdo->query("SELECT DISTINCT event_date FROM registrations ORDER BY event_date")->fetchAll();

// Удаление записи
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM registrations WHERE id = ?");
    $stmt->execute([$delete_id]);
    header('Location: admin_panel.php?message=deleted');
    exit;
}

// Очистка старых записей
if (isset($_GET['cleanup'])) {
    $stmt = $pdo->prepare("DELETE FROM registrations WHERE event_date < CURDATE() - INTERVAL 30 DAY");
    $stmt->execute();
    header('Location: admin_panel.php?message=cleaned');
    exit;
}

// Экспорт в CSV
if (isset($_GET['export'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=registrations_' . date('Y-m-d') . '.csv');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'ФИО', 'Email', 'Телефон', 'Мероприятие', 'Дата', 'Участие', 'Участников', 'Комментарий', 'Дата регистрации']);
    
    $stmt = $pdo->query("SELECT * FROM registrations ORDER BY registration_date DESC");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ-панель - Год семьи 2024</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --danger-color: #e74c3c;
            --success-color: #2ecc71;
            --warning-color: #f39c12;
            --dark-color: #2c3e50;
            --light-color: #f8f9fa;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fb;
            color: #333;
        }
        
        .admin-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .admin-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .admin-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 20px;
            font-weight: 600;
        }
        
        .admin-user {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .admin-container {
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            display: flex;
            align-items: center;
            gap: 20px;
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
        }
        
        .stat-icon.total { background: var(--primary-color); }
        .stat-icon.today { background: var(--success-color); }
        .stat-icon.upcoming { background: var(--warning-color); }
        
        .stat-info h3 {
            font-size: 32px;
            margin-bottom: 5px;
        }
        
        .stat-info p {
            color: #666;
            font-size: 14px;
        }
        
        .admin-toolbar {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }
        
        .search-box {
            flex: 1;
            min-width: 300px;
            position: relative;
        }
        
        .search-box input {
            width: 100%;
            padding: 12px 15px 12px 45px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
        }
        
        .search-box i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
        }
        
        .filter-select {
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            background: white;
        }
        
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: var(--primary-color);
            color: white;
        }
        
        .btn-success {
            background: var(--success-color);
            color: white;
        }
        
        .btn-warning {
            background: var(--warning-color);
            color: white;
        }
        
        .btn-danger {
            background: var(--danger-color);
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .registrations-table {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        thead {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
        }
        
        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        tbody tr:hover {
            background-color: #f9f9f9;
        }
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-online {
            background: #e8f7ef;
            color: var(--success-color);
        }
        
        .status-offline {
            background: #e8f4fc;
            color: var(--primary-color);
        }
        
        .actions {
            display: flex;
            gap: 5px;
        }
        
        .action-btn {
            width: 35px;
            height: 35px;
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            border: none;
            color: white;
            font-size: 14px;
        }
        
        .action-view { background: var(--primary-color); }
        .action-delete { background: var(--danger-color); }
        .action-edit { background: var(--warning-color); }
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 30px;
        }
        
        .page-item {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: white;
            border-radius: 5px;
            text-decoration: none;
            color: #333;
            font-weight: 600;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        
        .page-item.active {
            background: var(--primary-color);
            color: white;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid var(--success-color);
        }
        
        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border-left: 4px solid var(--primary-color);
        }
        
        .no-data {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        
        .no-data i {
            font-size: 48px;
            margin-bottom: 15px;
            color: #ddd;
        }
        
        @media (max-width: 768px) {
            .admin-toolbar {
                flex-direction: column;
                align-items: stretch;
            }
            
            .search-box {
                min-width: auto;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            table {
                display: block;
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
    <header class="admin-header">
        <nav class="admin-nav">
            <div class="admin-logo">
                <i class="fas fa-home"></i>
                <span>Год семьи 2024 - Админ-панель</span>
            </div>
            <div class="admin-user">
                <i class="fas fa-user-circle"></i>
                <span><?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
                <a href="logout.php" style="color: white; margin-left: 15px;">
                    <i class="fas fa-sign-out-alt"></i> Выйти
                </a>
            </div>
        </nav>
    </header>
    
    <main class="admin-container">
        <?php if (isset($_GET['message'])): ?>
            <?php if ($_GET['message'] == 'deleted'): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <span>Запись успешно удалена</span>
            </div>
            <?php elseif ($_GET['message'] == 'cleaned'): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                <span>Старые записи успешно очищены</span>
            </div>
            <?php endif; ?>
        <?php endif; ?>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon total">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo number_format($total_registrations); ?></h3>
                    <p>Всего регистраций</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon today">
                    <i class="fas fa-calendar-day"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo number_format($today_registrations); ?></h3>
                    <p>Регистраций сегодня</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon upcoming">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo number_format($upcoming_events); ?></h3>
                    <p>Предстоящих мероприятий</p>
                </div>
            </div>
        </div>
        
        <div class="admin-toolbar">
            <form method="GET" class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" name="search" placeholder="Поиск по ФИО, email или телефону..." 
                       value="<?php echo htmlspecialchars($search); ?>">
            </form>
            
            <select name="event_date" class="filter-select" onchange="this.form.submit()">
                <option value="">Все мероприятия</option>
                <?php foreach ($event_dates as $date): ?>
                <option value="<?php echo $date['event_date']; ?>"
                    <?php echo $date['event_date'] == $event_date ? 'selected' : ''; ?>>
                    <?php echo date('d.m.Y', strtotime($date['event_date'])); ?>
                </option>
                <?php endforeach; ?>
            </select>
            
            <a href="?export=1" class="btn btn-success">
                <i class="fas fa-file-export"></i> Экспорт CSV
            </a>
            
            <a href="?cleanup=1" class="btn btn-warning" 
               onclick="return confirm('Удалить записи старше 30 дней?')">
                <i class="fas fa-broom"></i> Очистить старые
            </a>
        </div>
        
        <div class="registrations-table">
            <?php if (empty($registrations)): ?>
            <div class="no-data">
                <i class="fas fa-inbox"></i>
                <h3>Нет зарегистрированных участников</h3>
                <p>Попробуйте изменить критерии поиска</p>
            </div>
            <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>ФИО</th>
                        <th>Email</th>
                        <th>Телефон</th>
                        <th>Мероприятие</th>
                        <th>Дата</th>
                        <th>Участие</th>
                        <th>Участников</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($registrations as $registration): ?>
                    <tr>
                        <td><?php echo $registration['id']; ?></td>
                        <td><?php echo htmlspecialchars($registration['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($registration['email']); ?></td>
                        <td><?php echo htmlspecialchars($registration['phone']); ?></td>
                        <td><?php echo htmlspecialchars($registration['event_name']); ?></td>
                        <td><?php echo date('d.m.Y', strtotime($registration['event_date'])); ?></td>
                        <td>
                            <span class="status-badge status-<?php echo $registration['participation']; ?>">
                                <?php echo $registration['participation'] == 'online' ? 'Онлайн' : 'Очно'; ?>
                            </span>
                        </td>
                        <td><?php echo $registration['participants']; ?></td>
                        <td>
                            <div class="actions">
                                <button class="action-btn action-view" 
                                        onclick="viewRegistration(<?php echo $registration['id']; ?>)"
                                        title="Просмотр">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <a href="?delete=<?php echo $registration['id']; ?>" 
                                   class="action-btn action-delete"
                                   onclick="return confirm('Удалить запись?')"
                                   title="Удалить">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
        
        <!-- Модальное окно для просмотра деталей -->
        <div id="viewModal" class="modal" style="display: none;">
            <div class="modal-content">
                <button class="modal-close" onclick="closeModal()">
                    <i class="fas fa-times"></i>
                </button>
                <div class="modal-body">
                    <h2>Детали регистрации</h2>
                    <div id="modalContent">
                        <!-- Содержимое будет загружено через AJAX -->
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <script>
        function viewRegistration(id) {
            fetch('view_registration.php?id=' + id)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('modalContent').innerHTML = data;
                    document.getElementById('viewModal').style.display = 'flex';
                    document.body.style.overflow = 'hidden';
                })
                .catch(error => {
                    alert('Ошибка загрузки данных');
                });
        }
        
        function closeModal() {
            document.getElementById('viewModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }
        
        // Закрытие модального окна при клике вне его
        window.onclick = function(event) {
            const modal = document.getElementById('viewModal');
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>
    
    <style>
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            z-index: 2000;
            justify-content: center;
            align-items: center;
        }
        
        .modal-content {
            background-color: white;
            border-radius: 10px;
            width: 90%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
            position: relative;
        }
        
        .modal-close {
            position: absolute;
            top: 15px;
            right: 15px;
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #666;
            cursor: pointer;
            z-index: 10;
        }
        
        .modal-body {
            padding: 30px;
        }
        
        .registration-details p {
            margin: 10px 0;
            padding: 10px;
            background: #f9f9f9;
            border-radius: 5px;
        }
        
        .registration-details strong {
            color: #333;
            display: inline-block;
            width: 150px;
        }
    </style>
</body>
</html>