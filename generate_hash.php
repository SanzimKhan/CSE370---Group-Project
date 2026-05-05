<?php
/**
 * Password Hash Generator
 * Simple utility to generate bcrypt hashes for test passwords
 */

declare(strict_types=1);

$password = isset($_POST['password']) ? (string) $_POST['password'] : 'password123';
$generated_hash = password_hash($password, PASSWORD_BCRYPT);
$verify_test = password_verify($password, $generated_hash);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Hash Generator</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
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
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 40px;
            max-width: 600px;
            width: 100%;
        }
        
        h1 {
            color: #333;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
        }
        
        input[type="text"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1em;
            font-family: 'Courier New', monospace;
        }
        
        input[type="text"]:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        button {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 1em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }
        
        .result {
            margin-top: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 5px;
            border-left: 4px solid #667eea;
        }
        
        .result-label {
            color: #666;
            font-size: 0.9em;
            margin-bottom: 8px;
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        
        .result-value {
            background: white;
            padding: 12px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
            font-size: 0.85em;
            word-break: break-all;
            color: #333;
            border: 1px solid #ddd;
            margin-bottom: 15px;
        }
        
        .copy-btn {
            padding: 8px 12px;
            background: #28a745;
            font-size: 0.9em;
            width: auto;
            display: inline-block;
            margin-top: 10px;
        }
        
        .copy-btn:hover {
            background: #218838;
        }
        
        .verify {
            margin-top: 15px;
            padding: 12px;
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            border-radius: 3px;
            text-align: center;
            font-weight: 600;
        }
        
        .info {
            background: #d1ecf1;
            color: #0c5460;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #bee5eb;
        }
        
        code {
            background: #f5f5f5;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
            color: #d63384;
        }
    </style>
    <script>
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                alert('Hash copied to clipboard!');
            }).catch(() => {
                alert('Failed to copy. Please copy manually.');
            });
        }
    </script>
</head>
<body>
    <div class="container">
        <h1>🔐 Password Hash Generator</h1>
        
        <div class="info">
            Generate bcrypt password hashes for your database test users. Use the hashes in <code>bloat_database.php</code>.
        </div>
        
        <form method="POST">
            <div class="form-group">
                <label for="password">Password to Hash:</label>
                <input type="text" id="password" name="password" value="<?php echo htmlspecialchars($password); ?>" placeholder="Enter password">
            </div>
            <button type="submit">Generate Hash</button>
        </form>
        
        <?php if ($password): ?>
            <div class="result">
                <div class="result-label">Generated Hash (bcrypt):</div>
                <div class="result-value" id="hashValue"><?php echo htmlspecialchars($generated_hash); ?></div>
                <button class="copy-btn" onclick="copyToClipboard('<?php echo htmlspecialchars($generated_hash); ?>')">Copy Hash</button>
                
                <div class="result-label" style="margin-top: 15px;">How to Use in bloat_database.php:</div>
                <div class="result-value">$password_hash = '<?php echo htmlspecialchars($generated_hash); ?>';</div>
                
                <?php if ($verify_test): ?>
                    <div class="verify">✓ Hash verified! This password will work correctly.</div>
                <?php else: ?>
                    <div style="background: #f8d7da; color: #721c24; padding: 12px; border: 1px solid #f5c6cb; border-radius: 3px; margin-top: 15px;">
                        ✗ Hash verification failed!
                    </div>
                <?php endif; ?>
                
                <div class="result-label" style="margin-top: 15px;">Update bloat_database.php:</div>
                <div class="result-value">
                    Find line: <code>$password_hash = '...';</code><br>
                    Replace with: <code>$password_hash = '<?php echo htmlspecialchars($generated_hash); ?>';</code>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
