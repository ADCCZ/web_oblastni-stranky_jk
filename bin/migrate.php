<?php

/**
 * Runs every *.sql file in migrations/ (alphabetical order) against the database
 * configured via DB_* environment variables (same variables as app/Bootstrap.php).
 *
 * Usage:
 *   docker compose exec app php bin/migrate.php
 *   php bin/migrate.php                          (if PHP + pdo_mysql run locally)
 */

declare(strict_types=1);

$env = static fn (string $name, string $default = '') => getenv($name) !== false ? getenv($name) : $default;

$host = $env('DB_HOST', '127.0.0.1');
$port = $env('DB_PORT', '3306');
$name = $env('DB_DATABASE', 'pathfinder_jk');
$dsn = $env('DB_DSN', "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4");
$user = $env('DB_USERNAME', 'root');
$password = $env('DB_PASSWORD', '');

$migrationsDir = dirname(__DIR__) . '/migrations';
$files = glob($migrationsDir . '/*.sql');
sort($files, SORT_STRING);

if ($files === [] || $files === false) {
	fwrite(STDERR, "No .sql files found in {$migrationsDir}\n");
	exit(1);
}

try {
	$pdo = new PDO($dsn, $user, $password, [
		PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
	]);
} catch (PDOException $e) {
	fwrite(STDERR, "Cannot connect to database ({$dsn}): {$e->getMessage()}\n");
	exit(1);
}

foreach ($files as $file) {
	$label = basename($file);
	echo "==> {$label}\n";
	$sql = file_get_contents($file);
	// Strip full-line comments first, otherwise a comment line glued to the
	// following statement inside the same ';'-delimited chunk hides real SQL.
	$sql = preg_replace('/^\s*--.*$/m', '', $sql);

	foreach (array_filter(array_map('trim', explode(';', $sql))) as $statement) {
		if ($statement === '') {
			continue;
		}
		try {
			$pdo->exec($statement);
		} catch (PDOException $e) {
			// 1060 = duplicate column, 1061 = duplicate key, 1050 = table already exists
			// Safe to skip on re-run since migrations are not tracked individually.
			if (in_array((int) $e->errorInfo[1], [1060, 1061, 1050], true)) {
				echo "    skipped (already applied): {$e->getMessage()}\n";
				continue;
			}
			fwrite(STDERR, "    FAILED: {$e->getMessage()}\n");
			exit(1);
		}
	}
}

echo "Done.\n";
