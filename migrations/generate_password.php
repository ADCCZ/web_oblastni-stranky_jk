<?php
/**
 * Pomocny skript pro generovani hash hesla
 * Spust: php migrations/generate_password.php
 */

$password = 'admin123'; // Zmen na sve heslo

$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

echo "Heslo: {$password}\n";
echo "Hash:  {$hash}\n";
echo "\n";
echo "SQL pro vlozeni admina:\n";
echo "INSERT INTO users (email, password_hash, first_name, last_name, role, is_active) VALUES\n";
echo "    ('admin@jiznirkiz.cz', '{$hash}', 'Admin', 'Pathfinder', 'admin', TRUE);\n";
