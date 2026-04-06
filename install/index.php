<?php

declare(strict_types=1);

session_start();

$rootDir = dirname(__DIR__);
$configDir = $rootDir . '/config';
$configFile = $configDir . '/config.php';
$databaseFile = $configDir . '/database.php';
$installHtaccess = __DIR__ . '/.htaccess';
$installSqlPath = $rootDir . '/install.sql';
$createLeadsSqlPath = $rootDir . '/sql/create_leads_table.sql';
$uploadDir = $rootDir . '/assets';
$alreadyInstalled = is_file($configFile);
$error = '';
$installCompleted = false;

$requirements = [
    'php_version' => version_compare(PHP_VERSION, '8.1.0', '>='),
    'pdo_mysql' => extension_loaded('pdo_mysql'),
    'json' => extension_loaded('json'),
    'mbstring' => extension_loaded('mbstring'),
    'config_writable' => is_dir($configDir) ? is_writable($configDir) : is_writable($rootDir),
];

function extractInstallTables(string $sqlPath): array
{
    if (!is_file($sqlPath)) {
        return [];
    }

    $sql = (string) file_get_contents($sqlPath);
    preg_match_all('/CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?`?([a-zA-Z0-9_]+)`?/i', $sql, $matches);
    return array_values(array_unique($matches[1] ?? []));
}

function getTablesChecklist(array $db, array $expectedTables): array
{
    $result = [];
    if ($expectedTables === []) {
        return $result;
    }

    try {
        $pdo = new PDO(
            sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', (string) ($db['host'] ?? ''), (string) ($db['db_name'] ?? '')),
            (string) ($db['db_user'] ?? ''),
            (string) ($db['db_pass'] ?? ''),
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        foreach ($expectedTables as $table) {
            $result[$table] = tableExists($pdo, $table);
        }
    } catch (Throwable) {
        foreach ($expectedTables as $table) {
            $result[$table] = false;
        }
    }

    return $result;
}

function installRenderEmailTemplate(string $rootDir, string $template, array $vars = []): string
{
    $path = $rootDir . '/templates/emails/' . $template . '.php';
    if (!is_file($path)) {
        return '<p>Installation terminée.</p>';
    }

    extract($vars, EXTR_SKIP);
    ob_start();
    include $path;
    return (string) ob_get_clean();
}

function installSendEmail(string $to, string $subject, string $html, string $siteName): void
{
    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=UTF-8',
        'From: ' . ($siteName !== '' ? $siteName : 'EstimIA') . ' <no-reply@localhost>',
    ];

    if ($to !== '') {
        @mail($to, $subject, $html, implode("\r\n", $headers));
    }
}

function tableExists(PDO $pdo, string $tableName): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :table_name'
    );
    $stmt->execute(['table_name' => $tableName]);
    return (int) $stmt->fetchColumn() > 0;
}

