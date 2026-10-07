<?php

declare(strict_types=1);

namespace App\Core;

use LogicException;
use InvalidArgumentException;
use PDO;

/**
 * Class Model
 * Lớp cơ sở cho các thực thể dữ liệu trong hệ thống HP-eGov.
 * Đảm bảo 100% truy vấn dùng PDO prepared statement, kiểm soát $fillable whitelist.
 * TUYỆT ĐỐI KHÔNG CUNG CẤP hàm xóa vật lý (chặn triệt để theo §7 Master Rules).
 */
abstract class Model
{
    /** @var string Tên bảng CSDL */
    protected string $table = '';

    /** @var string Tên khóa chính */
    protected string $primaryKey = 'id';

    /** @var array<int, string> Danh sách cột được phép nạp dữ liệu (Mass Assignment Whitelist) */
    protected array $fillable = [];

    /** @var bool Có tự động cập nhật created_at, updated_at hay không */
    protected bool $timestamps = true;

    /** @var string Tên cột cờ xóa logic */
    protected string $softDeleteColumn = 'is_deleted';

    /** @var array<int, array{column: string, operator: string, value: mixed}> Các điều kiện WHERE */
    protected array $wheres = [];

    /** @var array<int, array{column: string, values: array<int, mixed>}> Các điều kiện WHERE IN */
    protected array $whereIns = [];

    /** @var array<int, array{column: string, direction: string}> Các mệnh đề ORDER BY */
    protected array $orders = [];

    /** @var int|null Giới hạn bản ghi */
    protected ?int $limit = null;

    /** @var int|null Vị trí bắt đầu */
    protected ?int $offset = null;

    /**
     * Khởi tạo Model
     */
    public function __construct()
    {
        if (empty($this->table)) {
            throw new LogicException('Tên bảng ($table) phải được định nghĩa trong class con.');
        }
    }

    /**
     * Tạo một truy vấn mới
     *
     * @return static
     */
    public static function query(): static
    {
        return new static();
    }

    /**
     * Lấy kết nối ghi (Master)
     */
    protected function getWritePdo(): PDO
    {
        return Database::getWritePdo();
    }

    /**
     * Lấy kết nối đọc (Replica)
     */
    protected function getReadPdo(): PDO
    {
        return Database::getReadPdo();
    }

    /**
     * Thêm điều kiện WHERE
     *
     * @param string $column Tên cột
     * @param mixed $operator Toán tử hoặc giá trị (nếu 2 tham số)
     * @param mixed $value Giá trị cần so khớp
     * @return static
     */
    public function where(string $column, mixed $operator = '=', mixed $value = null): static
    {
        if ($value === null && $operator !== null) {
            $value = $operator;
            $operator = '=';
        }

        $this->sanitizeColumnName($column);
        $operator = strtoupper(trim((string)$operator));

        $allowedOperators = ['=', '!=', '<>', '<', '<=', '>', '>=', 'LIKE', 'NOT LIKE'];
        if (!in_array($operator, $allowedOperators, true)) {
            throw new InvalidArgumentException("Toán tử không hợp lệ: {$operator}");
        }

        $this->wheres[] = [
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
        ];

        return $this;
    }

    /**
     * Thêm điều kiện WHERE IN
     *
     * @param string $column Tên cột
     * @param array<int, mixed> $values Mảng giá trị
     * @return static
     */
    public function whereIn(string $column, array $values): static
    {
        $this->sanitizeColumnName($column);
        $this->whereIns[] = [
            'column' => $column,
            'values' => array_values($values),
        ];
        return $this;
    }

    /**
     * Sắp xếp kết quả
     *
     * @param string $column Cột sắp xếp
     * @param string $direction Hướng sắp xếp (ASC | DESC)
     * @return static
     */
    public function orderBy(string $column, string $direction = 'ASC'): static
    {
        $this->sanitizeColumnName($column);
        $direction = strtoupper(trim($direction));
        if ($direction !== 'ASC' && $direction !== 'DESC') {
            $direction = 'ASC';
        }

        $this->orders[] = [
            'column' => $column,
            'direction' => $direction,
        ];

        return $this;
    }

    /**
     * Giới hạn số bản ghi
     */
    public function limit(int $limit): static
    {
        $this->limit = max(0, $limit);
        return $this;
    }

    /**
     * Vị trí bắt đầu
     */
    public function offset(int $offset): static
    {
        $this->offset = max(0, $offset);
        return $this;
    }

    /**
     * Tìm bản ghi theo Khóa chính
     *
     * @param int|string $id
     * @return array<string, mixed>|null
     */
    public static function find(int|string $id): ?array
    {
        $instance = new static();
        return $instance->where($instance->primaryKey, '=', $id)->first();
    }

