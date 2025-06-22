<?php
require_once 'includes/db_connect.php';

// 检查是否提供了API ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("无效的API ID");
}
$apiId = (int)$_GET['id'];

// 获取API详情
$stmt = $pdo->prepare("SELECT a.*, c.name as category_name FROM apis a LEFT JOIN categories c ON a.category_id = c.id WHERE a.id = ?");
$stmt->execute([$apiId]);
$api = $stmt->fetch();

if (!$api) {
    die("未找到指定的API");
}

// 获取请求参数
$requestParams = $pdo->prepare("SELECT * FROM request_parameters WHERE api_id = ? ORDER BY id");
$requestParams->execute([$apiId]);
$requestParams = $requestParams->fetchAll();

// 获取返回参数
$responseParams = $pdo->prepare("SELECT * FROM response_parameters WHERE api_id = ? ORDER BY id");
$responseParams->execute([$apiId]);
$responseParams = $responseParams->fetchAll();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php $settings = get_settings(); ?>
<title><?= htmlspecialchars($api['name']) ?> - <?= htmlspecialchars($settings['site_title']) ?></title>
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
                        <a class="nav-link" href="index.php">返回接口列表</a>
                    </li>
                </ul>
                <div class="navbar-nav">
                    <a href="admin/login.php" class="btn btn-light">后台管理</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- 主要内容区 -->
    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h2 class="mb-0"><?= htmlspecialchars($api['name']) ?></h2>
                    </div>
                    <div class="card-body">
                        <div class="mb-4">
                            <h5><strong>接口描述：</strong></h5>
                            <p><?= nl2br(htmlspecialchars($api['description'])) ?></p>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-4">
                                <h5><strong>接口地址：</strong></h5>
                                <p><?= htmlspecialchars($api['url']) ?></p>
                            </div>
                            <div class="col-md-2">
                                <h5><strong>请求方式：</strong></h5>
                                <p><span class="badge bg-secondary"><?= htmlspecialchars($api['request_method']) ?></span></p>
                            </div>
                            <div class="col-md-2">
                                <h5><strong>返回格式：</strong></h5>
                                <p><span class="badge bg-secondary"><?= htmlspecialchars($api['return_format']) ?></span></p>
                            </div>
                            <div class="col-md-2">
                                <h5><strong>接口状态：</strong></h5>
                                <p><span class="badge <?= $api['status'] === 'active' ? 'bg-success' : 'bg-warning' ?>">
                                    <?= $api['status'] === 'active' ? '正常' : '维护中' ?>
                                </span></p>
                            </div>
                            <div class="col-md-2">
                                <h5><strong>调用次数：</strong></h5>
                                <p><?= $api['call_count'] ?></p>
                            </div>
                        </div>

                        <div class="mb-4">
                            <h5><strong>请求示例：</strong></h5>
                            <pre class="bg-light p-3 rounded"><code id="requestExample"><?= htmlspecialchars($api['request_example']) ?></code></pre>
                        </div>

                        <div class="mb-4">
                            <h5><strong>请求参数说明：</strong></h5>
                            <?php if(empty($requestParams)): ?>
                                <p>无请求参数</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th>参数名称</th>
                                                <th>必填</th>
                                                <th>类型</th>
                                                <th>说明</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($requestParams as $param): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($param['name']) ?></td>
                                                <td><?= $param['required'] ? '<span class="text-danger">是</span>' : '否' ?></td>
                                                <td><?= htmlspecialchars($param['type']) ?></td>
                                                <td><?= htmlspecialchars($param['description']) ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-4">
                            <h5><strong>返回参数说明：</strong></h5>
                            <?php if(empty($responseParams)): ?>
                                <p>无返回参数</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th>参数名称</th>
                                                <th>说明</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($responseParams as $param): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($param['name']) ?></td>
                                                <td><?= htmlspecialchars($param['description']) ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-4">
                            <h5><strong>返回示例：</strong></h5>
                            <pre class="bg-light p-3 rounded"><code id="responseExample"><?= htmlspecialchars($api['response_example']) ?></code></pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const requestUrl = document.getElementById('requestExample').textContent.trim();
    const responseElement = document.getElementById('responseExample');
    
    if (requestUrl) {
        fetch(requestUrl)
                    .then(response => {
                        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                        return response.text();
                    })
                    .then(text => {
                        try {
                            // 尝试解析JSON
                            const data = JSON.parse(text);
                            return JSON.stringify(data, null, 2);
                        } catch (e) {
                            // 解析失败则直接返回文本
                            return text;
                        }
                    })
                    .then(formatted => {
                        responseElement.textContent = formatted;
                    })
                    .catch(error => {
                        responseElement.textContent = `获取返回示例失败: ${error.message}`;
                    });
    }
});
</script>
</body>
</html>