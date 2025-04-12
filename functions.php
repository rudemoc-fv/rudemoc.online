<?php
require_once 'config.php'; // Подключаем конфигурацию базы данных

// Получение списка борд
function getBoards() {
    global $db;
    $stmt = $db->query("SELECT * FROM boards");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Получение списка тредов для борды
function getThreads($board_id) {
    global $db;
    $stmt = $db->prepare("SELECT * FROM threads WHERE board_id = ? ORDER BY created_at DESC");
    $stmt->execute([$board_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Получение всех постов для треда
function getPosts($thread_id) {
    global $db;
    $stmt = $db->prepare("SELECT * FROM posts WHERE thread_id = ? ORDER BY created_at ASC");
    $stmt->execute([$thread_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Генерация анонимного ID пользователя
function generateUserId() {
    return md5($_SERVER['REMOTE_ADDR'] . time());
}


function uploadMedia($file) {
    

    if ($file['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'png', 'jpeg', 'gif', 'mp3', 'mp4', 'webm'];
        
        if (in_array($ext, $allowed)) {
            $filename = uniqid() . '.' . $ext;
            $uploadPath = UPLOAD_DIR . $filename;
            move_uploaded_file($file['tmp_name'], $uploadPath);
            
            
            
            $result = ['path' => $filename, 'type' => getMediaType($ext), 'thumb' => 'video_placeholder.jpg'];
             // Указываем имя файла заглушки
            global $db;
            $db -> exec("UPDATE posts SET media_thumb = 'video_placeholder.jpg' WHERE media_type = 'video' AND (media_thumb IS NULL OR media_thumb = '');");
            
            return $result;
        }
    }
    return false;
}

function getMediaType($ext) {
    switch ($ext) {
        case 'jpg':
            return 'image';
        case 'png':
            return 'image';
        case 'gif':
            return 'image';
        case 'jpeg':
            return 'image';
        case 'mp3':
            return 'audio';
        case 'mp4':
            return 'video';
        case 'webm':
            return 'video';
        default:
            return 'unknown';
    }
}

function createThumbnail($source, $destination, $width) {
    list($origWidth, $origHeight) = getimagesize($source);
    $ratio = $origWidth / $origHeight;
    $height = $width / $ratio;
    
    $thumb = imagecreatetruecolor($width, $height);
    $sourceImage = imagecreatefromstring(file_get_contents($source));
    imagecopyresampled($thumb, $sourceImage, 0, 0, 0, 0, $width, $height, $origWidth, $origHeight);
    imagejpeg($thumb, $destination, 80);
    imagedestroy($thumb);
    imagedestroy($sourceImage);
}

// Проверка лимита хранения (10 ГБ)
function checkStorageLimit() {
    $size = getDirSize(UPLOAD_DIR);
    if ($size >= MAX_STORAGE) {
        global $db;
        $db->exec("DELETE FROM posts");
        $db->exec("DELETE FROM threads");
        array_map('unlink', glob(UPLOAD_DIR . '*'));
    }
}

// Получение размера директории
function getDirSize($dir) {
    $size = 0;
    foreach (glob(rtrim($dir, '/') . '/*', GLOB_NOSORT) as $file) {
        $size += is_file($file) ? filesize($file) : getDirSize($file);
    }
    return $size;
}

// Построение дерева постов (для отображения ответов)
function buildPostTree($posts) {
    $tree = [];
    $map = [];
    
    // Собираем посты в массив по ID
    foreach ($posts as $post) {
        $post['replies'] = [];
        $map[$post['id']] = $post;
    }
    
    // Строим дерево
    foreach ($posts as $post) {
        // Проверяем наличие parent_id, если его нет, считаем пост корневым
        $parent_id = isset($post['parent_id']) ? $post['parent_id'] : null;
        if ($parent_id === null) {
            $tree[] = &$map[$post['id']];
        } else if (isset($map[$parent_id])) {
            $map[$parent_id]['replies'][] = &$map[$post['id']];
        }
    }
    
    return $tree;
}
?>