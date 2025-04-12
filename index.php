<?php
session_start();
require_once 'config.php';
require_once 'functions.php';


// Проверка размера данных
checkStorageLimit();

// Расчет заполнения памяти
$storage_used = getDirSize(UPLOAD_DIR);
$storage_limit = MAX_STORAGE; // 10 GB из config.php
$storage_percent = min(100, ($storage_used / $storage_limit) * 100);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_board'])) {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    
    if (!empty($name)) {
        $stmt = $db->prepare("INSERT INTO boards (name, description) VALUES (?, ?)");
        $stmt->execute([$name, $description]);
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Rudemoc - * FOX ATE FROG *</title>
     <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="icon" type="image/x-icon" href="/photo_2025-03-10_15-32-12.jpg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <style>
    .gif-link:hover img {
        opacity: 0.8; /* Эффект при наведении */
        transform: scale(1.02); /* Увеличение */
        transition: 0.3s;
    }
</style>
</head>
<body>
    
    <!-- Полоска заполнения памяти -->
    <div class="storage-bar">
        <div class="storage-fill" style="width: <?php echo $storage_percent; ?>%;"></div>
        <span class="storage-text">* Storage: <?php echo round($storage_used / (1024 * 1024), 2); ?> MB / <?php echo round($storage_limit / (1024 * 1024), 2); ?> MB (<?php echo round($storage_percent, 2); ?>%) *</span>
    </div>
    
    
        <h2 class="undertale-title">Федотов Владислав Игоревич</h2>
        
        <div class="container">
        
        <div class="container" align="center">
        <h1>♥ ПОДДЕРЖИТЕ ПРОЕКТ ♥</h1>
    </div>
    <div class="container" align="center">
            <a href="https://www.donationalerts.com/r/rudemoc" class="gif-link">
                
                <img 
                src="https://media1.tenor.com/m/avmjJ3TW6YkAAAAC/undertale-frisk.gif" 
                alt="Анимированная кнопка"
                width=360px
                height=200px
                >
                    
            </a>
            
        </div>
        <h1 class="undertale-title">* Rudemoc *</h1>
        <img src="https://media.tenor.com/5EeA2C8aoVwAAAAi/uhhh-sweating.gif">
        
        <!-- Список борд -->
        <div class="boards">
            <?php
            $boards = getBoards();
            if (empty($boards)) {
                echo "<p>* No boards yet! Create one! *</p>";
            } else {
                foreach ($boards as $board) {
                    echo "<a href='board.php?id={$board['id']}' class='board-link'>/{$board['name']}/ - {$board['description']}</a><br>";
                }
            }
            ?>
        </div>
    </div>
</body>
</html>