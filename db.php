<?php
// db.php - Supabase PostgreSQL database connection

class MabiniPDO extends PDO {
    public function __construct(string $dsn, ?string $username = null, ?string $password = null, ?array $options = null) {
        parent::__construct($dsn, $username, $password, $options ?? []);
    }

    #[\ReturnTypeWillChange]
    public function prepare($query, $options = []) {
        return parent::prepare($this->translateSql((string)$query), $options);
    }

    #[\ReturnTypeWillChange]
    public function query($query, ...$fetchModeArgs) {
        return parent::query($this->translateSql((string)$query), ...$fetchModeArgs);
    }

    #[\ReturnTypeWillChange]
    public function exec($statement) {
        $sql = $this->translateSql((string)$statement);
        if ($sql === '') {
            return 0;
        }
        return parent::exec($sql);
    }

    private function translateSql(string $sql): string {
        $trimmed = trim($sql);
        if (preg_match('/^ALTER\s+TABLE\s+\w+\s+MODIFY\s+COLUMN\s+/i', $trimmed)) {
            return '';
        }

        $sql = str_replace('DATABASE()', 'current_schema()', $sql);
        $sql = preg_replace('/SHOW\s+COLUMNS\s+FROM\s+([a-zA-Z_][a-zA-Z0-9_]*)\s+LIKE\s+\'([a-zA-Z_][a-zA-Z0-9_]*)\'/i', "SELECT column_name FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = '$1' AND column_name = '$2'", $sql);
        $sql = preg_replace('/DATE_FORMAT\s*\(\s*([^)]+?)\s*,\s*["\']%Y-%m["\']\s*\)/i', "TO_CHAR($1, 'YYYY-MM')", $sql);
        $sql = preg_replace('/YEAR\s*\(\s*([^)]+?)\s*\)/i', 'EXTRACT(YEAR FROM $1)', $sql);
        $sql = preg_replace('/MONTH\s*\(\s*([^)]+?)\s*\)/i', 'EXTRACT(MONTH FROM $1)', $sql);
        $sql = preg_replace('/TIME\s*\(\s*([^)]+?)\s*\)/i', 'CAST($1 AS TIME)', $sql);
        $sql = preg_replace('/SELECT\s+DATA_TYPE\s+FROM\s+information_schema\.columns/i', 'SELECT data_type AS "DATA_TYPE" FROM information_schema.columns', $sql);
        $sql = preg_replace('/SELECT\s+COLUMN_NAME\s+FROM\s+information_schema\.columns/i', 'SELECT column_name AS "COLUMN_NAME" FROM information_schema.columns', $sql);
        $sql = str_replace('`read`', 'read', $sql);

        $doubleQuotedStrings = [
            '""' => "''",
            '" "' => "' '",
            '"-"' => "'-'",
            '"approved"' => "'approved'",
            '"pending"' => "'pending'",
            '"declined"' => "'declined'",
            '"hr"' => "'hr'",
            '"employee"' => "'employee'",
            '"department_head"' => "'department_head'",
            '"absent"' => "'absent'",
            '"completed"' => "'completed'",
            '"in_progress"' => "'in_progress'",
            '"missed"' => "'missed'",
            '"on-leave"' => "'on-leave'",
            '"Absent"' => "'Absent'",
        ];
        $sql = strtr($sql, $doubleQuotedStrings);
        $sql = preg_replace('/"([0-9]{2}:[0-9]{2}:[0-9]{2})"/', "'$1'", $sql);

        $sql = $this->translateDdl($sql);
        $sql = $this->translateUpsert($sql);

        return $sql;
    }

    private function translateDdl(string $sql): string {
        $sql = preg_replace('/\s+ENGINE=InnoDB\s+DEFAULT\s+CHARSET=\w+(?:\s+COLLATE=\w+)?/i', '', $sql);
        $sql = preg_replace('/\s+COMMENT\s+\'[^\']*\'/i', '', $sql);
        $sql = preg_replace('/\bINT\s+AUTO_INCREMENT\s+PRIMARY\s+KEY\b/i', 'SERIAL PRIMARY KEY', $sql);
        $sql = preg_replace('/\bTINYINT\s*\(\s*1\s*\)/i', 'SMALLINT', $sql);
        $sql = preg_replace('/\bDATETIME\b/i', 'TIMESTAMP', $sql);
        $sql = preg_replace('/\b(LONGTEXT|MEDIUMTEXT)\b/i', 'TEXT', $sql);
        $sql = preg_replace('/\bENUM\s*\([^)]+\)/i', 'VARCHAR(50)', $sql);
        $sql = preg_replace('/\s+ON\s+UPDATE\s+CURRENT_TIMESTAMP/i', '', $sql);
        $sql = preg_replace('/\s+AFTER\s+[a-zA-Z_][a-zA-Z0-9_]*/i', '', $sql);
        $sql = preg_replace('/UNIQUE\s+KEY\s+([a-zA-Z_][a-zA-Z0-9_]*)\s*\(([^)]+)\)/i', 'CONSTRAINT $1 UNIQUE ($2)', $sql);
        $sql = preg_replace('/,\s*(?:INDEX|KEY)\s+[a-zA-Z_][a-zA-Z0-9_]*\s*\([^)]+\)/i', '', $sql);
        return $sql;
    }

