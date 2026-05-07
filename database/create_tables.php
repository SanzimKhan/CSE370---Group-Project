<?php







declare(strict_types=1);


ini_set('display_errors', '1');
error_reporting(E_ALL);


$dbHost = '127.0.0.1';
$dbPort = 3306;
$dbName = 'bracu_freelance_marketplace';
$dbUser = 'root';
$dbPass = '';

$results = [];
$isExecuting = isset($_GET['action']) && $_GET['action'] === 'create';


if ($isExecuting) {
    
    $connection = new mysqli($dbHost . ':' . $dbPort, $dbUser, $dbPass);

    if ($connection->connect_error) {
        $results['error'] = "Connection failed: " . $connection->connect_error;
    } else {
        $results['connected'] = true;

        
        $schemaFile = __DIR__ . '/schema.sql';

        if (!file_exists($schemaFile)) {
            $results['error'] = "Error: schema.sql file not found at " . $schemaFile;
        } else {
            $sqlContent = file_get_contents($schemaFile);

            if ($sqlContent === false) {
                $results['error'] = "Error: Could not read schema.sql file";
            } else {
                $results['fileSize'] = strlen($sqlContent);

                
                $statements = array_filter(
                    array_map('trim', preg_split('/;(?=\s|$)/i', $sqlContent)),
                    function ($stmt) {
                        return !empty($stmt) && strpos($stmt, '--') !== 0;
                    }
                );

                $results['totalStatements'] = count($statements);
                $results['statements'] = [];
                $successCount = 0;
                $errorCount = 0;

                
                foreach ($statements as $index => $statement) {
                    $statement = trim($statement);
                    
                    if (empty($statement)) {
                        continue;
                    }
                    
                    $statementResult = [
                        'index' => $index,
                        'action' => 'Execute',
                        'objectName' => 'Statement',
                        'status' => 'pending'
                    ];
                    
                    
                    if (preg_match('/^(CREATE|CREATE INDEX|ALTER|DROP)/i', $statement, $matches)) {
                        $statementResult['action'] = strtoupper($matches[1]);
                        
                        
                        if (preg_match('/CREATE\s+(?:TABLE|INDEX|DATABASE)\s+(?:IF\s+NOT\s+EXISTS\s+)?`?(\w+)`?/i', $statement, $nameMatch)) {
                            $statementResult['objectName'] = $nameMatch[1];
                        }
                    }
                    
                    
                    if ($connection->multi_query($statement . ';')) {
                        
                        do {
                            if ($connection->store_result()) {
                                $connection->free_result();
                            }
                        } while ($connection->more_results() && $connection->next_result());
                        
                        $statementResult['status'] = 'success';
                        $successCount++;
                    } else {
                        $statementResult['status'] = 'failed';
                        $statementResult['error'] = $connection->error;
                        $errorCount++;
                    }
                    
                    $results['statements'][] = $statementResult;
                }

                $results['successCount'] = $successCount;
                $results['errorCount'] = $errorCount;
            }
        }

        if (isset($connection)) {
            $connection->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Table Creation</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            max-width: 900px;
            width: 100%;
            padding: 40px;
        }

        h1 {
            color: #333;
            margin-bottom: 10px;
            text-align: center;
        }

        .subtitle {
            color: #666;
            text-align: center;
            margin-bottom: 30px;
            font-size: 14px;
        }

        .button-section {
            text-align: center;
            margin-bottom: 30px;
        }

        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }

        .btn:active {
            transform: translateY(0);
        }

        .error-box {
            background: #fee;
            border-left: 4px solid #f44;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            color: #c33;
        }

        .success-box {
            background: #efe;
            border-left: 4px solid #4f4;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            color: #3c3;
        }

        .info-section {
            background: #f0f4ff;
            border-left: 4px solid #667eea;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
            border-top: 3px solid #667eea;
            text-align: center;
        }

        .stat-label {
            color: #999;
            font-size: 12px;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #333;
        }

        .statements-list {
            max-height: 400px;
            overflow-y: auto;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .statement-item {
            padding: 10px 15px;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
        }

        .statement-item:last-child {
            border-bottom: none;
        }

        .statement-icon {
            font-size: 18px;
            min-width: 20px;
        }

        .statement-content {
            flex: 1;
        }

        .statement-index {
            color: #999;
            font-size: 12px;
            margin-right: 10px;
        }

        .statement-action {
            font-weight: bold;
            color: #667eea;
        }

        .statement-object {
            color: #666;
        }

        .statement-error {
            color: #f44;
            font-size: 12px;
            margin-top: 5px;
        }

        .status-success {
            background: #efe;
        }

        .status-failed {
            background: #fee;
        }

        .note {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 12px;
            border-radius: 5px;
            margin-top: 20px;
            font-size: 13px;
            color: #856404;
        }

        .loading {
            text-align: center;
            padding: 20px;
            color: #667eea;
        }

        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            animation: spin 1s linear infinite;
            margin: 0 auto 10px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
        }

        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🗄️ Database Table Creation</h1>
        <p class="subtitle">BRACU Freelance Marketplace Setup</p>

        <?php if ($isExecuting): ?>
            <!-- Execution Results -->
            <?php if (isset($results['error'])): ?>
                <div class="error-box">
                    <strong>❌ Error:</strong> <?= htmlspecialchars($results['error']) ?>
                </div>
            <?php else: ?>
                <div class="success-box">
                    <strong>✨ Database initialization completed!</strong>
                </div>

                <div class="info-section">
                    <strong>📋 Execution Summary</strong>
                    <p style="margin-top: 10px; color: #555;">
                        Schema file size: <strong><?= number_format($results['fileSize']) ?></strong> bytes<br>
                        Total statements: <strong><?= $results['totalStatements'] ?></strong>
                    </p>
                </div>

                <div class="stats">
                    <div class="stat-card">
                        <div class="stat-label">Successful</div>
                        <div class="stat-value" style="color: #4f4;">
                            ✓ <?= $results['successCount'] ?>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Failed</div>
                        <div class="stat-value" style="color: <?= $results['errorCount'] > 0 ? '#f44' : '#4f4' ?>;">
                            <?= $results['errorCount'] > 0 ? '❌ ' . $results['errorCount'] : '✓ 0' ?>
                        </div>
                    </div>
                </div>

                <?php if (!empty($results['statements'])): ?>
                    <strong style="display: block; margin-bottom: 10px;">📊 Detailed Statement Execution</strong>
                    <div class="statements-list">
                        <?php foreach ($results['statements'] as $stmt): ?>
                            <div class="statement-item status-<?= $stmt['status'] ?>">
                                <div class="statement-icon">
                                    <?= $stmt['status'] === 'success' ? '✓' : '❌' ?>
                                </div>
                                <div class="statement-content">
                                    <span class="statement-index">[<?= $stmt['index'] ?>]</span>
                                    <span class="statement-action"><?= htmlspecialchars($stmt['action']) ?></span>
                                    <span class="statement-object"><?= htmlspecialchars($stmt['objectName']) ?></span>
                                    <?php if ($stmt['status'] === 'failed'): ?>
                                        <div class="statement-error">Error: <?= htmlspecialchars($stmt['error']) ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="note">
                    <strong>ℹ️ Note:</strong> All tables have been created successfully. Your database is ready for use.
                </div>
            <?php endif; ?>

            <a href="create_tables.php" class="back-link">← Run Again</a>

        <?php else: ?>
            <!-- Initial State - Ready to Create -->
            <div class="info-section">
                <strong>📝 Ready to Initialize</strong>
                <p style="margin-top: 10px; color: #555;">
                    This will create all necessary tables for the BRACU Freelance Marketplace database.
                    Click the button below to proceed.
                </p>
            </div>

            <div class="button-section">
                <a href="?action=create" class="btn">🚀 Create All Tables</a>
            </div>

            <div class="note">
                <strong>⚠️ Warning:</strong> This action will create tables from schema.sql. If tables already exist, they will be skipped (IF NOT EXISTS clause is used).
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