function applySqlFileIfTableMissing(PDO $pdo, string $tableName, string $sqlPath): void
{
    if (tableExists($pdo, $tableName)) {
        return;
    }
    if (!is_file($sqlPath)) {
        throw new RuntimeException(basename($sqlPath) . ' introuvable.');
    }
    $sql = trim((string) file_get_contents($sqlPath));
    if ($sql === '') {
        throw new RuntimeException(basename($sqlPath) . ' est vide.');
    }
    $pdo->exec($sql);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $step = (int) ($_POST['step'] ?? 1);

    if ($step === 3) {
        $_SESSION['install_site'] = [
            'site_name'            => trim((string) ($_POST['site_name'] ?? 'EstimIA')),
            'city_name'            => trim((string) ($_POST['city_name'] ?? 'Bordeaux')),
            'operation_radius_km'  => max(1, (int) ($_POST['operation_radius_km'] ?? 30)),
            'admin_email'          => trim((string) ($_POST['admin_email'] ?? '')),
            'site_phone'           => trim((string) ($_POST['site_phone'] ?? '')),
            'admin_password'       => (string) ($_POST['admin_password'] ?? ''),
            'base_url'             => trim((string) ($_POST['base_url'] ?? '')),
            'smtp_host'            => trim((string) ($_POST['smtp_host'] ?? '')),
            'smtp_port'            => (int) ($_POST['smtp_port'] ?? 587),
            'smtp_user'            => trim((string) ($_POST['smtp_user'] ?? '')),
            'smtp_pass'            => (string) ($_POST['smtp_pass'] ?? ''),
            'ai_openai_key'        => trim((string) ($_POST['ai_openai_key'] ?? ($_SESSION['install_site']['ai_openai_key'] ?? ''))),
            'ai_anthropic_key'     => trim((string) ($_POST['ai_anthropic_key'] ?? ($_SESSION['install_site']['ai_anthropic_key'] ?? ''))),
            'ai_perplexity_key'    => trim((string) ($_POST['ai_perplexity_key'] ?? ($_SESSION['install_site']['ai_perplexity_key'] ?? ''))),
            'ai_mistral_key'       => trim((string) ($_POST['ai_mistral_key'] ?? ($_SESSION['install_site']['ai_mistral_key'] ?? ''))),
            'operation_cities_json'=> (string) ($_POST['operation_cities_json'] ?? ($_SESSION['install_site']['operation_cities_json'] ?? '[]')),
        ];
        header('Location: ?step=4');
        exit;
    }

    if ($step === 4) {
        $site = $_SESSION['install_site'] ?? [];
        if (!is_array($site)) {
            $site = [];
        }

        $site['ai_openai_key'] = trim((string) ($_POST['ai_openai_key'] ?? ($site['ai_openai_key'] ?? '')));
        $site['ai_anthropic_key'] = trim((string) ($_POST['ai_anthropic_key'] ?? ($site['ai_anthropic_key'] ?? '')));
        $site['ai_perplexity_key'] = trim((string) ($_POST['ai_perplexity_key'] ?? ($site['ai_perplexity_key'] ?? '')));
        $site['ai_mistral_key'] = trim((string) ($_POST['ai_mistral_key'] ?? ($site['ai_mistral_key'] ?? '')));

        $_SESSION['install_site'] = $site;
        header('Location: ?step=5');
        exit;
    }

    if ($step >= 1 && $step <= 6) {
        $_SESSION['install_wizard'] = array_merge(
            $_SESSION['install_wizard'] ?? [],
            [
                'agence_nom' => trim((string) ($_POST['agence_nom'] ?? ($_SESSION['install_wizard']['agence_nom'] ?? ''))),
                'ville_principale' => trim((string) ($_POST['ville_principale'] ?? ($_SESSION['install_wizard']['ville_principale'] ?? ''))),
                'couleur' => trim((string) ($_POST['couleur'] ?? ($_SESSION['install_wizard']['couleur'] ?? '#1e3a5f'))),
                'email_reception' => trim((string) ($_POST['email_reception'] ?? ($_SESSION['install_wizard']['email_reception'] ?? ''))),
                'smtp_host' => trim((string) ($_POST['smtp_host'] ?? ($_SESSION['install_wizard']['smtp_host'] ?? ''))),
                'smtp_port' => (int) ($_POST['smtp_port'] ?? ($_SESSION['install_wizard']['smtp_port'] ?? 587)),
                'smtp_user' => trim((string) ($_POST['smtp_user'] ?? ($_SESSION['install_wizard']['smtp_user'] ?? ''))),
                'smtp_pass' => (string) ($_POST['smtp_pass'] ?? ($_SESSION['install_wizard']['smtp_pass'] ?? '')),
                'email_expediteur' => trim((string) ($_POST['email_expediteur'] ?? ($_SESSION['install_wizard']['email_expediteur'] ?? ''))),
                'h1_titre' => trim((string) ($_POST['h1_titre'] ?? ($_SESSION['install_wizard']['h1_titre'] ?? ''))),
                'sous_titre' => trim((string) ($_POST['sous_titre'] ?? ($_SESSION['install_wizard']['sous_titre'] ?? ''))),
                'meta_description' => trim((string) ($_POST['meta_description'] ?? ($_SESSION['install_wizard']['meta_description'] ?? ''))),
                'api_key_openai' => trim((string) ($_POST['api_key_openai'] ?? ($_SESSION['install_wizard']['api_key_openai'] ?? ''))),
                'api_key_perplexity' => trim((string) ($_POST['api_key_perplexity'] ?? ($_SESSION['install_wizard']['api_key_perplexity'] ?? ''))),
                'api_key_claude' => trim((string) ($_POST['api_key_claude'] ?? ($_SESSION['install_wizard']['api_key_claude'] ?? ''))),
                'api_key_dvf' => trim((string) ($_POST['api_key_dvf'] ?? ($_SESSION['install_wizard']['api_key_dvf'] ?? ''))),
                'api_key_mamouth' => trim((string) ($_POST['api_key_mamouth'] ?? ($_SESSION['install_wizard']['api_key_mamouth'] ?? ''))),
                'api_keys_activate_now' => isset($_POST['api_keys_activate_now'])
                    ? true
                    : (bool) ($_SESSION['install_wizard']['api_keys_activate_now'] ?? false),
            ]
        );

        if ($step === 1 && isset($_FILES['logo']) && is_uploaded_file($_FILES['logo']['tmp_name'])) {
            if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
                die('Impossible de créer le dossier assets/.');
            }

            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = (string) $finfo->file($_FILES['logo']['tmp_name']);
            $allowed = [
                'image/png' => 'png',
                'image/jpeg' => 'jpg',
                'image/webp' => 'webp',
                'image/svg+xml' => 'svg',
            ];

            if (isset($allowed[$mime])) {
                $extension = $allowed[$mime];
                $target = $uploadDir . '/logo.' . $extension;
                if (!move_uploaded_file($_FILES['logo']['tmp_name'], $target)) {
                    die('Impossible d\'enregistrer le logo uploadé.');
                }
                $_SESSION['install_wizard']['logo'] = 'assets/logo.' . $extension;
            }
        }

        if ($step === 2) {
            $_SESSION['install_db'] = [
                'host' => trim((string) ($_POST['host'] ?? ($_SESSION['install_db']['host'] ?? 'localhost'))),
                'db_name' => trim((string) ($_POST['db_name'] ?? ($_SESSION['install_db']['db_name'] ?? ''))),
                'db_user' => trim((string) ($_POST['db_user'] ?? ($_SESSION['install_db']['db_user'] ?? ''))),
                'db_pass' => (string) ($_POST['db_pass'] ?? ($_SESSION['install_db']['db_pass'] ?? '')),
            ];

            $rawCities = $_POST['villes'] ?? [];
            $cities = [];
            if (is_array($rawCities)) {
                foreach ($rawCities as $city) {
                    $city = trim((string) $city);
                    if ($city !== '') {
                        $cities[] = $city;
                    }
                }
            }
            $_SESSION['install_wizard']['villes'] = array_values(array_unique($cities));

            try {
                $db = $_SESSION['install_db'];
                new PDO(
                    sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', (string) $db['host'], (string) $db['db_name']),
                    (string) $db['db_user'],
                    (string) $db['db_pass'],
                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                );

                header('Location: ?step=3');
                exit;
            } catch (Throwable $ex) {
                $error = 'Connexion DB impossible : ' . $ex->getMessage();
            }
        }

    if ($step === 6) {
        $wizard = $_SESSION['install_wizard'] ?? [];

        $agenceNom = trim((string) ($wizard['agence_nom'] ?? ''));
        $villePrincipale = trim((string) ($wizard['ville_principale'] ?? ''));
        $villes = $wizard['villes'] ?? [];

        if ($agenceNom === '' || $villePrincipale === '' || empty($villes)) {
            header('Location: index.php?step=1&error=missing_data');
            exit;
        }

        if (!is_dir($configDir) && !mkdir($configDir, 0755, true) && !is_dir($configDir)) {
            die('Impossible de créer le dossier config/.');
        }

        $config = [
            'installed' => true,
            'agence_nom' => $agenceNom,
            'ville_principale' => $villePrincipale,
            'logo' => (string) ($wizard['logo'] ?? ''),
            'couleur' => preg_match('/^#[0-9A-Fa-f]{6}$/', (string) ($wizard['couleur'] ?? '')) ? $wizard['couleur'] : '#1e3a5f',
            'email_reception' => (string) ($wizard['email_reception'] ?? ''),
            'smtp_host' => (string) ($wizard['smtp_host'] ?? ''),
            'smtp_port' => (int) ($wizard['smtp_port'] ?? 587),
            'smtp_user' => (string) ($wizard['smtp_user'] ?? ''),
            'smtp_pass' => (string) ($wizard['smtp_pass'] ?? ''),
            'email_expediteur' => (string) ($wizard['email_expediteur'] ?? ''),
            'h1_titre' => (string) ($wizard['h1_titre'] ?: ('Combien vaut votre bien à ' . $villePrincipale . ' ?')),
            'sous_titre' => (string) ($wizard['sous_titre'] ?: 'Obtenez une estimation instantanée basée sur les données du marché local.'),
            'meta_description' => (string) ($wizard['meta_description'] ?: ('Estimation gratuite à ' . $villePrincipale)),
            'villes' => array_values($villes),
            'api_key_openai' => (string) ($wizard['api_key_openai'] ?? ''),
            'api_key_perplexity' => (string) ($wizard['api_key_perplexity'] ?? ''),
            'api_key_claude' => (string) ($wizard['api_key_claude'] ?? ''),
            'api_key_dvf' => (string) ($wizard['api_key_dvf'] ?? ''),
            'api_key_mamouth' => (string) ($wizard['api_key_mamouth'] ?? ''),
            'api_keys_activate_now' => (bool) ($wizard['api_keys_activate_now'] ?? false),
        ];

        unset($_SESSION['install_wizard']);

    if ($step === 3) {
        $_SESSION['install_site'] = [
            'site_name'            => trim((string) ($_POST['site_name'] ?? 'EstimIA')),
            'city_name'            => trim((string) ($_POST['city_name'] ?? 'Bordeaux')),
            'operation_radius_km'  => max(1, (int) ($_POST['operation_radius_km'] ?? 30)),
            'admin_email'          => trim((string) ($_POST['admin_email'] ?? '')),
            'site_phone'           => trim((string) ($_POST['site_phone'] ?? '')),
            'admin_password'       => (string) ($_POST['admin_password'] ?? ''),
            'base_url'             => trim((string) ($_POST['base_url'] ?? '')),
            'smtp_host'            => trim((string) ($_POST['smtp_host'] ?? '')),
            'smtp_port'            => (int) ($_POST['smtp_port'] ?? 587),
            'smtp_user'            => trim((string) ($_POST['smtp_user'] ?? '')),
            'smtp_pass'            => (string) ($_POST['smtp_pass'] ?? ''),
            'ai_openai_key'        => trim((string) ($_POST['ai_openai_key'] ?? '')),
            'ai_anthropic_key'     => trim((string) ($_POST['ai_anthropic_key'] ?? '')),
            'ai_perplexity_key'    => trim((string) ($_POST['ai_perplexity_key'] ?? '')),
            'ai_mistral_key'       => trim((string) ($_POST['ai_mistral_key'] ?? '')),
            'operation_cities_json'=> (string) ($_POST['operation_cities_json'] ?? '[]'),
        ];
        header('Location: ?step=4');
        exit;
    }
}

    if ($step === 4) {
        $site = $_SESSION['install_site'] ?? [];
        if (!is_array($site)) {
            $site = [];
        }
        $site['ai_openai_key'] = trim((string) ($_POST['ai_openai_key'] ?? ($site['ai_openai_key'] ?? '')));
        $site['ai_anthropic_key'] = trim((string) ($_POST['ai_anthropic_key'] ?? ($site['ai_anthropic_key'] ?? '')));
        $site['ai_perplexity_key'] = trim((string) ($_POST['ai_perplexity_key'] ?? ($site['ai_perplexity_key'] ?? '')));
        $site['ai_mistral_key'] = trim((string) ($_POST['ai_mistral_key'] ?? ($site['ai_mistral_key'] ?? '')));
        $_SESSION['install_site'] = $site;
        header('Location: ?step=5');
        exit;
    }

    if ($step === 5) {
        $db   = $_SESSION['install_db'] ?? null;
        $site = $_SESSION['install_site'] ?? null;

        if (!is_array($db) || !is_array($site)) {
            $error = 'Les étapes 2 et 3 doivent être complétées avant la finalisation.';
        } elseif (($site['admin_email'] ?? '') === '' || ($site['admin_password'] ?? '') === '') {
            $error = 'Email admin et mot de passe admin requis.';
        } else {
            try {
                if (!is_dir($configDir) && !mkdir($configDir, 0755, true) && !is_dir($configDir)) {
                    throw new RuntimeException('Impossible de créer le dossier config/.');
                }

                $secret    = bin2hex(random_bytes(32));
                $e         = fn(string $v): string => addslashes($v);
                $siteName  = $e((string) $site['site_name']);
                $cityName  = $e((string) $site['city_name']);
                $sitePhone = $e((string) $site['site_phone']);
                $adminEmail = $e((string) $site['admin_email']);
                $baseUrl   = $e((string) $site['base_url']);
                $radius    = max(1, (int) ($site['operation_radius_km'] ?? 30));
                $smtpHost  = $e((string) ($site['smtp_host'] ?? ''));
                $smtpPort  = max(1, (int) ($site['smtp_port'] ?? 587));
                $smtpUser  = $e((string) ($site['smtp_user'] ?? ''));
                $smtpPass  = $e((string) ($site['smtp_pass'] ?? ''));
                $aiOpenAi = $e((string) ($site['ai_openai_key'] ?? ''));
                $aiAnthropic = $e((string) ($site['ai_anthropic_key'] ?? ''));
                $aiPerplexity = $e((string) ($site['ai_perplexity_key'] ?? ''));
                $aiMistral = $e((string) ($site['ai_mistral_key'] ?? ''));
                $operationCitiesJson = $e((string) ($site['operation_cities_json'] ?? '[]'));
                $dbHost    = $e((string) $db['host']);
                $dbName    = $e((string) $db['db_name']);
                $dbUser    = $e((string) $db['db_user']);
                $dbPass    = $e((string) $db['db_pass']);

                $configContent = <<<PHP
<?php
// Configuration EstimIA - Bordeaux
define('DEBUG_MODE', false);
define('MAINTENANCE_MODE', false);
define('SITE_NAME', '{$siteName}');
define('CITY_NAME', '{$cityName}');
define('OPERATION_RADIUS_KM', {$radius});
define('SITE_PHONE', '{$sitePhone}');

// Base de données
define('DB_HOST', '{$dbHost}');
define('DB_NAME', '{$dbName}');
define('DB_USER', '{$dbUser}');
define('DB_PASS', '{$dbPass}');

// Email
define('SMTP_HOST', '{$smtpHost}');
define('SMTP_USER', '{$smtpUser}');
define('SMTP_PASS', '{$smtpPass}');
define('SMTP_PORT', {$smtpPort});
define('SMTP_FROM', '{$smtpUser}');
define('MAIL_FROM', SMTP_FROM);
define('MAIL_FROM_NAME', 'EstimIA Bordeaux');
define('OPERATION_CITIES_JSON', '{$operationCitiesJson}');

// IA — Multi-provider fallback
define('AI_OPENAI_KEY', '{$aiOpenAi}');
define('AI_ANTHROPIC_KEY', '{$aiAnthropic}');
define('AI_PERPLEXITY_KEY', '{$aiPerplexity}');
define('AI_MISTRAL_KEY', '{$aiMistral}');

// Sécurité
define('ADMIN_EMAIL', '{$adminEmail}');
define('SECRET_KEY', '{$secret}');

// Chemins
define('BASE_URL', '{$baseUrl}');
define('BASE_PATH', __DIR__ . '/..');

require_once BASE_PATH . '/includes/error-handler.php';
PHP;

                file_put_contents($configFile, $configContent);

                $databaseContent = <<<PHP
<?php
return [
    'host'    => '{$dbHost}',
    'dbname'  => '{$dbName}',
    'user'    => '{$dbUser}',
    'pass'    => '{$dbPass}',
    'charset' => 'utf8mb4',
];
PHP;

                file_put_contents($databaseFile, $databaseContent);

                $pdo = new PDO(
                    sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $db['host'], $db['db_name']),
                    $db['db_user'], $db['db_pass'],
                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                );
                applySqlFileIfTableMissing($pdo, 'leads', $createLeadsSqlPath);

                $nameParts     = preg_split('/\s+/', trim((string) $site['site_name'])) ?: [];
                $defaultPrenom = isset($nameParts[0]) && $nameParts[0] !== '' ? $nameParts[0] : 'Admin';
                $defaultNom    = count($nameParts) > 1 ? implode(' ', array_slice($nameParts, 1)) : 'EstimIA';

                $adminStmt = $pdo->prepare(
                    'INSERT INTO admins (prenom, nom, email) VALUES (:prenom, :nom, :email)
                     ON DUPLICATE KEY UPDATE prenom = VALUES(prenom), nom = VALUES(nom)'
                );
                $adminStmt->execute([
                    'prenom' => $defaultPrenom,
                    'nom'    => $defaultNom,
                    'email'  => (string) $site['admin_email'],
                ]);

                $emailHtml = installRenderEmailTemplate($rootDir, 'install-success', [
                    'prenom'   => $defaultPrenom,
                    'nom'      => $defaultNom,
                    'siteName' => (string) $site['site_name'],
                    'cityName' => (string) $site['city_name'],
                    'baseUrl'  => (string) $site['base_url'],
                ]);

                installSendEmail(
                    (string) $site['admin_email'],
                    'Installation terminée - Accès administration',
                    $emailHtml,
                    (string) $site['site_name']
                );

                $installCompleted = true;
                unset($_SESSION['install_db'], $_SESSION['install_site']);
            } catch (Throwable $ex) {
                $error = $ex->getMessage();
            }
        }
    }
}
}

