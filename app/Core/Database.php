<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use Throwable;

/**
 * Class Database
 * Quản lý kết nối PDO Singleton, tách biệt Master (Write) và Replica (Read)
 * Cung cấp Transaction helper và cấu hình an toàn tuyệt đối.
 */
class Database
{
    /** @var PDO|null Kết nối ghi (Master) */
    private static ?PDO $writePdo = null;

    /** @var PDO|null Kết nối đọc (Replica) */
    private static ?PDO $readPdo = null;

    /**
     * Lấy kết nối PDO Master (cho thao tác INSERT, UPDATE, DELETE, DDL, Transaction)
     *
     * @return PDO
     */
    public static function getWritePdo(): PDO
    {
        if (self::$writePdo === null) {
            self::$writePdo = self::createConnection('write');
        }
        return self::$writePdo;
    }

    /**
     * Alias tiện ích lấy kết nối PDO mặc định (Master)
     */
    public static function connection(): PDO
    {
        return self::getWritePdo();
    }

    /**
     * Alias tiện ích lấy kết nối PDO
     */
    public static function pdo(): PDO
    {
        return self::getWritePdo();
    }

    /**
     * Lấy kết nối PDO Read (cho thao tác SELECT)
     * Nếu không cấu hình Host riêng cho Replica thì tự động tái sử dụng kết nối Master.
     *
     * @return PDO
     */
    public static function getReadPdo(): PDO
    {
        // Nếu đang trong Transaction, mọi câu lệnh SELECT phải đi qua Master để đảm bảo tính nhất quán (Read-Your-Writes)
        if (self::$writePdo !== null && self::$writePdo->inTransaction()) {
            return self::$writePdo;
        }

        if (self::$readPdo === null) {
            $readHost = Config::get('database.connections.mysql.read.host');
            if (empty($readHost)) {
                // Không cấu hình Replica riêng -> dùng chung Master
                self::$readPdo = self::getWritePdo();
            } else {
                self::$readPdo = self::createConnection('read');
            }
        }

        return self::$readPdo;
    }

    /**
     * Khởi tạo kết nối PDO an toàn theo cấu hình
     *
     * @param string $type 'write' hoặc 'read'
     * @return PDO
     */
    private static function createConnection(string $type): PDO
    {
        $cfg = Config::get("database.connections.mysql.{$type}");
        if (empty($cfg) || empty($cfg['host'])) {
            // Fallback sang cấu hình write nếu read bị trống
            $cfg = Config::get('database.connections.mysql.write');
        }

        $host = $cfg['host'] ?? '127.0.0.1';
        $port = $cfg['port'] ?? 3306;
        $db = $cfg['database'] ?? 'hp_egov';
        $user = $cfg['username'] ?? 'root';
        $pass = $cfg['password'] ?? '';
        $charset = $cfg['charset'] ?? 'utf8mb4';

        $dsn = "mysql:host={$host};port={$port};dbname={$db};charset={$charset}";

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$charset} COLLATE utf8mb4_unicode_ci",
        ];

        try {
            return new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            // Ghi log lỗi bảo mật, không để lộ password trong message
            Logger::error("Lỗi kết nối CSDL ({$type}): " . $e->getMessage());
            throw new PDOException("Không thể kết nối đến máy chủ cơ sở dữ liệu ({$type}).", (int)$e->getCode());
        }
    }

    /**
     * Thực thi một khối lệnh bên trong Transaction
     * Tự động Commit nếu thành công, Rollback nếu xảy ra Throwable
     *
     * @template T
     * @param callable(PDO): T $callback Hàm xử lý logic nhận vào Write PDO
     * @return T
     * @throws Throwable
     */
    public static function transaction(callable $callback): mixed
    {
        $pdo = self::getWritePdo();
        $isRoot = !$pdo->inTransaction();

        if ($isRoot) {
            $pdo->beginTransaction();
        }

        try {
            $result = $callback($pdo);

            if ($isRoot && $pdo->inTransaction()) {
                $pdo->commit();
            }

            return $result;
        } catch (Throwable $e) {
            if ($isRoot && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Đóng các kết nối PDO (tiện ích giải phóng tài nguyên)
     */
    public static function disconnect(): void
    {
        self::$writePdo = null;
        self::$readPdo = null;
    }
}
