<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$file = $_GET['file'] ?? '';
$file = basename($file);
$file_path = __DIR__ . '/' . $file;

if (!file_exists($file_path)) {
    die('File not found');
}

$content = htmlspecialchars(file_get_contents($file_path));
$file_extension = pathinfo($file, PATHINFO_EXTENSION);
?>
<!DOCTYPE html>
<html>
<head>
    <title>View File: <?= htmlspecialchars($file) ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Courier New', monospace; background: #1e1e1e; color: #d4d4d4; padding: 20px; }
        .header { background: #2c3e50; padding: 15px 20px; margin-bottom: 20px; border-radius: 4px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; }
        .header h1 { color: white; font-size: 20px; }
        .header a { color: #3498db; text-decoration: none; margin-left: 15px; }
        .header a:hover { text-decoration: underline; }
        .file-info { background: #2d2d2d; padding: 10px 15px; margin-bottom: 20px; border-radius: 4px; border-left: 4px solid #3498db; }
        .file-info span { color: #858585; }
        .code-container { background: #1e1e1e; border: 1px solid #333; border-radius: 4px; padding: 20px; overflow-x: auto; }
        .code-container pre { margin: 0; white-space: pre-wrap; word-wrap: break-word; }
        .code-container code { font-size: 14px; line-height: 1.6; }
        .line-numbers { color: #858585; user-select: none; }
        .btn { display: inline-block; padding: 8px 16px; background: #3498db; color: white; text-decoration: none; border-radius: 4px; border: none; cursor: pointer; font-size: 14px; }
        .btn:hover { background: #2980b9; }
        .btn-success { background: #27ae60; }
        .btn-success:hover { background: #229954; }
        .btn-print { background: #8e44ad; }
        .btn-print:hover { background: #7d3c98; }
    </style>
</head>
<body>
    <div class="header">
        <h1>📄 <?= htmlspecialchars($file) ?></h1>
        <div>
            <a href="download_file.php?file=<?= urlencode($file) ?>">📥 Download</a>
            <a href="print_file.php?file=<?= urlencode($file) ?>" target="_blank">🖨️ Print</a>
            <a href="download_files.php">⬅️ Back</a>
        </div>
    </div>
    
    <div class="file-info">
        <span>File:</span> <?= htmlspecialchars($file) ?> &nbsp;|&nbsp; 
        <span>Size:</span> <?= round(filesize($file_path)/1024, 2) ?> KB &nbsp;|&nbsp;
        <span>Type:</span> <?= strtoupper($file_extension) ?>
    </div>
    
    <div class="code-container">
        <pre><code><?= $content ?></code></pre>
    </div>
    
    <div style="margin-top: 20px; display: flex; gap: 10px; flex-wrap: wrap;">
        <button onclick="window.print()" class="btn btn-print">🖨️ Print This File</button>
        <a href="download_file.php?file=<?= urlencode($file) ?>" class="btn">📥 Download</a>
        <a href="download_files.php" class="btn">⬅️ Back to Files</a>
    </div>
</body>
</html>