    private function translateUpsert(string $sql): string {
        if (stripos($sql, 'ON DUPLICATE KEY UPDATE') === false) {
            return $sql;
        }

        $conflict = null;
        if (preg_match('/INSERT\s+INTO\s+attendance\b/i', $sql)) {
            $conflict = '(employee_id, date)';
        } elseif (preg_match('/INSERT\s+INTO\s+employee_leave_credits_override\b/i', $sql)) {
            $conflict = '(employee_email, leave_type)';
        } elseif (preg_match('/INSERT\s+INTO\s+employee_signatures\b/i', $sql)) {
            $conflict = '(employee_email)';
        } elseif (preg_match('/INSERT\s+INTO\s+(hr_signatures|municipal_signatures)\b/i', $sql)) {
            $conflict = '(email)';
        }

        if (!$conflict) {
            return $sql;
        }

        $sql = preg_replace('/ON\s+DUPLICATE\s+KEY\s+UPDATE/i', "ON CONFLICT $conflict DO UPDATE SET", $sql);
        $sql = preg_replace('/VALUES\s*\(\s*([a-zA-Z_][a-zA-Z0-9_]*)\s*\)/i', 'EXCLUDED.$1', $sql);
        return $sql;
    }
}

$requiredEnv = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS'];
$missingEnv = [];
foreach ($requiredEnv as $name) {
    if (getenv($name) === false || getenv($name) === '') {
        $missingEnv[] = $name;
    }
}

if ($missingEnv) {
    throw new RuntimeException('Missing Supabase database environment variables: ' . implode(', ', $missingEnv));
}

$host    = getenv('DB_HOST');
$db      = getenv('DB_NAME');
$user    = getenv('DB_USER');
$pass    = getenv('DB_PASS');
$port    = getenv('DB_PORT') ?: '5432';
$sslmode = getenv('DB_SSLMODE') ?: 'require';

$dsn = "pgsql:host=$host;port=$port;dbname=$db;sslmode=$sslmode";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => true,
];
try {
    $pdo = new MabiniPDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    throw new PDOException($e->getMessage(), (int)$e->getCode());
}

// Helper: Generate next employee ID in format EMPYYYY-####
if (!function_exists('getNextEmployeeId')) {
    function getNextEmployeeId(PDO $pdo, ?string $year = null): string {
        $yr = $year ?: date('Y');
        // Use a short transaction to reduce race conditions
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("SELECT employee_id FROM users WHERE employee_id LIKE ? ORDER BY employee_id DESC LIMIT 1 FOR UPDATE");
            $like = sprintf('EMP%s-%%', $yr);
            $stmt->execute([$like]);
            $last = $stmt->fetchColumn();
            if ($last && preg_match('/EMP\d{4}-(\d{1,})$/', $last, $m)) {
                $seq = ((int)$m[1]) + 1;
            } else {
                $seq = 1;
            }
            $next = sprintf('EMP%s-%04d', $yr, $seq);
            $pdo->commit();
            return $next;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            // Fallback without transaction
            $stmt = $pdo->prepare("SELECT employee_id FROM users WHERE employee_id LIKE ? ORDER BY employee_id DESC LIMIT 1");
            $like = sprintf('EMP%s-%%', $yr);
            $stmt->execute([$like]);
            $last = $stmt->fetchColumn();
            if ($last && preg_match('/EMP\d{4}-(\d{1,})$/', $last, $m)) {
                $seq = ((int)$m[1]) + 1;
            } else {
                $seq = 1;
            }
            return sprintf('EMP%s-%04d', $yr, $seq);
        }
    }
}

// Base path for the application. Automatically detects the correct path for both local and hosted environments.
if (!defined('BASE_PATH')) {
    // Detect the base path dynamically from the current script location
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    
    // If we're in a subdirectory like /capstone/something/file.php or /myapp/something/file.php
    // Extract the first directory level as the base path
    if ($scriptName) {
        // Remove the filename and any subdirectories
        $scriptDir = dirname($scriptName);
        
        // For files in root subdirectories like /capstone/index.php, scriptDir will be /capstone
        // For files in nested dirs like /capstone/api/file.php, we need to go up to /capstone
        // For files at root like /index.php, scriptDir will be /
        
        // Find the first directory level after root
        $parts = explode('/', trim($scriptDir, '/'));
        
        if (!empty($parts) && $parts[0] !== '') {
            // We're in a subdirectory - use the first part
            $basePath = '/' . $parts[0];
        } else {
            // We're at root level
            $basePath = '';
        }
    } else {
        // Fallback: empty means root
        $basePath = '';
    }
    
    define('BASE_PATH', $basePath);
}
