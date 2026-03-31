<?php
require 'config.php';

//Обработка входа
if (isset($_POST['login'])) {
    $password = $_POST['password'] ?? '';
    if (login($password)) {
        header('Location: admin.php');
        exit;
    } else {
        $error = 'Неверный пароль';
    }
}

// Выход
if (isset($_GET['logout'])) {
    logout();
    header('Location: admin.php');
    exit;
}

// Если не авторизован, показываем форму входа
if (!isAdmin()) {
    ?>
    <!DOCTYPE html>
    <html>
    <head><title>Вход в админку</title></head>
    <body>
        <h1>Вход</h1>
        <?php if (isset($error)) echo "<p style='color:red'>$error</p>"; ?>
        <form method="post">
            <input type="password" name="password" placeholder="Пароль">
            <button type="submit" name="login">Войти</button>
        </form>
    </body>
    </html>
    <?php
    exit;
}

// Загрузка изображения
if (isset($_POST['upload']) && isset($_FILES['image'])) {
    $file = $_FILES['image'];
    if ($file['error'] === UPLOAD_ERR_OK) {
        $allowed = ['image/jpeg', 'image/png', 'image/gif'];
        if (in_array($file['type'], $allowed) && $file['size'] <= 10 * 1024 * 1024) {
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $newFilename = uniqid() . '.' . $ext;
            $destination = UPLOAD_DIR . $newFilename;
            if (move_uploaded_file($file['tmp_name'], $destination)) {
                $db = getDB();
                $stmt = $db->prepare("INSERT INTO images (filename, original_name, mime_type, size) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("sssi", $newFilename, $file['name'], $file['type'], $file['size']);
                $stmt->execute();
                $success = 'Файл загружен';
            } else {
                $error = 'Ошибка сохранения файла';
            }
        } else {
            $error = 'Недопустимый формат или размер (макс. 10 МБ, JPEG/PNG/GIF)';
        }
    } else {
        $error = 'Ошибка загрузки файла';
    }
}

// Удаление изображения
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $db = getDB();
    $stmt = $db->prepare("SELECT filename FROM images WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $filename = $row['filename'];
        @unlink(UPLOAD_DIR . $filename);
        // Удаляем кэшированные файлы, если есть
        @unlink(WATERMARKED_DIR . $filename);
        $delStmt = $db->prepare("DELETE FROM images WHERE id = ?");
        $delStmt->bind_param("i", $id);
        $delStmt->execute();
        $success = 'Изображение удалено';
    } else {
        $error = 'Изображение не найдено';
    }
    header('Location: admin.php');
    exit;
}

// Получение списка изображений
$db = getDB();
$result = $db->query("SELECT id, original_name, filename, size, uploaded_at FROM images ORDER BY uploaded_at DESC");
$images = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Админ-панель</title>
    <style>
        body { font-family: system-ui; margin: 2rem; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; }
        h1 { margin-bottom: 1rem; }
        .form { background: white; padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .gallery { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1rem; }
        .card { background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .card img { width: 100%; height: 150px; object-fit: cover; display: block; }
        .card .info { padding: 0.75rem; }
        .card .name { font-weight: bold; margin-bottom: 0.25rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .card .meta { font-size: 0.8rem; color: #666; margin-bottom: 0.5rem; }
        .delete-btn { background: #dc3545; color: white; border: none; padding: 0.25rem 0.5rem; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; font-size: 0.8rem; }
        .delete-btn:hover { background: #c82333; }
        .alert { padding: 0.75rem; border-radius: 4px; margin-bottom: 1rem; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .logout { float: right; }
    </style>
</head>
<body>
<div class="container">
    <h1>Админ-панель <a href="?logout" class="logout">Выйти</a></h1>

    <?php if (isset($success)) echo "<div class='alert alert-success'>$success</div>"; ?>
    <?php if (isset($error)) echo "<div class='alert alert-error'>$error</div>"; ?>

    <div class="form">
        <h2>Загрузить новое изображение</h2>
        <form method="post" enctype="multipart/form-data">
            <input type="file" name="image" accept="image/jpeg,image/png,image/gif" required>
            <button type="submit" name="upload">Загрузить</button>
        </form>
    </div>

    <h2>Загруженные изображения</h2>
    <div class="gallery">
        <?php foreach ($images as $img): ?>
        <div class="card">
            <a href="watermark.php?id=<?= $img['id'] ?>" target="_blank">
                <img src="watermark.php?id=<?= $img['id'] ?>" alt="<?= htmlspecialchars($img['original_name']) ?>">
            </a>
            <div class="info">
                <div class="name" title="<?= htmlspecialchars($img['original_name']) ?>"><?= htmlspecialchars($img['original_name']) ?></div>
                <div class="meta"><?= round($img['size'] / 1024) ?> КБ</div>
                <a href="?delete=<?= $img['id'] ?>" class="delete-btn" onclick="return confirm('Удалить изображение?')">Удалить</a>
            </div>
        </div>
        <?php endforeach; ?>
        <?php if (empty($images)) echo '<p>Нет загруженных изображений.</p>'; ?>
    </div>
</div>
</body>
</html>