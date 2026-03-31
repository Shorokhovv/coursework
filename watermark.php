//watermark.php
<?php
require 'config.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) {
    header('HTTP/1.0 404 Not Found');
    die('Изображение не найдено');
}

$db = getDB();
$stmt = $db->prepare("SELECT filename, mime_type FROM images WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$image = $result->fetch_assoc();

if (!$image) {
    header('HTTP/1.0 404 Not Found');
    die('Изображение не найдено');
}

$filepath = UPLOAD_DIR . $image['filename'];
if (!file_exists($filepath)) {
    header('HTTP/1.0 404 Not Found');
    die('Файл не найден');
}

//Загружаем изображение в зависимости от типа
switch ($image['mime_type']) {
    case 'image/jpeg':
        $src = imagecreatefromjpeg($filepath);
        break;
    case 'image/png':
        $src = imagecreatefrompng($filepath);
        break;
    case 'image/gif':
        $src = imagecreatefromgif($filepath);
        break;
    default:
        header('HTTP/1.0 415 Unsupported Media Type');
        die('Неподдерживаемый формат');
}

// Получаем размеры
$width = imagesx($src);
$height = imagesy($src);

// Создаём новое изображение (для сохранения прозрачности PNG)
$dst = imagecreatetruecolor($width, $height);
imagecopy($dst, $src, 0, 0, 0, 0, $width, $height);

// Определяем цвет текста (белый с полупрозрачностью)
$textColor = imagecolorallocatealpha($dst, 255, 255, 255, 70);

// Настройки текста
$text = WATERMARK_TEXT;
$font = WATERMARK_FONT; //1-5 (встроенный шрифт)
$textWidth = imagefontwidth($font) * strlen($text);
$textHeight = imagefontheight($font);

// Вычисляем позицию
switch (WATERMARK_POSITION) {
    case 'top-left':
        $x = 10;
        $y = 10;
        break;
    case 'top-right':
        $x = $width - $textWidth - 10;
        $y = 10;
        break;
    case 'bottom-left':
        $x = 10;
        $y = $height - $textHeight - 10;
        break;
    case 'bottom-right':
    default:
        $x = $width - $textWidth - 10;
        $y = $height - $textHeight - 10;
        break;
}

// Накладываем текст
imagestring($dst, $font, $x, $y, $text, $textColor);

// Выводим результат
header('Content-Type: ' . $image['mime_type']);
switch ($image['mime_type']) {
    case 'image/jpeg':
        imagejpeg($dst);
        break;
    case 'image/png':
        imagepng($dst);
        break;
    case 'image/gif':
        imagegif($dst);
        break;
}

// Освобождаем память
imagedestroy($src);
imagedestroy($dst);
?>