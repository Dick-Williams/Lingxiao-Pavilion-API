<?php
require_once '../includes/db_connect.php';
session_start();

// 检查管理员登录状态
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// 初始化设置数组
$settings = [
    'site_title' => '',
    'site_keywords' => '',
    'site_description' => '',
    'site_favicon' => ''
];

// 检查设置表是否存在，不存在则创建
$tableCheck = $pdo->query("SHOW TABLES LIKE 'settings'");
if ($tableCheck->rowCount() === 0) {
    $pdo->exec("CREATE TABLE settings (
        id INT PRIMARY KEY AUTO_INCREMENT,
        setting_key VARCHAR(50) NOT NULL UNIQUE,
        setting_value TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    
    // 插入默认设置
    $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)");
    foreach ($settings as $key => $value) {
        $stmt->execute([$key, $value]);
    }
}

// 获取当前设置
$stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 准备更新语句
    $updateableSettings = ['site_title', 'site_keywords', 'site_description'];
    $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
    
    // 处理网站图标上传
    if (isset($_FILES['site_favicon']) && $_FILES['site_favicon']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/x-icon', 'image/png', 'image/jpeg'];
        $fileType = mime_content_type($_FILES['site_favicon']['tmp_name']);
        
        if (in_array($fileType, $allowedTypes)) {
            $uploadDir = '../upload/';
        // 确保上传目录存在
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $fileName = 'custom-icon.ico'; // 使用自定义图标文件名
        $uploadPath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['site_favicon']['tmp_name'], $uploadPath)) {
                $settings['site_favicon'] = $fileName;
                // 更新数据库中的网站图标设置
                $stmt->execute([$fileName, 'site_favicon']);
            } else {
                $error = '文件上传失败，请检查目录权限';
            }
        }
    }
    
    // 更新文本设置

    
    foreach ($updateableSettings as $key) {
        if (isset($_POST[$key])) {
            $stmt->execute([$_POST[$key], $key]);
            $settings[$key] = $_POST[$key];
        }
    }
    
    $success = true;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>系统设置 - API接口管理系统</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../static/css/admin.css">
    <?php if (!empty($settings['site_favicon'])): ?>
    <link rel="icon" href="../upload/<?= htmlspecialchars($settings['site_favicon']) ?>" type="image/ico">
    <?php endif; ?>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- 侧边栏 -->
            <div class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="index.php">
                                <i class="fa fa-tachometer-alt me-2"></i> 控制台
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="apis.php">
                                <i class="fa fa-list me-2"></i> 接口管理
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="categories.php">
                                <i class="fa fa-folder me-2"></i> 分类管理
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="settings.php">
                                <i class="fa fa-cog me-2"></i> 系统设置
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- 主内容区 -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">系统设置</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="login.php?action=logout" class="btn btn-sm btn-outline-secondary">
                            <i class="fa fa-sign-out-alt me-1"></i> 退出登录
                        </a>
                    </div>
                </div>

                <?php if (isset($success)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    设置已成功更新！
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>

                <?php if (isset($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= $error ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">网站基本设置</h5>
                    </div>
                    <div class="card-body">
                        <form method="post" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="site_title" class="form-label">网站标题</label>
                                <input type="text" class="form-control" id="site_title" name="site_title" value="<?= htmlspecialchars($settings['site_title']) ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="site_keywords" class="form-label">网站关键词</label>
                                <input type="text" class="form-control" id="site_keywords" name="site_keywords" value="<?= htmlspecialchars($settings['site_keywords']) ?>">
                                <div class="form-text">多个关键词用英文逗号分隔</div>
                            </div>

                            <div class="mb-3">
                                <label for="site_description" class="form-label">网站描述</label>
                                <textarea class="form-control" id="site_description" name="site_description" rows="3"><?= htmlspecialchars($settings['site_description']) ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="site_favicon" class="form-label">网站图标</label>
                                <input class="form-control" type="file" id="site_favicon" name="site_favicon" accept=".ico,.png,.jpg,.jpeg">
                                <div class="form-text">推荐尺寸: 32x32px，支持ICO、PNG、JPG格式</div>
                                <?php if (!empty($settings['site_favicon'])): ?>
                                <div class="mt-2">
                                    <img src="../upload/<?= htmlspecialchars($settings['site_favicon']) ?>" alt="当前图标" style="width: 32px; height: 32px;">
                                </div>
                                <?php endif; ?>
                            </div>

                            <button type="submit" class="btn btn-primary">保存设置</button>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</body>
</html>