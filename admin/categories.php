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

// 处理添加分类
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (empty($name)) {
        $error = '分类名称不能为空';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
            $stmt->execute([$name, $description]);
            $success = '分类添加成功';
            header('Location: categories.php');
            exit;
        } catch(PDOException $e) {
            $error = '添加失败: ' . $e->getMessage();
        }
    }
}

// 处理编辑分类
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_category'])) {
    $id = (int)$_POST['id'];
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (empty($name)) {
        $error = '分类名称不能为空';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE categories SET name = ?, description = ? WHERE id = ?");
            $stmt->execute([$name, $description, $id]);
            $success = '分类更新成功';
            header('Location: categories.php');
            exit;
        } catch(PDOException $e) {
            $error = '更新失败: ' . $e->getMessage();
        }
    }
}

// 处理删除分类
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    try {
        // 先检查该分类下是否有接口
        $checkStmt = $pdo->prepare("SELECT COUNT(*) as count FROM apis WHERE category_id = ?");
        $checkStmt->execute([$id]);
        $count = $checkStmt->fetch()['count'];

        if ($count > 0) {
            $error = '该分类下存在接口，无法删除';
        } else {
            $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([$id]);
            $success = '分类删除成功';
        }
    } catch(PDOException $e) {
        $error = '删除失败: ' . $e->getMessage();
    }
}

// 获取所有分类及对应接口数量
$categories = $pdo->query("SELECT c.*, COUNT(a.id) as api_count FROM categories c LEFT JOIN apis a ON c.id = a.category_id GROUP BY c.id ORDER BY c.name")->fetchAll();

// 获取要编辑的分类
$editCategory = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$id]);
    $editCategory = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once '../includes/db_connect.php'; $settings = get_settings(); ?>
<title>分类管理 - <?= htmlspecialchars($settings['site_title']) ?></title>
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
                            <a class="nav-link" href="index.php">
                                <i class="fa fa-dashboard me-2"></i> 控制台
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="apis.php">
                                <i class="fa fa-list me-2"></i> 接口管理
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="categories.php">
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
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">接口分类管理</h1>
                </div>

                <!-- 消息提示 -->
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= $error ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?= $success ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-4">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0"><?= $editCategory ? '编辑分类' : '添加新分类' ?></h5>
                            </div>
                            <div class="card-body">
                                <form method="post">
                                    <?php if ($editCategory): ?>
                                        <input type="hidden" name="id" value="<?= $editCategory['id'] ?>">
                                    <?php endif; ?>

                                    <div class="mb-3">
                                        <label for="name" class="form-label">分类名称 <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="name" name="name" required
                                            value="<?= $editCategory ? htmlspecialchars($editCategory['name']) : '' ?>">
                                    </div>

                                    <div class="mb-3">
                                        <label for="description" class="form-label">分类描述</label>
                                        <textarea class="form-control" id="description" name="description" rows="3">
                                            <?= $editCategory ? htmlspecialchars($editCategory['description']) : '' ?></textarea>
                                    </div>

                                    <div class="d-grid">
                                        <button type="submit" name="<?= $editCategory ? 'edit_category' : 'add_category' ?>" class="btn btn-primary">
                                            <?= $editCategory ? '更新分类' : '添加分类' ?>
                                        </button>
                                        <?php if ($editCategory): ?>
                                            <a href="categories.php" class="btn btn-secondary mt-2">取消</a>
                                        <?php endif; ?>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">分类列表</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>分类名称</th>
                                                <th>接口数量</th>
                                                <th>操作</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($categories)): ?>
                                                <tr>
                                                    <td colspan="4" class="text-center">暂无分类数据</td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($categories as $category): ?>
                                                <tr>
                                                    <td><?= $category['id'] ?></td>
                                                    <td><?= htmlspecialchars($category['name']) ?></td>
                                                    <td><?= $category['api_count'] ?></td>
                                                    <td>
                                                        <div class="btn-group" role="group">
                                                            <a href="categories.php?edit=<?= $category['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                                <i class="fa fa-edit"></i> 编辑
                                                            </a>
                                                            <a href="categories.php?delete=<?= $category['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('确定要删除该分类吗？');">
                                                                <i class="fa fa-trash"></i> 删除
                                                            </a>
                                                            <a href="apis.php?category=<?= $category['id'] ?>" class="btn btn-sm btn-outline-secondary">
                                                                <i class="fa fa-list"></i> 查看接口
                                                            </a>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
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