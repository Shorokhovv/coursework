<?php
session_start();

//Настройки БД
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'photogallery');

//Настройки водяного знака
define('WATERMARK_TEXT', '© Photographer Name'); //текст водяного знака
define('WATERMARK_FONT', 5);                     //размер шрифта (1-5)
define('WATERMARK_POSITION', 'bottom-right');    //положение: top-left, top-right, bottom-left, bottom-right

//Аутентификация админа (простой пароль, можно хешировать)
define('ADMIN_USER', 'admin');
define('ADMIN_PASS', 'password123'); //измените на сложный

//Пути
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('WATERMARKED_DIR', __DIR__ . '/watermarked/');

//Создаём папки, если их нет
if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
if (!is_dir(WATERMARKED_DIR)) mkdir(WATERMARKED_DIR, 0755, true);

//Подключение к БД
function getDB() {
    static $db = null;
    if ($db === null) {
        $db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME); //!!!ТЕКУЩАЯ ПРОБЛЕМА: ПОДКЛЮЧЕНИЕ К БД
        if ($db->connect_error) {
            die("Ошибка подключения: " . $db->connect_error);
        }
        $db->set_charset("utf8");
    }
    return $db;
}

//Проверка авторизации
function isAdmin() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

function login($password) {
    if ($password === ADMIN_PASS) {
        $_SESSION['admin_logged_in'] = true;
        return true;
    }
    return false;
}

function logout() {
    unset($_SESSION['admin_logged_in']);
    session_destroy();
}

//Функция для безопасного имени файла
function sanitizeFilename($name) {
    $name = pathinfo($name, PATHINFO_FILENAME);
    $name = preg_replace('/[^a-zA-Z0-9_\-]/', '', $name);
    $name = substr($name, 0, 100);
    return $name . '_' . uniqid() . '.' . pathinfo($name, PATHINFO_EXTENSION);
}
?>