$step = max(1, min(6, (int) ($_GET['step'] ?? ($_POST['step'] ?? 1))));
$data = $_SESSION['install_wizard'] ?? [];

$dbSession   = $_SESSION['install_db'] ?? ['host' => 'localhost', 'db_name' => '', 'db_user' => '', 'db_pass' => ''];
$siteSession = $_SESSION['install_site'] ?? [
    'site_name' => 'EstimIA', 'city_name' => 'Bordeaux', 'operation_radius_km' => 30,
    'admin_email' => '', 'site_phone' => '', 'admin_password' => '', 'base_url' => '',
    'smtp_host' => '', 'smtp_port' => 587, 'smtp_user' => '', 'smtp_pass' => '',
    'ai_openai_key' => '', 'ai_anthropic_key' => '', 'ai_perplexity_key' => '', 'ai_mistral_key' => '',
    'operation_cities_json' => '[]',
];

$tableDescriptions = [
    'estimations'             => 'Stocke toutes les demandes d\'estimation des visiteurs.',
    'users'                   => 'Comptes administrateurs/agents pour accéder au back-office.',
    'settings'                => 'Paramètres du site (nom, ville, téléphone, couleurs…).',
    'villes_prix'             => 'Prix au m² par ville/quartier pour le calcul d\'estimation.',
    'lead_activities'         => 'Historique des notes et actions sur les leads.',
    'rate_limits'             => 'Protection anti-abus et limitation de requêtes.',
    'login_attempts'          => 'Suivi des tentatives de connexion à l\'admin.',
    'email_logs'              => 'Historique des emails envoyés (estimation, relance…).',
    'webhook_logs'            => 'Journal des appels webhooks sortants.',
    'sessions'                => 'Sessions admin actives.',
    'admin_users'             => 'Comptes d\'administration avancée supplémentaires.',
    'ads_checklist_progress'  => 'Progression de la checklist Google Ads.',
    'google_ads_drafts'       => 'Brouillons d\'annonces Google Ads.',
    'admins'                  => 'Table principale des comptes administrateurs.',
];

