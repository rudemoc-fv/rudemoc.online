<?php
session_start();
require_once 'config.php';
require_once 'functions.php';

$board_id = $_GET['id'] ?? 1;
$board = $db->query("SELECT * FROM boards WHERE id = $board_id")->fetch(PDO::FETCH_ASSOC);

if (!$board) {
    die("* Этот борд не существует! *");
}

// Расчет заполнения памяти
$storage_used = getDirSize(UPLOAD_DIR);
$storage_limit = MAX_STORAGE; // 10 GB из config.php
$storage_percent = min(100, ($storage_used / $storage_limit) * 100);

global $db;
$db -> exec("UPDATE posts SET media_thumb = 'video_placeholder.jpg' WHERE media_type = 'video' AND (media_thumb IS NULL OR media_thumb = '');");
            

// Создание нового треда
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_thread'])) {
    $title = $_POST['title'] ?? '';
    $content = $_POST['content'] ?? '';
    $user_id = generateUserId();
    
    if (!empty($title) && !empty($content)) {
        
            $db->beginTransaction();
            
            $stmt = $db->prepare("INSERT INTO threads (board_id, title) VALUES (?, ?)");
            $stmt->execute([$board_id, $title]);
            $thread_id = $db->lastInsertId();
            
            $media = uploadMedia($_FILES['media'] ?? []);
            $stmt = $db->prepare("INSERT INTO posts (thread_id, user_id, content, media_path, media_type) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$thread_id, $user_id, $content, $media['path'] ?? null, $media['type'] ?? null]);
            
            $db->commit();
        
    } else {
        die("* Введите название и сообщение! *");
    }
}

