<?php
// 数据库配置信息
$host = '1Panel-mysql-tnWf';
$dbname = 'api';
$username = 'api';
$password = 'NpRxHtf8QAhhTwkG'; // 请根据实际情况修改密码

try {
    // 创建PDO实例
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch(PDOException $e) {
    // 连接失败处理
    die("数据库连接失败: " . $e->getMessage() . "<br>详细信息: 主机=$host, 数据库名=$dbname, 用户名=$username, 密码=$password");
}

// 获取系统设置
function get_settings() {
    global $pdo;
    $settings = [];
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    return $settings;
}

// 初始化设置（如果表不存在）
function init_settings() {
    global $pdo;
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'settings'");
    if ($tableCheck->rowCount() === 0) {
        $pdo->exec("CREATE TABLE settings (
            id INT PRIMARY KEY AUTO_INCREMENT,
            setting_key VARCHAR(50) NOT NULL UNIQUE,
            setting_value TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        
        $defaultSettings = [
            'site_title' => 'API接口管理系统',
            'site_keywords' => 'API,接口管理,开发',
            'site_description' => '提供API接口管理服务',
            'site_favicon' => 'favicon.ico'
        ];
        
        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)");
        foreach ($defaultSettings as $key => $value) {
            $stmt->execute([$key, $value]);
        }
    }
}

// 初始化设置表
init_settings();
?>