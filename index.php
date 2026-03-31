<?php
require 'config.php';
$db = getDB();
$result = $db->query("SELECT id, original_name FROM images ORDER BY uploaded_at DESC");
$images = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Галерея фотографа</title>
    <style>
        body { font-family: system-ui; margin: 2rem; background: #fafafa; }
        .container { max-width: 1200px; margin: 0 auto; }
        h1 { text-align: center; margin-bottom: 2rem; }
        .gallery { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 1.5rem; }
        .card { background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 8px rgba(0,0,0,0.1); transition: transform 0.2s; }
        .card:hover { transform: translateY(-5px); }
        .card img { width: 100%; height: 200px; object-fit: cover; display: block; }
        .card .caption { padding: 0.75rem; text-align: center; font-size: 0.9rem; color: #555; }
    </style>
</head>
<body>
<div class="container">
    <h1>Портфолио фотографа</h1>
    <div class="gallery">
        <?php foreach ($images as $img): ?>
        <div class="card">
            <a href="watermark.php?id=<?= $img['id'] ?>" target="_blank">
                <img src="watermark.php?id=<?= $img['id'] ?>" alt="<?= htmlspecialchars($img['original_name']) ?>">
            </a>
            <div class="caption"><?= htmlspecialchars($img['original_name']) ?></div>
        </div>
        <?php endforeach; ?>
        <?php if (empty($images)) echo '<p>Пока нет фотографий. Зайдите в админку, чтобы добавить.</p>'; ?>
    </div>
</div>
</body>
</html>