$expectedTables  = extractInstallTables($installSqlPath);
$tableChecklist  = [];
if (is_array($_SESSION['install_db'] ?? null)) {
    $tableChecklist = getTablesChecklist($_SESSION['install_db'], $expectedTables);
}

$allReqOk = !in_array(false, $requirements, true);
$stepLabels = ['Pré-requis', 'Base de données', 'Configuration', 'Clés IA', 'Finalisation'];

?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Installation EstimIA - Assistant d'installation</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4F46E5;
            --primary-dark: #3730A3;
            --primary-light: #6366F1;
            --primary-glow: rgba(79, 70, 229, 0.15);
            --text-primary: #1F2937;
            --text-secondary: #6B7280;
            --text-muted: #9CA3AF;
            --bg: #F9FAFB;
            --bg-dark: #111827;
            --bg-card: #FFFFFF;
            --border: #E5E7EB;
            --border-dark: #374151;
            --radius-sm: 8px;
            --radius-md: 12px;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg);
            color: var(--text-primary);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .dark body { background-color: var(--bg-dark); color: #F9FAFB; }
        .container { max-width: 800px; margin: 0 auto; padding: 0 1rem; width: 100%; }

        .installer-header { padding: 2rem 0; border-bottom: 1px solid var(--border); margin-bottom: 2rem; }
        .header-content { display: flex; justify-content: space-between; align-items: center; }
        .logo { display: flex; align-items: center; gap: 0.75rem; font-size: 1.5rem; font-weight: 700; }
        .logo-icon {
            width: 32px; height: 32px; background-color: var(--primary); border-radius: 50%;
            display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;
        }
        .installer-title { color: var(--text-secondary); font-size: 0.9rem; font-weight: 500; }

        .progress-steps { display: flex; justify-content: space-between; position: relative; margin-bottom: 2rem; }
        .progress-steps::before {
            content: ''; position: absolute; top: 50%; left: 0; right: 0; height: 2px; background-color: var(--border); z-index: 1;
        }
        .progress-step { display: flex; flex-direction: column; align-items: center; position: relative; z-index: 2; width: 100%; }
        .step-number {
            width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center;
            background-color: var(--border); color: var(--text-muted); font-weight: 600; margin-bottom: 0.5rem; border: 2px solid var(--bg);
        }
        .progress-step.active .step-number { background-color: var(--primary); color: white; border-color: var(--primary-light); }
        .progress-step.completed .step-number { background-color: var(--primary); color: white; }
        .step-label { font-size: 0.875rem; font-weight: 500; color: var(--text-muted); text-align: center; }
        .progress-step.active .step-label { color: var(--text-primary); }
        .progress-step.completed .step-label { color: var(--primary); }

        .installer-content {
            flex: 1; background-color: var(--bg-card); border-radius: var(--radius-md);
            padding: 2rem; box-shadow: var(--shadow-sm); margin-bottom: 2rem;
        }

        .content-header { margin-bottom: 2rem; }
        .content-title { font-size: 1.5rem; font-weight: 600; margin-bottom: 0.5rem; }
        .content-subtitle { color: var(--text-secondary); font-size: 1rem; }

        .form-group { margin-bottom: 1.5rem; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
        @media (max-width: 640px) { .form-row { grid-template-columns: 1fr; } }
        .form-label { display: block; margin-bottom: 0.5rem; font-weight: 500; font-size: 0.875rem; }
        .form-input {
            width: 100%; padding: 0.75rem 1rem; border: 1px solid var(--border); border-radius: var(--radius-sm);
            background-color: var(--bg); font-size: 1rem; transition: border-color 0.2s;
        }
        .form-input:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px var(--primary-glow); }

        .btn {
            display: inline-flex; align-items: center; justify-content: center; padding: 0.75rem 1.5rem;
            border-radius: var(--radius-sm); font-weight: 500; font-size: 0.875rem;
            transition: all 0.2s; cursor: pointer; border: 1px solid transparent;
        }
        .btn-primary { background-color: var(--primary); color: white; }
        .btn-primary:hover { background-color: var(--primary-dark); }
        .btn-secondary { background-color: transparent; color: var(--primary); border: 1px solid var(--primary); }
        .btn-secondary:hover { background-color: var(--primary-glow); }
        .btn-group { display: flex; gap: 0.75rem; justify-content: flex-end; margin-top: 2rem; }

        .checklist { list-style: none; margin: 1.5rem 0; }
        .checklist-item { display: flex; align-items: center; padding: 0.75rem 0; border-bottom: 1px solid var(--border); }
        .checklist-item:last-child { border-bottom: none; }
        .checklist-icon {
            width: 20px; height: 20px; border-radius: 50%; display: flex; align-items: center; justify-content: center;
            margin-right: 0.75rem; font-size: 0.75rem; font-weight: bold;
        }
        .checklist-icon.success { background-color: var(--primary); color: white; }
        .checklist-icon.danger { background-color: #EF4444; color: white; }

        .spinner {
            width: 1rem; height: 1rem; border: 2px solid rgba(255, 255, 255, 0.3); border-radius: 50%;
            border-top-color: white; animation: spin 0.6s linear infinite; margin-right: 0.5rem;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        .installer-footer { padding: 1.5rem 0; text-align: center; color: var(--text-muted); font-size: 0.875rem; }
        .animate-in { animation: fadeIn 0.3s ease-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .hidden { display: none; }
        .alert {
            padding: 0.75rem 1rem; border-radius: var(--radius-sm); margin-bottom: 1rem; border: 1px solid #fecaca;
            background: #fff1f2; color: #9f1239; font-size: 0.9rem;
        }
        .dark .form-input { background-color: var(--bg-dark); border-color: var(--border-dark); color: #F9FAFB; }
    </style>
</head>
<body class="dark">
    <div class="container">
        <header class="installer-header">
            <div class="header-content">
                <div class="logo">
                    <div class="logo-icon">E</div>
                    <span>EstimIA</span>
                </div>
                <div class="installer-title">Assistant d'installation</div>
            </div>
        </header>

        <div class="progress-steps">
            <?php foreach ($stepLabels as $i => $label): ?>
            <div class="progress-step <?= $step === $i + 1 ? 'active' : ($step > $i + 1 ? 'completed' : '') ?>">
                <div class="step-number"><?= $i + 1 ?></div>
                <div class="step-label"><?= htmlspecialchars($label) ?></div>
            </div>
            <?php endforeach; ?>
        </div>

        <main class="installer-content animate-in">
            <?php if ($error !== ''): ?>
                <div class="alert"><?= htmlspecialchars($error, ENT_QUOTES) ?></div>
            <?php endif; ?>

            <?php if ($installCompleted): ?>
            <div class="content-header">
                <h2 class="content-title">Installation terminée ✅</h2>
                <p class="content-subtitle">Votre configuration est enregistrée. Vous pouvez maintenant vous connecter à l'administration.</p>
            </div>
            <div class="btn-group">
                <a href="../admin/login.php" class="btn btn-primary">Aller à l'administration</a>
            </div>
            <?php endif; ?>

            <?php if (!$installCompleted && $step === 1): ?>
            <div class="content-header">
                <h2 class="content-title">Pré-requis système</h2>
                <p class="content-subtitle">Vérification des exigences pour l'installation d'EstimIA</p>
            </div>

            <ul class="checklist">
                <?php foreach ($requirements as $req => $ok): ?>
                <li class="checklist-item">
                    <div class="checklist-icon <?= $ok ? 'success' : 'danger' ?>"><?= $ok ? '✓' : '✗' ?></div>
                    <div class="checklist-content">
                        <div class="checklist-title"><?= htmlspecialchars((string) $req) ?></div>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>

            <div class="btn-group">
                <?php if ($allReqOk): ?>
                <button type="button" class="btn btn-primary" onclick="window.location.href='?step=2'">Suivant</button>
                <?php else: ?>
                <button type="button" class="btn btn-secondary" onclick="window.location.reload()">Re-vérifier</button>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php if (!$installCompleted && $step === 2): ?>
            <div class="content-header">
                <h2 class="content-title">Configuration de la base de données</h2>
                <p class="content-subtitle">Paramétrez la connexion à votre base de données MySQL</p>
            </div>

            <form method="post">
                <input type="hidden" name="step" value="2">
                <div class="form-group">
                    <label class="form-label" for="host">Hôte de la base de données</label>
                    <input class="form-input" type="text" id="host" name="host" value="<?= htmlspecialchars($dbSession['host'] ?? 'localhost', ENT_QUOTES) ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="db_name">Nom de la base de données</label>
                    <input class="form-input" type="text" id="db_name" name="db_name" value="<?= htmlspecialchars($dbSession['db_name'] ?? '', ENT_QUOTES) ?>" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="db_user">Utilisateur</label>
                        <input class="form-input" type="text" id="db_user" name="db_user" value="<?= htmlspecialchars($dbSession['db_user'] ?? '', ENT_QUOTES) ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="db_pass">Mot de passe</label>
                        <input class="form-input" type="password" id="db_pass" name="db_pass" value="<?= htmlspecialchars($dbSession['db_pass'] ?? '', ENT_QUOTES) ?>">
                    </div>
                </div>
                <div class="btn-group">
                    <button type="button" class="btn btn-secondary" onclick="window.location.href='?step=1'">Précédent</button>
                    <button type="submit" class="btn btn-primary">Tester la connexion</button>
                </div>
            </form>
            <?php endif; ?>

            <?php if (!$installCompleted && $step === 3): ?>
            <div class="content-header">
                <h2 class="content-title">Configuration du site</h2>
                <p class="content-subtitle">Paramétrez les informations de votre site d'estimation</p>
            </div>

            <form method="post">
                <input type="hidden" name="step" value="3">

                <div class="form-group">
                    <label class="form-label" for="site_name">Nom du site</label>
                    <input class="form-input" type="text" id="site_name" name="site_name" value="<?= htmlspecialchars($siteSession['site_name'] ?? 'EstimIA', ENT_QUOTES) ?>" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="city_name">Ville principale</label>
                        <input class="form-input" type="text" id="city_name" name="city_name" value="<?= htmlspecialchars($siteSession['city_name'] ?? 'Bordeaux', ENT_QUOTES) ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="operation_radius_km">Rayon d'opération (km)</label>
                        <input class="form-input" type="number" id="operation_radius_km" name="operation_radius_km" value="<?= htmlspecialchars((string) ($siteSession['operation_radius_km'] ?? '30'), ENT_QUOTES) ?>" min="1" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="admin_email">Email administrateur</label>
                    <input class="form-input" type="email" id="admin_email" name="admin_email" value="<?= htmlspecialchars($siteSession['admin_email'] ?? '', ENT_QUOTES) ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="site_phone">Téléphone du site</label>
                    <input class="form-input" type="tel" id="site_phone" name="site_phone" value="<?= htmlspecialchars($siteSession['site_phone'] ?? '', ENT_QUOTES) ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="admin_password">Mot de passe administrateur</label>
                    <input class="form-input" type="password" id="admin_password" name="admin_password" required>
                </div>

                <div class="btn-group">
                    <button type="button" class="btn btn-secondary" onclick="window.location.href='?step=2'">Précédent</button>
                    <button type="submit" class="btn btn-primary">Suivant</button>
                </div>
            </form>
            <?php endif; ?>

            <?php if (!$installCompleted && $step === 4): ?>
            <div class="content-header">
                <h2 class="content-title">Configuration des clés API</h2>
                <p class="content-subtitle">Configurez les clés pour les services d'intelligence artificielle</p>
            </div>

            <form method="post">
                <input type="hidden" name="step" value="4">

                <div class="form-group">
                    <label class="form-label" for="ai_openai_key">Clé API OpenAI</label>
                    <input class="form-input" type="password" id="ai_openai_key" name="ai_openai_key" value="<?= htmlspecialchars($siteSession['ai_openai_key'] ?? '', ENT_QUOTES) ?>" placeholder="sk-...">
                </div>

                <div class="form-group">
                    <label class="form-label" for="ai_anthropic_key">Clé API Anthropic (Claude)</label>
                    <input class="form-input" type="password" id="ai_anthropic_key" name="ai_anthropic_key" value="<?= htmlspecialchars($siteSession['ai_anthropic_key'] ?? '', ENT_QUOTES) ?>" placeholder="sk-ant-...">
                </div>

                <div class="form-group">
                    <label class="form-label" for="ai_perplexity_key">Clé API Perplexity</label>
                    <input class="form-input" type="password" id="ai_perplexity_key" name="ai_perplexity_key" value="<?= htmlspecialchars($siteSession['ai_perplexity_key'] ?? '', ENT_QUOTES) ?>" placeholder="pplx-...">
                </div>

                <div class="form-group">
                    <label class="form-label" for="ai_mistral_key">Clé API Mistral</label>
                    <input class="form-input" type="password" id="ai_mistral_key" name="ai_mistral_key" value="<?= htmlspecialchars($siteSession['ai_mistral_key'] ?? '', ENT_QUOTES) ?>" placeholder="...">
                </div>

                <div class="btn-group">
                    <button type="button" class="btn btn-secondary" onclick="window.location.href='?step=3'">Précédent</button>
                    <button type="submit" class="btn btn-primary">Suivant</button>
                </div>
            </form>
            <?php endif; ?>

            <?php if (!$installCompleted && $step === 5): ?>
            <div class="content-header">
                <h2 class="content-title">Finalisation</h2>
                <p class="content-subtitle">Vérifiez les informations avant de finaliser l'installation</p>
            </div>

            <form method="post" id="finalizeForm">
                <input type="hidden" name="step" value="5">
                <div class="btn-group">
                    <button type="button" class="btn btn-secondary" onclick="window.location.href='?step=4'">Précédent</button>
                    <button type="submit" id="finalizeBtn" class="btn btn-primary">
                        <span id="finalSpinner" class="spinner hidden"></span>
                        Finaliser l'installation
                    </button>
                </div>
            </form>
            <?php endif; ?>
        </main>

        <footer class="installer-footer">
            <p>EstimIA - Assistant d'installation | © <?= date('Y') ?></p>
        </footer>
    </div>

    <script>
        document.getElementById('finalizeForm')?.addEventListener('submit', function() {
            const btn = document.getElementById('finalizeBtn');
            const spinner = document.getElementById('finalSpinner');
            spinner?.classList.remove('hidden');
            if (btn) {
                btn.disabled = true;
            }
        });
    </script>
</body>
</html>
