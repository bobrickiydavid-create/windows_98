<?php
$host = 'localhost';
$user = 'root'; // Стандартный логин в XAMPP
$password = ''; // Стандартный пароль в XAMPP (обычно пустой)
$dbname = 'web_os_db';

// Создаем подключение
$conn = new mysqli($host, $user, $password, $dbname);

// Проверяем, всё ли ок
if ($conn->connect_error) {
    die("Błąd połączenia z bazą danych: " . $conn->connect_error);
}

// Устанавливаем кодировку, чтобы польские буквы отображались без багов
$conn->set_charset("utf8mb4");
?>