    /**
     * Lấy bản ghi đầu tiên thỏa mãn điều kiện
     *
     * @return array<string, mixed>|null
     */
    public function first(): ?array
    {
        $this->limit(1);
        $results = $this->get();
        return $results[0] ?? null;
    }

    /**
     * Lấy toàn bộ danh sách bản ghi thỏa mãn điều kiện
     *
     * @return array<int, array<string, mixed>>
     */
    public function get(): array
    {
        [$sql, $bindings] = $this->buildSelectQuery();
        $stmt = $this->getReadPdo()->prepare($sql);
        $stmt->execute($bindings);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Đếm tổng số bản ghi thỏa mãn điều kiện
     *
     * @return int
     */
    public function count(): int
    {
        [$whereClause, $bindings] = $this->buildWhereClause();
        $sql = "SELECT COUNT(*) as total FROM `{$this->table}` {$whereClause}";
        $stmt = $this->getReadPdo()->prepare($sql);
        $stmt->execute($bindings);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) ($row['total'] ?? 0);
    }

    /**
     * Phân trang kết quả truy vấn
     *
     * @param int $perPage Số bản ghi mỗi trang
     * @param int|null $page Số trang hiện tại (nếu null sẽ lấy từ $_GET['page'])
     * @return Paginator
     */
    public function paginate(int $perPage = 15, ?int $page = null): Paginator
    {
        $page = $page ?? (int) ($_GET['page'] ?? 1);
        $page = max(1, $page);
        $perPage = max(1, $perPage);

        // Đếm tổng số bản ghi
        $total = $this->count();

        // Lấy dữ liệu cho trang
        $this->limit($perPage);
        $this->offset(($page - 1) * $perPage);
        $items = $this->get();

        $path = strtok($_SERVER['REQUEST_URI'] ?? '', '?') ?: '';
        $queryParams = $_GET;
        unset($queryParams['page']);

        return new Paginator($items, $total, $perPage, $page, $path, $queryParams);
    }

    /**
     * Thêm mới một bản ghi (có lọc fillable whitelist)
     *
     * @param array<string, mixed> $data Dữ liệu cần thêm
     * @return int|string ID bản ghi vừa tạo
     */
    public function insert(array $data): int|string
    {
        $filtered = $this->filterFillable($data);

        if ($this->timestamps) {
            $now = date('Y-m-d H:i:s');
            if (!isset($filtered['created_at'])) {
                $filtered['created_at'] = $now;
            }
            if (!isset($filtered['updated_at'])) {
                $filtered['updated_at'] = $now;
            }
        }

        if (empty($filtered)) {
            throw new InvalidArgumentException('Không có trường dữ liệu hợp lệ trong fillable whitelist để thêm mới.');
        }

        $columns = array_keys($filtered);
        $escapedColumns = array_map(fn($col) => "`{$col}`", $columns);
        $placeholders = array_map(fn($col) => ":{$col}", $columns);

        $sql = sprintf(
            'INSERT INTO `%s` (%s) VALUES (%s)',
            $this->table,
            implode(', ', $escapedColumns),
            implode(', ', $placeholders)
        );

        $stmt = $this->getWritePdo()->prepare($sql);
        $stmt->execute($filtered);

        return $this->getWritePdo()->lastInsertId();
    }

    /**
     * Cập nhật bản ghi theo ID (có lọc fillable whitelist)
     *
     * @param int|string $id
     * @param array<string, mixed> $data Dữ liệu cần cập nhật
     * @return bool
     */
    public function update(int|string $id, array $data): bool
    {
        $filtered = $this->filterFillable($data);

        if ($this->timestamps && !isset($filtered['updated_at'])) {
            $filtered['updated_at'] = date('Y-m-d H:i:s');
        }

        if (empty($filtered)) {
            return false;
        }

        $setParts = [];
        $bindings = [':id' => $id];

        foreach ($filtered as $column => $value) {
            $setParts[] = "`{$column}` = :{$column}";
            $bindings[":{$column}"] = $value;
        }

        $sql = sprintf(
            'UPDATE `%s` SET %s WHERE `%s` = :id',
            $this->table,
            implode(', ', $setParts),
            $this->primaryKey
        );

        $stmt = $this->getWritePdo()->prepare($sql);
        return $stmt->execute($bindings);
    }

