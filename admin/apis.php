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

// 获取所有分类用于下拉选择
$categories = $pdo->query("SELECT id, name FROM categories ORDER BY name")->fetchAll();

// 处理添加接口
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_api'])) {
    $name = trim($_POST['name'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $url = trim($_POST['url'] ?? '');
    $method = trim($_POST['method'] ?? 'GET');
    $return_format = trim($_POST['return_format'] ?? 'JSON');
    $response_format = trim($_POST['response_format'] ?? 'JSON');
    $status = trim($_POST['status'] ?? 'normal');
    $validStatuses = ['active', 'maintenance'];
if (!in_array($status, $validStatuses)) {
    $status = 'active';
}
$request_example = trim($_POST['request_example'] ?? '');
$request_parameters = $_POST['request_params'] ?? [];
    $response_parameters = $_POST['response_params'] ?? [];

    if (empty($name) || empty($url) || $category_id == 0 || empty($request_example)) {
    $error = '接口名称、URL、分类和请求示例为必填项';
} else {
    // 验证URL格式
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        $error = '接口地址必须是有效的URL格式';
    } else {
        try {
            $pdo->beginTransaction();

            // 插入接口主表
            $stmt = $pdo->prepare("INSERT INTO apis (name, category_id, description, url, request_method, return_format, request_example, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
$stmt->execute([$name, $category_id, $description, $url, $method, $return_format, $request_example, $status]);
            $api_id = $pdo->lastInsertId();

            // 插入请求参数
            if (!empty($request_parameters)) {
                $paramStmt = $pdo->prepare("INSERT INTO request_parameters (api_id, name, type, required, description) VALUES (?, ?, ?, ?, ?)");
                foreach ($request_parameters as $param) {
                    if (!empty($param['name'])) {
                        $paramStmt->execute([
                            $api_id,
                            trim($param['name']),
                            trim($param['type']),
                            isset($param['required']) ? 1 : 0,
                            trim($param['description'])
                        ]);
                    }
                }
            }

            // 插入响应参数
            if (!empty($response_parameters)) {
                $paramStmt = $pdo->prepare("INSERT INTO response_parameters (api_id, name, type, description) VALUES (?, ?, ?, ?)");
                foreach ($response_parameters as $param) {
                    if (!empty($param['name'])) {
                        $paramStmt->execute([
                            $api_id,
                            trim($param['name']),
                            trim($param['type']),
                            trim($param['description'])
                        ]);
                    }
                }
            }

            $pdo->commit();
            $success = '接口添加成功';
            header('Location: apis.php');
            exit;
        } catch(PDOException $e) {
            $pdo->rollBack();
            $error = '添加失败: ' . $e->getMessage();
        }
    }
}
}

// 处理编辑接口
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_api'])) {
    $id = (int)$_POST['id'];
    $name = trim($_POST['name'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $url = trim($_POST['url'] ?? '');
    $method = trim($_POST['method'] ?? 'GET');
    $return_format = trim($_POST['return_format'] ?? 'JSON');
    $response_format = trim($_POST['response_format'] ?? 'JSON');
    $status = trim($_POST['status'] ?? 'active');
$request_example = trim($_POST['request_example'] ?? '');
$request_parameters = $_POST['request_params'] ?? [];
    $response_parameters = $_POST['response_params'] ?? [];

    if (empty($name) || empty($url) || $category_id == 0) {
    $error = '接口名称、URL和分类为必填项';
} else {
    // 验证URL格式
    $urlPattern = '/^(https?:\/\/)?([\da-z\.-]+)\.([a-z\.]{2,6})([\/\w \.-]*)*\/?$/';
    if (!preg_match($urlPattern, $url)) {
        $error = '接口地址格式不正确，请输入有效的URL';
    } else {
        try {
            $pdo->beginTransaction();

            // 更新接口主表
            $stmt = $pdo->prepare("UPDATE apis SET name = ?, category_id = ?, description = ?, url = ?, request_method = ?, return_format = ?, request_example = ?, status = ?, updated_at = NOW() WHERE id = ?");
$stmt->execute([$name, $category_id, $description, $url, $method, $return_format, $request_example, $status, $id]);

            // 删除旧的请求参数
            $pdo->prepare("DELETE FROM request_parameters WHERE api_id = ?")->execute([$id]);
            // 插入新的请求参数
            if (!empty($request_parameters)) {
                $paramStmt = $pdo->prepare("INSERT INTO request_parameters (api_id, name, type, required, description) VALUES (?, ?, ?, ?, ?)");
                foreach ($request_parameters as $param) {
                    if (!empty($param['name'])) {
                        $paramStmt->execute([
                            $id,
                            trim($param['name']),
                            trim($param['type']),
                            isset($param['required']) ? 1 : 0,
                            trim($param['description'] ?? '')
                        ]);
                    }
                }
            }

            // 删除旧的响应参数
            $pdo->prepare("DELETE FROM response_parameters WHERE api_id = ?")->execute([$id]);
            // 插入新的响应参数
            if (!empty($response_parameters)) {
                $paramStmt = $pdo->prepare("INSERT INTO response_parameters (api_id, name, type, description) VALUES (?, ?, ?, ?)");
                foreach ($response_parameters as $param) {
                    if (!empty($param['name'])) {
                        $paramStmt->execute([
                            $id,
                            trim($param['name']),
                            trim($param['type']),
                            trim($param['description'] ?? '')
                        ]);
                    }
                }
            }

            $pdo->commit();
            $success = '接口更新成功';
            header('Location: apis.php');
            exit;
        } catch(PDOException $e) {
            $pdo->rollBack();
            $error = '更新失败: ' . $e->getMessage();
        }
    }
}
}

// 处理删除接口
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    try {
        $pdo->beginTransaction();
        // 删除关联的参数
        $pdo->prepare("DELETE FROM request_parameters WHERE api_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM response_parameters WHERE api_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM call_logs WHERE api_id = ?")->execute([$id]);
        // 删除接口
        $stmt = $pdo->prepare("DELETE FROM apis WHERE id = ?");
        $stmt->execute([$id]);
        $pdo->commit();
        $success = '接口删除成功';
    } catch(PDOException $e) {
        $pdo->rollBack();
        $error = '删除失败: ' . $e->getMessage();
    }
}

// 获取接口列表
$filter_category = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$query = "SELECT a.*, c.name as category_name, COUNT(cl.id) as call_count FROM apis a LEFT JOIN categories c ON a.category_id = c.id LEFT JOIN call_logs cl ON a.id = cl.api_id GROUP BY a.id";
$params = [];
if ($filter_category > 0) {
    $query .= " WHERE a.category_id = ?";
    $params[] = $filter_category;
}
$query .= " ORDER BY a.name";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$apis = $stmt->fetchAll();

// 获取要编辑的接口
$editApi = null;
$editRequestParams = [];
$editResponseParams = [];
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM apis WHERE id = ?");
    $stmt->execute([$id]);
    $editApi = $stmt->fetch();
}

    if ($editApi) {
        // 获取请求参数
        $stmt = $pdo->prepare("SELECT * FROM request_parameters WHERE api_id = ? ORDER BY id");
        $stmt->execute([$id]);
        $editRequestParams = $stmt->fetchAll();

        // 获取响应参数
        $stmt = $pdo->prepare("SELECT * FROM response_parameters WHERE api_id = ? ORDER BY id");
        $stmt->execute([$id]);
        $editResponseParams = $stmt->fetchAll();
    }
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once '../includes/db_connect.php'; $settings = get_settings(); ?>
<title>接口管理 - <?= htmlspecialchars($settings['site_title']) ?></title>
<link rel="icon" href="../<?= htmlspecialchars($settings['site_favicon']) ?>">
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 图标 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/font-awesome@4.7.0/css/font-awesome.min.css">
    <!-- 自定义CSS -->
    <link rel="stylesheet" href="../static/css/admin.css">
    <style>
        .param-row { margin-bottom: 1rem; padding-bottom: 1rem; border-bottom: 1px dashed #eee; }
        .param-row:last-child { border-bottom: none; }
        .method-badge { font-size: 0.8rem; padding: 0.2rem 0.5rem; }
    </style>
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
                            <a class="nav-link active" href="apis.php">
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
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">接口管理</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="apis.php" class="btn btn-sm btn-outline-secondary <?= $filter_category == 0 ? 'active' : '' ?>">所有接口</a>
                        </div>
                        <div class="btn-group me-2">
                            <select class="form-select form-select-sm" id="categoryFilter">
                                <option value="0">所有分类</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= $category['id'] ?>" <?= $filter_category == $category['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($category['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
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
                                <h5 class="mb-0"><?= $editApi ? '编辑接口' : '添加新接口' ?></h5>
                            </div>
                            <div class="card-body">
                                <form method="post" id="apiForm">
                                    <?php if ($editApi): ?>
                                        <input type="hidden" name="id" value="<?= $editApi['id'] ?>">
                                    <?php endif; ?>

                                    <div class="mb-3">
                                        <label for="name" class="form-label">接口名称 <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="name" name="name" required
                                            value="<?= $editApi ? htmlspecialchars($editApi['name']) : '' ?>">
                                    </div>

                                    <div class="mb-3">
                                        <label for="category_id" class="form-label">所属分类 <span class="text-danger">*</span></label>
                                        <select class="form-select" id="category_id" name="category_id" required>
                                            <option value="">-- 选择分类 --</option>
                                            <?php foreach ($categories as $category): ?>
                                                <option value="<?= $category['id'] ?>" <?= $editApi && $editApi['category_id'] == $category['id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($category['name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label for="description" class="form-label">接口描述</label>
                                        <textarea class="form-control" id="description" name="description" rows="3"><?= $editApi ? trim(htmlspecialchars($editApi['description'])) : '' ?></textarea>
                                    </div>

                                    <div class="mb-3">
                                        <label for="url" class="form-label">接口URL <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="url" name="url" required
                                            value="<?= $editApi ? htmlspecialchars($editApi['url']) : '' ?>">
                                    </div>

                                    <div class="mb-3">
                                        <label for="request_example" class="form-label">请求示例 <span class="text-danger">*</span></label>
    <input type="text" class="form-control" id="request_example" name="request_example" required
        value="<?= $editApi ? trim(htmlspecialchars($editApi['request_example'])) : '' ?>">
                                        <div class="form-text">请输入接口的请求示例URL</div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="method" class="form-label">请求方法</label>
                                        <select class="form-select" id="method" name="method">
                                            <option value="GET" <?= (!$editApi || ($editApi['request_method'] ?? '') == 'GET') ? 'selected' : '' ?>>GET</option>
                                            <option value="POST" <?= $editApi && ($editApi['request_method'] ?? '') == 'POST' ? 'selected' : '' ?>>POST</option>
                                            <option value="PUT" <?= $editApi && ($editApi['request_method'] ?? '') == 'PUT' ? 'selected' : '' ?>>PUT</option>
                                            <option value="DELETE" <?= $editApi && ($editApi['request_method'] ?? '') == 'DELETE' ? 'selected' : '' ?>>DELETE</option>
                                            <option value="PATCH" <?= $editApi && ($editApi['request_method'] ?? '') == 'PATCH' ? 'selected' : '' ?>>PATCH</option>
                                        </select>
                                    </div>

                                    <div class="row mb-3">
                                        <div class="col">
                                            <label for="return_format" class="form-label">返回格式</label>
                                            <select class="form-select" id="return_format" name="return_format">
                                                <option value="JSON" <?= (!$editApi || $editApi['return_format'] == 'JSON') ? 'selected' : '' ?>>JSON</option>
                                                <option value="FORM" <?= $editApi && $editApi['return_format'] == 'FORM' ? 'selected' : '' ?>>FORM</option>
                                                <option value="XML" <?= $editApi && $editApi['return_format'] == 'XML' ? 'selected' : '' ?>>XML</option>
                                            </select>
                                        </div>

                                    </div>

                                    <div class="mb-3">
                                        <label for="status" class="form-label">状态</label>
                                        <select class="form-select" id="status" name="status">
                                            <option value="active" <?= (!$editApi || $editApi['status'] == 'active') ? 'selected' : '' ?>>正常</option>
                                            <option value="maintenance" <?= $editApi && $editApi['status'] == 'maintenance' ? 'selected' : '' ?>>维护中</option>
                                        </select>
                                    </div>

                                    <hr>
                                    <h5 class="mb-3">请求参数</h5>
                                    <div id="requestParamsContainer">
                                        <?php if ($editApi && !empty($editRequestParams)): ?>
                                            <?php foreach ($editRequestParams as $i => $param): ?>
                                                <div class="param-row" data-index="<?= $i ?>">
                                                    <div class="row g-3">
                                                        <div class="col-md-4">
                                                            <input type="text" class="form-control" name="request_params[<?= $i ?>][name]" placeholder="参数名" value="<?= htmlspecialchars($param['name']) ?>">
                                                        </div>
                                                        <div class="col-md-2">
                                                            <input type="text" class="form-control" name="request_params[<?= $i ?>][type]" placeholder="类型" value="<?= htmlspecialchars($param['type']) ?>">
                                                        </div>
                                                        <div class="col-md-1 d-flex align-items-center">
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" name="request_params[<?= $i ?>][required]" id="req_required_<?= $i ?>" <?= $param['required'] ? 'checked' : '' ?>>
                                                                <label class="form-check-label" for="req_required_<?= $i ?>">必填</label>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <input type="text" class="form-control" name="request_params[<?= $i ?>][description]" placeholder="描述" value="<?= htmlspecialchars($param['description']) ?>">
                                                        </div>
                                                        <div class="col-md-1">
                                                            <button type="button" class="btn btn-sm btn-danger remove-param"><i class="fa fa-times"></i></button>
                                                        </div>
                                                </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <div class="param-row" data-index="0">
                                                <div class="row g-3">
                                                    <div class="col-md-4">
                                                        <input type="text" class="form-control" name="request_params[0][name]" placeholder="参数名">
                                                    </div>
                                                    <div class="col-md-2">
                                                        <input type="text" class="form-control" name="request_params[0][type]" placeholder="类型">
                                                    </div>
                                                    <div class="col-md-1 d-flex align-items-center">
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" name="request_params[0][required]" id="req_required_0">
                                                            <label class="form-check-label" for="req_required_0">必填</label>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <input type="text" class="form-control" name="request_params[0][description]" placeholder="描述">
                                                    </div>
                                                    <div class="col-md-1">
                                                        <button type="button" class="btn btn-sm btn-danger remove-param"><i class="fa fa-times"></i></button>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-secondary mb-4" id="addRequestParam"><i class="fa fa-plus"></i> 添加参数</button>

                                    <hr>
                                    <h5 class="mb-3">响应参数</h5>
                                    <div id="responseParamsContainer">
                                        <?php if ($editApi && !empty($editResponseParams)): ?>
                                            <?php foreach ($editResponseParams as $i => $param): ?>
                                                <div class="param-row" data-index="<?= $i ?>">
                                                    <div class="row g-3">
                                                        <div class="col-md-4">
                                                            <input type="text" class="form-control" name="response_params[<?= $i ?>][name]" placeholder="参数名" value="<?= htmlspecialchars($param['name']) ?>">
                                                        </div>
                                                        <div class="col-md-7">
                                                            <input type="text" class="form-control" name="response_params[<?= $i ?>][description]" placeholder="描述" value="<?= htmlspecialchars($param['description']) ?>">
                                                        </div>
                                                        <div class="col-md-1">
                                                            <button type="button" class="btn btn-sm btn-danger remove-param"><i class="fa fa-times"></i></button>
                                                        </div>
                                                    </div>


                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <div class="param-row" data-index="0">
                                                <div class="row g-3">
                                                    <div class="col-md-4">
                                                        <input type="text" class="form-control" name="response_params[0][name]" placeholder="参数名">
                                                    </div>
                                                    <div class="col-md-7">
                                                        <input type="text" class="form-control" name="response_params[0][description]" placeholder="描述">
                                                    </div>
                                                    <div class="col-md-1">
                                                        <button type="button" class="btn btn-sm btn-danger remove-param"><i class="fa fa-times"></i></button>
                                                    </div>
                                                </div>


                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-secondary mb-4" id="addResponseParam"><i class="fa fa-plus"></i> 添加参数</button>

                                    <div class="d-grid">
                                        <button type="submit" name="<?= $editApi ? 'edit_api' : 'add_api' ?>" class="btn btn-primary">
                                            <?= $editApi ? '更新接口' : '添加接口' ?>
                                        </button>
                                        <?php if ($editApi): ?>
                                            <a href="apis.php" class="btn btn-secondary mt-2">取消</a>
                                        <?php endif; ?>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">接口列表</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>接口名称</th>
                                                <th>分类</th>
                                                <th>请求方法</th>
                                                <th>状态</th>
                                                <th>调用次数</th>
                                                <th>操作</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($apis)): ?>
                                                <tr>
                                                    <td colspan="7" class="text-center">暂无接口数据</td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($apis as $api): ?>
                                                <tr>
                                                    <td><?= $api['id'] ?></td>
                                                    <td><?= htmlspecialchars($api['name']) ?></td>
                                                    <td><?= htmlspecialchars($api['category_name']) ?></td>
                                                    <td>
                                                        <span class="badge <?= ($api['request_method'] ?? 'GET') == 'GET' ? 'bg-success' : (($api['request_method'] ?? 'GET') == 'POST' ? 'bg-primary' : (($api['request_method'] ?? 'GET') == 'PUT' ? 'bg-warning' : (($api['request_method'] ?? 'GET') == 'DELETE' ? 'bg-danger' : 'bg-secondary'))) ?> method-badge">
                                                            <?= strtoupper($api['request_method'] ?? 'GET') ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="badge <?= $api['status'] == 'active' ? 'bg-success' : ($api['status'] == 'inactive' ? 'bg-secondary' : 'bg-warning') ?>">
                                                            <?= $api['status'] == 'active' ? '正常' : '维护中' ?>
                                                        </span>
                                                    </td>
                                                    <td><?= $api['call_count'] ?></td>
                                                    <td>
                                                        <div class="btn-group" role="group">
                                                            <a href="../api_detail.php?id=<?= $api['id'] ?>" target="_blank" class="btn btn-sm btn-outline-info">
                                                                <i class="fa fa-eye"></i> 查看
                                                            </a>
                                                            <a href="apis.php?edit=<?= $api['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                                <i class="fa fa-edit"></i> 编辑
                                                            </a>
                                                            <a href="apis.php?delete=<?= $api['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('确定要删除该接口吗？相关参数和调用记录也将被删除！');">
                                                                <i class="fa fa-trash"></i> 删除
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
    <script>
        // 分类筛选
        document.getElementById('categoryFilter').addEventListener('change', function() {
            const categoryId = this.value;
            window.location.href = categoryId ? `apis.php?category=${categoryId}` : 'apis.php';
        });

        // 添加请求参数
        document.getElementById('addRequestParam').addEventListener('click', function() {
            const container = document.getElementById('requestParamsContainer');
            const index = container.children.length;
            const paramRow = document.createElement('div');
            paramRow.className = 'param-row';
            paramRow.dataset.index = index;
            paramRow.innerHTML = `
                <div class="row g-3">
                    <div class="col-md-4">
                        <input type="text" class="form-control" name="request_params[${index}][name]" placeholder="参数名">
                    </div>
                    <div class="col-md-2">
                        <input type="text" class="form-control" name="request_params[${index}][type]" placeholder="类型">
                    </div>
                    <div class="col-md-1 d-flex align-items-center">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="request_params[${index}][required]" id="req_required_${index}">
                            <label class="form-check-label" for="req_required_${index}">必填</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <input type="text" class="form-control" name="request_params[${index}][description]" placeholder="描述">
                    </div>
                    <div class="col-md-1">
                        <button type="button" class="btn btn-sm btn-danger remove-param"><i class="fa fa-times"></i></button>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-11 offset-md-1">
                        <input type="text" class="form-control" name="request_params[${index}][example]" placeholder="示例值">
                    </div>
                </div>
            `;
            container.appendChild(paramRow);
            attachRemoveParamEvent(paramRow.querySelector('.remove-param'));
        });

        // 添加响应参数
        document.getElementById('addResponseParam').addEventListener('click', function() {
            const container = document.getElementById('responseParamsContainer');
            const index = container.children.length;
            const paramRow = document.createElement('div');
            paramRow.className = 'param-row';
            paramRow.dataset.index = index;
            paramRow.innerHTML = `
                <div class="row g-3">
                    <div class="col-md-4">
                        <input type="text" class="form-control" name="response_params[${index}][name]" placeholder="参数名">
                    </div>
                    <div class="col-md-7">
                        <input type="text" class="form-control" name="response_params[${index}][description]" placeholder="描述">
                    </div>
                    <div class="col-md-1">
                        <button type="button" class="btn btn-sm btn-danger remove-param"><i class="fa fa-times"></i></button>
                    </div>
                </div>

            `;
            container.appendChild(paramRow);
            attachRemoveParamEvent(paramRow.querySelector('.remove-param'));
        });

        // 删除参数
        function attachRemoveParamEvent(button) {
            button.addEventListener('click', function() {
                this.closest('.param-row').remove();
                // 更新索引
                updateParamIndices('requestParamsContainer', 'request_params');
                updateParamIndices('responseParamsContainer', 'response_params');
            });
        }

        // 更新参数索引
        function updateParamIndices(containerId, paramName) {
            const container = document.getElementById(containerId);
            const rows = container.querySelectorAll('.param-row');
            rows.forEach((row, index) => {
                row.dataset.index = index;
                const inputs = row.querySelectorAll('input, select, textarea');
                inputs.forEach(input => {
                    if (input.name.includes(paramName)) {
                        input.name = input.name.replace(/\[\d+\]/, `[${index}]`);
                        if (input.id) {
                            input.id = input.id.replace(/\d+/, index);
                            const label = row.querySelector(`label[for="${input.id}"]`);
                            if (label) {
                                label.setAttribute('for', input.id);
                            }
                        }
                    }
                });
            });
          }
    </script>
</body>
</html>