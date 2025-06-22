<?php
require_once 'includes/db_connect.php';

// 获取所有分类
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

// 获取所有接口（后续可添加分类筛选）
$apis = $pdo->query("SELECT a.*, c.name as category_name FROM apis a LEFT JOIN categories c ON a.category_id = c.id ORDER BY a.name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once 'includes/db_connect.php'; $settings = get_settings(); ?>
<title><?= htmlspecialchars($settings['site_title']) ?></title>
<meta name="keywords" content="<?= htmlspecialchars($settings['site_keywords']) ?>">
<meta name="description" content="<?= htmlspecialchars($settings['site_description']) ?>">
<?php if (!empty($settings['site_favicon'])): ?>
<link rel="icon" href="upload/<?= htmlspecialchars($settings['site_favicon']) ?>" type="image/ico">
<?php else: ?>
<link rel="icon" href="favicon.ico" type="image/ico">
<?php endif; ?>
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- 自定义CSS -->
    <link rel="stylesheet" href="static/css/style.css">
</head>
<body>
    <!-- 导航栏 -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">API接口管理系统</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">全部接口</a>
                    </li>
                    <?php foreach($categories as $category): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="?category=<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></a>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <div class="navbar-nav">
                    <a href="admin/login.php" class="btn btn-light">后台管理</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- 主要内容区 -->
    <div class="container mt-4">
        <h1 class="mb-4">API接口列表</h1>

        <!-- 接口卡片容器 -->
        <div class="row">
            <?php if(empty($apis)): ?>
                <div class="col-12">
                    <div class="alert alert-info">暂无接口数据，请先在后台添加接口</div>
                </div>
            <?php else: ?>
                <?php foreach($apis as $api): ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($api['name']) ?></h5>
                            <h6 class="card-subtitle mb-2 text-muted"><?= htmlspecialchars($api['category_name']) ?></h6>
                            <p class="card-text"><?= htmlspecialchars(mb_substr($api['description'], 0, 100)) ?>...</p>
                        </div>
                        <div class="card-footer bg-transparent d-flex justify-content-between align-items-center">
                            <small class="text-muted">调用次数: <?= $api['call_count'] ?></small>
                            <span class="badge <?= $api['status'] === 'active' ? 'bg-success' : 'bg-warning' ?>">
                                <?= $api['status'] === 'active' ? '正常' : '维护中' ?>
                            </span>
                        </div>
                        <div class="card-footer bg-transparent pt-0">
                            <a href="api_detail.php?id=<?= $api['id'] ?>" class="btn btn-primary w-100">查看详情</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>