    /**
     * Hủy / Xóa logic bản ghi (Soft Delete)
     *
     * @param int|string $id
     * @param array<string, mixed> $extraData Các thông tin giải trình/căn cứ hủy kèm theo
     * @return bool
     */
    public function softDelete(int|string $id, array $extraData = []): bool
    {
        $updateData = $extraData;

        // Nếu bảng dùng cờ is_deleted
        $updateData['is_deleted'] = 1;
        $updateData['deleted_at'] = date('Y-m-d H:i:s');

        // Bỏ qua lọc fillable cho cờ xóa hệ thống
        $setParts = [];
        $bindings = [':id' => $id];

        foreach ($updateData as $column => $value) {
            $this->sanitizeColumnName($column);
            $setParts[] = "`{$column}` = :{$column}";
            $bindings[":{$column}"] = $value;
        }

        if ($this->timestamps) {
            $setParts[] = "`updated_at` = :updated_at";
            $bindings[':updated_at'] = date('Y-m-d H:i:s');
        }

        $sql = sprintf(
            'UPDATE `%s` SET %s WHERE `%s` = :id',
            $this->table,
            implode(', ', $setParts),
            $this->primaryKey
        );

        $stmt = $this->getWritePdo()->prepare($sql);
        return $stmt->execute($bindings);
    }

    /**
     * TUYỆT ĐỐI CHẶN XÓA VẬT LÝ
     * Theo §7 Quy định bất biến dữ liệu HP-eGov:
     * Nghiêm cấm xóa vật lý hồ sơ/tài liệu/dữ liệu hành chính.
     *
     * @throws LogicException
     */
    public function delete(mixed ...$args): void
    {
        throw new LogicException(
            "[HP-eGov Bảo mật] Thao tác xóa vật lý (DELETE) bị cấm tuyệt đối theo Quy chế Kiến trúc & BCP/DRP. " .
            "Vui lòng sử dụng hàm softDelete() để đánh dấu hủy logic kèm căn cứ giải trình."
        );
    }

    /**
     * Lọc dữ liệu qua Whitelist $fillable
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function filterFillable(array $data): array
    {
        if (empty($this->fillable)) {
            return $data;
        }

        $filtered = [];
        foreach ($this->fillable as $allowedCol) {
            if (array_key_exists($allowedCol, $data)) {
                $filtered[$allowedCol] = $data[$allowedCol];
            }
        }

        return $filtered;
    }

    /**
     * Kiểm tra tính hợp lệ của tên cột để chống SQL Injection
     */
    protected function sanitizeColumnName(string $column): void
    {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $column)) {
            throw new InvalidArgumentException("Tên cột CSDL không an toàn: {$column}");
        }
    }

    /**
     * Xây dựng mệnh đề WHERE và mảng Bindings
     *
     * @return array{0: string, 1: array<string, mixed>}
     */
    protected function buildWhereClause(): array
    {
        $clauses = [];
        $bindings = [];
        $idx = 0;

        foreach ($this->wheres as $w) {
            $paramName = ":p_where_{$idx}";
            $clauses[] = "`{$w['column']}` {$w['operator']} {$paramName}";
            $bindings[$paramName] = $w['value'];
            $idx++;
        }

        foreach ($this->whereIns as $wi) {
            $inPlaceholders = [];
            foreach ($wi['values'] as $v) {
                $paramName = ":p_in_{$idx}";
                $inPlaceholders[] = $paramName;
                $bindings[$paramName] = $v;
                $idx++;
            }
            if (!empty($inPlaceholders)) {
                $clauses[] = sprintf("`%s` IN (%s)", $wi['column'], implode(', ', $inPlaceholders));
            } else {
                $clauses[] = "1 = 0"; // Mảng rỗng -> điều kiện luôn sai
            }
        }

        $sql = empty($clauses) ? '' : 'WHERE ' . implode(' AND ', $clauses);
        return [$sql, $bindings];
    }

    /**
     * Xây dựng câu truy vấn SELECT hoàn chỉnh
     *
     * @return array{0: string, 1: array<string, mixed>}
     */
    protected function buildSelectQuery(): array
    {
        [$whereClause, $bindings] = $this->buildWhereClause();

        $orderClause = '';
        if (!empty($this->orders)) {
            $orderParts = array_map(fn($o) => "`{$o['column']}` {$o['direction']}", $this->orders);
            $orderClause = 'ORDER BY ' . implode(', ', $orderParts);
        }

        $limitClause = '';
        if ($this->limit !== null) {
            $limitClause = "LIMIT {$this->limit}";
            if ($this->offset !== null) {
                $limitClause .= " OFFSET {$this->offset}";
            }
        }

        $sql = trim("SELECT * FROM `{$this->table}` {$whereClause} {$orderClause} {$limitClause}");
        return [$sql, $bindings];
    }
}
