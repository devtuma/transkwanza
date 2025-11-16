<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste de Conexão - TransKwanza</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        .container {
            background: rgba(255,255,255,0.95);
            padding: 2.5rem;
            border-radius: 1rem;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 800px;
            width: 100%;
        }
        h1 {
            color: #333;
            margin-bottom: 2rem;
            text-align: center;
            font-size: 2rem;
        }
        .test-item {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            border-left: 4px solid #ccc;
        }
        .test-item.success {
            border-color: #28a745;
            background: #d4edda;
        }
        .test-item.error {
            border-color: #dc3545;
            background: #f8d7da;
        }
        .test-item.warning {
            border-color: #ffc107;
            background: #fff3cd;
        }
        .test-item h3 {
            margin-bottom: 0.5rem;
            color: #333;
        }
        .test-item p {
            color: #666;
            line-height: 1.6;
        }
        .icon {
            display: inline-block;
            width: 24px;
            height: 24px;
            margin-right: 0.5rem;
            vertical-align: middle;
        }
        .success .icon::before { content: '✓'; color: #28a745; font-size: 1.5rem; font-weight: bold; }
        .error .icon::before { content: '✗'; color: #dc3545; font-size: 1.5rem; font-weight: bold; }
        .warning .icon::before { content: '⚠'; color: #ffc107; font-size: 1.5rem; font-weight: bold; }
        table {
            width: 100%;
            margin-top: 1rem;
            border-collapse: collapse;
        }
        table td {
            padding: 0.5rem;
            border-bottom: 1px solid #ddd;
        }
        table td:first-child {
            font-weight: 600;
            width: 200px;
        }
        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background: #667eea;
            color: #fff;
            text-decoration: none;
            border-radius: 0.5rem;
            margin-top: 1.5rem;
            transition: all 0.3s;
        }
        .btn:hover {
            background: #764ba2;
            transform: translateY(-2px);
        }
        code {
            background: #2d2d2d;
            color: #f8f8f2;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-family: 'Courier New', monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Teste de Conexão - TransKwanza</h1>

        <?php
        // Definir configurações
        define('DB_HOST', 'localhost');
        define('DB_NAME', 'u442547792_transkwanza');
        define('DB_USER', 'u442547792_admin');
        define('DB_PASS', 'Life0852new2580!');

        $tests = [];

        // ===== TESTE 1: PHP Version =====
        $phpVersion = phpversion();
        $tests[] = [
            'title' => 'Versão do PHP',
            'status' => version_compare($phpVersion, '7.4', '>=') ? 'success' : 'error',
            'message' => "PHP $phpVersion " . (version_compare($phpVersion, '7.4', '>=') ? '(OK)' : '(Requer 7.4+)')
        ];

        // ===== TESTE 2: Extensões PHP =====
        $extensions = ['pdo', 'pdo_mysql', 'mbstring', 'json', 'fileinfo'];
        $missingExtensions = [];
        foreach ($extensions as $ext) {
            if (!extension_loaded($ext)) {
                $missingExtensions[] = $ext;
            }
        }

        $tests[] = [
            'title' => 'Extensões PHP Necessárias',
            'status' => empty($missingExtensions) ? 'success' : 'error',
            'message' => empty($missingExtensions)
                ? 'Todas as extensões estão instaladas'
                : 'Faltando: ' . implode(', ', $missingExtensions)
        ];

        // ===== TESTE 3: Conexão com Banco =====
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);

            $tests[] = [
                'title' => 'Conexão com MySQL',
                'status' => 'success',
                'message' => 'Conectado com sucesso ao banco de dados'
            ];

            // ===== TESTE 4: Verificar Tabelas =====
            $stmt = $pdo->query("SHOW TABLES");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $expectedTables = [
                'users', 'currencies', 'proposals', 'transactions', 'uploads',
                'fraud_checks', 'messages', 'ratings', 'notifications',
                'activity_log', 'admin_actions'
            ];

            $missingTables = array_diff($expectedTables, $tables);

            $tests[] = [
                'title' => 'Tabelas do Banco de Dados',
                'status' => empty($missingTables) ? 'success' : 'warning',
                'message' => empty($missingTables)
                    ? 'Todas as 11 tabelas estão criadas'
                    : 'Tabelas faltando: ' . implode(', ', $missingTables),
                'data' => ['Tabelas encontradas' => count($tables), 'Esperadas' => count($expectedTables)]
            ];

            // ===== TESTE 5: Verificar Moedas =====
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM currencies");
            $currencyCount = $stmt->fetch()['count'];

            $tests[] = [
                'title' => 'Moedas Cadastradas',
                'status' => $currencyCount >= 9 ? 'success' : 'warning',
                'message' => "$currencyCount moedas encontradas (esperado: 9)",
                'data' => $currencyCount >= 9 ? null : ['Ação' => 'Execute o arquivo database/production_schema.sql']
            ];

            // ===== TESTE 6: Verificar Admin =====
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE is_admin = 1");
            $adminCount = $stmt->fetch()['count'];

            $tests[] = [
                'title' => 'Usuário Administrador',
                'status' => $adminCount >= 1 ? 'success' : 'warning',
                'message' => "$adminCount admin(s) encontrado(s)",
                'data' => $adminCount >= 1 ? [
                    'Email' => 'admin@transkwanza.com',
                    'Senha' => 'admin123'
                ] : ['Ação' => 'Execute o arquivo database/production_schema.sql']
            ];

        } catch (PDOException $e) {
            $tests[] = [
                'title' => 'Conexão com MySQL',
                'status' => 'error',
                'message' => 'Erro: ' . $e->getMessage(),
                'data' => [
                    'Host' => DB_HOST,
                    'Database' => DB_NAME,
                    'User' => DB_USER
                ]
            ];
        }

        // ===== TESTE 7: Permissões de Pasta =====
        $uploadPath = __DIR__ . '/uploads/';
        $writable = is_writable($uploadPath);

        $tests[] = [
            'title' => 'Permissões da Pasta uploads/',
            'status' => $writable ? 'success' : 'error',
            'message' => $writable
                ? 'Pasta tem permissão de escrita (755)'
                : 'Pasta NÃO tem permissão de escrita',
            'data' => !$writable ? [
                'Comando' => 'chmod -R 755 public_html/uploads'
            ] : null
        ];

        // ===== TESTE 8: HTTPS =====
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                   || $_SERVER['SERVER_PORT'] == 443
                   || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

        $tests[] = [
            'title' => 'Protocolo HTTPS',
            'status' => $isHttps ? 'success' : 'warning',
            'message' => $isHttps ? 'HTTPS está ativo' : 'HTTPS não detectado (recomendado para produção)'
        ];

        // Renderizar testes
        foreach ($tests as $test) {
            echo '<div class="test-item ' . $test['status'] . '">';
            echo '<h3><span class="icon"></span>' . $test['title'] . '</h3>';
            echo '<p>' . $test['message'] . '</p>';

            if (isset($test['data']) && $test['data']) {
                echo '<table>';
                foreach ($test['data'] as $key => $value) {
                    echo '<tr><td>' . $key . '</td><td>' . $value . '</td></tr>';
                }
                echo '</table>';
            }

            echo '</div>';
        }
        ?>

        <div style="text-align: center; margin-top: 2rem; padding-top: 2rem; border-top: 2px solid #ddd;">
            <p style="color: #666; margin-bottom: 1rem;">
                ⚠️ <strong>IMPORTANTE:</strong> Delete este arquivo após os testes!
            </p>
            <p style="color: #999; font-size: 0.9rem;">
                Comando: <code>rm public_html/test_conexao.php</code>
            </p>
            <a href="index.html" class="btn">Ir para o Site</a>
        </div>
    </div>
</body>
</html>