// Создание ответа
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply'])) {
    $thread_id = $_POST['thread_id'] ?? 0;
    $parent_id = $_POST['parent_id'] ?? null;
    $content = $_POST['content'] ?? '';
    $user_id = generateUserId();
    
    if (!empty($content) && $thread_id) {
    
        $media = uploadMedia($_FILES['media'] ?? []);
        $stmt = $db->prepare("INSERT INTO posts (thread_id, parent_id, user_id, content, media_path, media_type) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$thread_id, $parent_id, $user_id, $content, $media['path'] ?? null, $media['type'] ?? null]);
        
    }

}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Rudemoc - /<?php echo htmlspecialchars($board['name']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="icon" type="image/x-icon" href="/photo_2025-03-10_15-32-12.jpg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <script>
        function toggleReplies(postId) {
            const replies = document.getElementById('replies-' + postId);
            const toggleBtn = document.getElementById('toggle-' + postId);
            if (replies.style.display === 'none') {
                replies.style.display = 'block';
                toggleBtn.textContent = '* Спрятать ответы *';
            } else {
                replies.style.display = 'none';
                toggleBtn.textContent = '* Показать ответы *';
            }
        }
            </script>
</head>
<body>

    <!-- Полоска заполнения памяти -->
    <div class="storage-bar">
        <div class="storage-fill" style="width: <?php echo $storage_percent; ?>%;"></div>
        <span class="storage-text">* Память: <?php echo round($storage_used / (1024 * 1024), 2); ?> MB / <?php echo round($storage_limit / (1024 * 1024), 2); ?> MB (<?php echo round($storage_percent, 2); ?>%) *</span>
    </div>

    <div class="container">
        <h1 class="undertale-title">* /<?php echo htmlspecialchars($board['name']); ?> *</h1>
        <p><?php echo htmlspecialchars($board['description']); ?></p>
        
        <!-- Форма создания треда -->
        <div class="thread-creation">
            <h2>* Начать трэд *</h2>
            <form method="POST" enctype="multipart/form-data" class="post-form mobile-optimized">
                <input type="text" name="title" placeholder="* Название" required><br>
                <textarea name="content" placeholder="* Сообщение" required></textarea><br>
                <input type="file" name="media" accept="image/*,audio/*,video/*"><br>
                <button type="submit" name="new_thread">* СДЕЛАТЬ ТРЭД!</button>
            </form>
        </div>
        
        <!-- Список тредов -->
        <div class="threads">
            <?php
            $threads = getThreads($board_id);
            if (empty($threads)) {
                echo "<p>* Нет трэдов! Стань первым! *</p>";
            }
            foreach ($threads as $thread) {
                echo "<div class='thread'>";
                echo "<h2>" . htmlspecialchars($thread['title']) . "</h2>";
                
                $posts = getPosts($thread['id']);
                $postTree = buildPostTree($posts);
                
                foreach ($postTree as $post) {
                    displayPost($post, $thread['id'], 0);
                }
                echo "</div>";
            }
            ?>
        </div>
    </div>
    <script>
function loadVideo(img) {
    const container = img.parentElement;
    const videoPath = container.getAttribute('data-video');
    const video = document.createElement('video');
    video.controls = true;
    video.src = videoPath;
    video.className = 'media';
    container.innerHTML = '';
    container.appendChild(video);
    video.play();
    
    const closeBtn = document.createElement('button');
    closeBtn.textContent = '* Закрыть видео *';
    closeBtn.className = 'toggle-btn';
    closeBtn.onclick = function() {
        video.pause();
        video.src = ''; // Выгружаем видео из памяти
        container.innerHTML = '';
        const newImg = document.createElement('img');
        newImg.src = img.src; // Возвращаем заглушку
        newImg.className = 'media';
        newImg.onclick = function() { loadVideo(newImg); };
        container.appendChild(newImg);
    };
    container.appendChild(closeBtn);
}

function openFullImage(path) {
    window.open(path, '_blank');
}

document.addEventListener('DOMContentLoaded', () => {
    // Инициализация всех аудиоплееров
    document.querySelectorAll('.undertale-audio').forEach(container => {
        const audio = container.querySelector('audio');
        const playBtn = container.querySelector('.play-btn');
        const rewindBtn = container.querySelector('.rewind-btn');
        const forwardBtn = container.querySelector('.forward-btn');
        const progressBar = container.querySelector('.progress-bar');
        const currentTime = container.querySelector('.current-time');
        const duration = container.querySelector('.duration');

        // Инициализация времени
        audio.addEventListener('loadedmetadata', () => {
            duration.textContent = formatTime(audio.duration);
        });

        // Обработчик времени
        audio.addEventListener('timeupdate', () => {
            currentTime.textContent = formatTime(audio.currentTime);
            const progress = (audio.currentTime / audio.duration) * 100;
            progressBar.style.width = `${progress}%`;
        });

        // Кнопка воспроизведения
        playBtn.addEventListener('click', () => {
            if(audio.paused) {
                audio.play();
                playBtn.innerHTML = '❚❚';
            } else {
                audio.pause();
                playBtn.innerHTML = '▶';
            }
        });

        // Перемотка назад
        rewindBtn.addEventListener('click', () => {
            audio.currentTime = Math.max(0, audio.currentTime - 10);
        });

        // Перемотка вперед
        forwardBtn.addEventListener('click', () => {
            audio.currentTime = Math.min(audio.duration, audio.currentTime + 10);
        });

        // Перетаскивание прогресс-бара
        let isDragging = false;
        const progressContainer = container.querySelector('.progress-container');
        
        progressContainer.addEventListener('mousedown', (e) => {
            isDragging = true;
            updateProgress(e);
        });

        document.addEventListener('mousemove', (e) => {
            if(isDragging) updateProgress(e);
        });

        document.addEventListener('mouseup', () => {
            isDragging = false;
        });

        function updateProgress(e) {
            const rect = progressContainer.getBoundingClientRect();
            const pos = (e.clientX - rect.left) / rect.width;
            audio.currentTime = pos * audio.duration;
        }
    });

    // Форматирование времени
    function formatTime(seconds) {
        const mins = Math.floor(seconds / 60);
        const secs = Math.floor(seconds % 60);
        return `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
    }
     // Управление с клавиатуры
    document.addEventListener('keydown', (e) => {
        const activeAudio = document.querySelector('.undertale-audio:hover audio');
        if(!activeAudio) return;

        switch(e.key) {
            case 'ArrowLeft':
                activeAudio.currentTime -= 5;
                break;
            case 'ArrowRight':
                activeAudio.currentTime += 5;
                break;
            case ' ':
                activeAudio.paused ? activeAudio.play() : activeAudio.pause();
                e.preventDefault();
                break;
        }
    });
});
</script>
</body>
</html>

<?php
function displayPost($post, $thread_id, $level) {
    $indent = $level * 20;
    echo "<div class='post' style='margin-left: {$indent}px;'>";
    echo "<span class='user-id'>ID: " . $post['user_id'] . "</span> ";
    echo "<span class='time'>" . $post['created_at'] . "</span><br>";
    
    if ($post['media_path']) {
        $path = UPLOAD_DIR . $post['media_path'];
        // Если есть media_thumb, используем его, иначе используем путь к файлу
        $thumbPath = !empty($post['media_thumb']) ? UPLOAD_DIR . $post['media_thumb'] : $path;
        
        switch ($post['media_type']) {
            case 'image':
            case 'gif':
                echo "<img src='$thumbPath' class='media' onclick='openFullImage(\"$path\")'>";
                break;
         case 'audio':
    echo '<div class="undertale-audio">';
    echo '<audio src="'.$path.'"></audio>';
    echo '<div class="custom-controls">';
    echo '<button class="player-btn rewind-btn">«</button>';
    echo '<button class="player-btn play-btn">▶</button>';
    echo '<button class="player-btn forward-btn">»</button>';
    echo '<div class="progress-container">';
    echo '<div class="progress-bar"></div>';
    echo '</div>';
    echo '</div>';
    echo '<div class="time-display">';
    echo '<span class="current-time">00:00</span> / ';
    echo '<span class="duration">00:00</span>';
    echo '</div>';
    echo '</div>';
    break;
            case 'video':
                echo "<div class='video-container' data-video='$path'>";
                echo "<img src='$thumbPath' class='media' onclick='loadVideo(this)'>";
                echo "</div>";
                break;
        }
    }
    echo "<p>" . htmlspecialchars($post['content']) . "</p>";
    
    // Форма ответа и остальной код остаются без изменений
    echo "<form method='POST' enctype='multipart/form-data' class='reply-form'>";
    echo "<input type='hidden' name='thread_id' value='$thread_id'>";
    echo "<input type='hidden' name='parent_id' value='{$post['id']}'>";
    echo "<textarea name='content' placeholder='* Ответ...' required></textarea>";
    echo "<input type='file' name='media' accept='image/*,audio/*,video/*'>";
    echo "<button type='submit' name='reply'>* ОТВЕТИТЬ!</button>";
    echo "</form>";
    
    if (!empty($post['replies'])) {
        echo "<button class='toggle-btn' id='toggle-{$post['id']}' onclick='toggleReplies({$post['id']})'>* Показать ответы *</button>";
        echo "<div class='replies' id='replies-{$post['id']}' style='display: none;'>";
        foreach ($post['replies'] as $reply) {
            displayPost($reply, $thread_id, $level + 1);
        }
        echo "</div>";
    }
    echo "</div>";
}
?>