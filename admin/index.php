<?php
// 启用错误报告
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once '../includes/db_connect.php';
session_start();

// 检查登录状态
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// 获取统计数据
// 总接口数量
$totalApis = $pdo->query("SELECT COUNT(*) as count FROM apis")->fetch()['count'];

// 总调用数量
$totalCalls = $pdo->query("SELECT SUM(call_count) as count FROM apis")->fetch()['count'] ?? 0;

// 接口分类统计
$categoryStats = $pdo->query("SELECT c.id, c.name, COUNT(a.id) as api_count FROM categories c LEFT JOIN apis a ON c.id = a.category_id GROUP BY c.id, c.name")->fetchAll();

// 调用排行前5的接口
$topApis = $pdo->query("SELECT a.name, a.call_count FROM apis a ORDER BY a.call_count DESC LIMIT 5")->fetchAll();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once '../includes/db_connect.php'; $settings = get_settings(); ?>
<title><?= htmlspecialchars($settings['site_title']) ?> - 控制台</title>
<link rel="icon" href="../<?= htmlspecialchars($settings['site_favicon']) ?>">
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 图标 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/font-awesome@4.7.0/css/font-awesome.min.css">
    <!-- 自定义CSS -->
    <link rel="stylesheet" href="../static/css/admin.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- 侧边栏 -->
            <div class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="index.php">
                                <i class="fa fa-dashboard me-2"></i> 控制台
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
                            <a class="nav-link" href="settings.php">
                                <i class="fa fa-cog me-2"></i> 系统设置
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- 主内容区 -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <!-- 顶部导航 -->
                <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
                    <div class="container-fluid">
                        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu">
                            <span class="navbar-toggler-icon"></span>
                        </button>
                        <div class="navbar-nav">
                            <span class="nav-item nav-link">欢迎回来, <?= htmlspecialchars($_SESSION['username']) ?></span>
                        </div>
                    </div>
                </nav>

                <!-- 统计卡片 -->
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">仪表盘</h1>
                </div>

                <div class="row">
                    <div class="col-md-6 col-lg-3 mb-4">
                        <div class="card bg-primary text-white h-100">
                            <div class="card-body">
                                <h5 class="card-title">总接口数量</h5>
                                <h2 class="display-4"><?= $totalApis ?></h2>
                            </div>
                            <div class="card-footer d-flex align-items-center justify-content-between">
                                <a href="apis.php" class="text-white">查看详情</a>
                                <i class="fa fa-arrow-circle-right"></i>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-3 mb-4">
                        <div class="card bg-success text-white h-100">
                            <div class="card-body">
                                <h5 class="card-title">总调用次数</h5>
                                <h2 class="display-4"><?= $totalCalls ?></h2>
                            </div>
                            <div class="card-footer d-flex align-items-center justify-content-between">
                                <a href="#" class="text-white">查看详情</a>
                                <i class="fa fa-arrow-circle-right"></i>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-3 mb-4">
                        <div class="card bg-warning text-white h-100">
                            <div class="card-body">
                                <h5 class="card-title">接口分类</h5>
                                <h2 class="display-4"><?= count($categoryStats) ?></h2>
                            </div>
                            <div class="card-footer d-flex align-items-center justify-content-between">
                                <a href="categories.php" class="text-white">查看详情</a>
                                <i class="fa fa-arrow-circle-right"></i>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-3 mb-4">
                        <div class="card bg-danger text-white h-100">
                            <div class="card-body">
                                <h5 class="card-title">维护中接口</h5>
                                <h2 class="display-4">
                                    <?= $pdo->query("SELECT COUNT(*) as count FROM apis WHERE status = 'maintenance'")->fetch()['count'] ?>
                                </h2>
                            </div>
                            <div class="card-footer d-flex align-items-center justify-content-between">
                                <a href="apis.php?status=maintenance" class="text-white">查看详情</a>
                                <i class="fa fa-arrow-circle-right"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 调用排行 -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">接口调用排行</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>排名</th>
                                        <th>接口名称</th>
                                        <th>调用次数</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(empty($topApis)): ?>
                                        <tr><td colspan="3" class="text-center">暂无接口调用数据</td></tr>
                                    <?php else: ?>
                                        <?php foreach($topApis as $index => $api): ?>
                                        <tr>
                                            <td><?= $index + 1 ?></td>
                                            <td><?= htmlspecialchars($api['name']) ?></td>
                                            <td><?= $api['call_count'] ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- 分类统计 -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">接口分类统计</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>分类名称</th>
                                        <th>接口数量</th>
                                        <th>操作</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(empty($categoryStats)): ?>
                                        <tr><td colspan="3" class="text-center">暂无分类数据</td></tr>
                                    <?php else: ?>
                                        <?php foreach($categoryStats as $category): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($category['name']) ?></td>
                                            <td><?= $category['api_count'] ?></td>
                                            <td>
                                                <a href="apis.php?category=<?= $category['id'] ?>" class="btn btn-sm btn-primary">查看接口</a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>