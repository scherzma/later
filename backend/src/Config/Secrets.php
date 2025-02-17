class Secrets {
private static function readSecret(string $name): string {
$file = "/run/secrets/$name";
if (!file_exists($file)) {
throw new RuntimeException("Secret not found: $name");
}
return trim(file_get_contents($file));
}

public static function getDatabasePassword(): string {
return self::readSecret('db_password');
}

public static function getRedisPassword(): string {
return self::readSecret('redis_password');
}

public static function getJwtSecret(): string {
return self::readSecret('jwt_secret');
}
}