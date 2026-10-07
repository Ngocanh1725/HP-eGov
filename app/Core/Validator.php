<?php

declare(strict_types=1);

namespace App\Core;

use DateTime;
use PDO;

/**
 * Class Validator
 * Kiểm định dữ liệu đầu vào với các quy chuẩn Việt Nam (CCCD 12 số, MST, SĐT VN, Email, File MIME, File Size, Unique CSDL).
 * Trả về thông báo lỗi chi tiết bằng tiếng Việt thân thiện.
 */
class Validator
{
    private array $data;
    private array $rules;
    private array $customMessages;
    private array $errors = [];

    /**
     * Khởi tạo Validator
     *
     * @param array<string, mixed> $data Dữ liệu đầu vào
     * @param array<string, string|array<int, string>> $rules Tập luật kiểm định
     * @param array<string, string> $customMessages Thông báo tùy biến
     */
    public function __construct(array $data, array $rules, array $customMessages = [])
    {
        $this->data = $data;
        $this->rules = $rules;
        $this->customMessages = $customMessages;
    }

    /**
     * Factory phương thức tiện ích
     */
    public static function make(array $data, array $rules, array $customMessages = []): static
    {
        return new static($data, $rules, $customMessages);
    }

    /**
     * Thực hiện kiểm định
     *
     * @return bool True nếu hợp lệ, False nếu có lỗi
     */
    public function validate(): bool
    {
        $this->errors = [];

        foreach ($this->rules as $field => $fieldRules) {
            $rulesList = is_string($fieldRules) ? explode('|', $fieldRules) : $fieldRules;
            $val = $this->data[$field] ?? null;

            foreach ($rulesList as $ruleItem) {
                $ruleParts = explode(':', $ruleItem, 2);
                $ruleName = trim($ruleParts[0]);
                $ruleParam = $ruleParts[1] ?? '';

                $this->applyRule($field, $val, $ruleName, $ruleParam);
            }
        }

        if (!empty($this->errors)) {
            // Flash lỗi và dữ liệu cũ vào Session để view hiển thị
            Session::flash('_errors', $this->errors);
            Session::flash('_old_input', $this->data);
            return false;
        }

        return true;
    }

    /**
     * Kiểm tra trạng thái hợp lệ
     */
    public function passes(): bool
    {
        return $this->validate();
    }

    /**
     * Kiểm tra trạng thái thất bại
     */
    public function fails(): bool
    {
        return !$this->validate();
    }

    /**
     * Lấy danh sách toàn bộ lỗi
     *
     * @return array<string, array<int, string>>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Lấy lỗi đầu tiên của một trường
     */
    public function first(string $field): ?string
    {
        return $this->errors[$field][0] ?? null;
    }

    /**
     * Áp dụng quy tắc kiểm tra
     */
    private function applyRule(string $field, mixed $val, string $ruleName, string $param): void
    {
        // Nếu không required và giá trị trống thì bỏ qua các rule khác (ngoại trừ file)
        $isEmpty = ($val === null || $val === '' || (is_array($val) && empty($val)));
        if ($ruleName !== 'required' && $isEmpty && !str_starts_with($ruleName, 'file_')) {
            return;
        }

        switch ($ruleName) {
            case 'required':
                if ($isEmpty) {
                    $this->addError($field, 'required', "Trường {$field} là bắt buộc và không được để trống.");
                }
                break;

            case 'email':
                if (!filter_var($val, FILTER_VALIDATE_EMAIL)) {
                    $this->addError($field, 'email', "Trường {$field} phải là địa chỉ email hợp lệ.");
                }
                break;

            case 'cccd':
                // Căn cước công dân: đúng 12 chữ số theo quy chuẩn Đề án 06
                if (!is_string($val) || !preg_match('/^[0-9]{12}$/', trim($val))) {
                    $this->addError($field, 'cccd', "Số CCCD phải bao gồm đúng 12 chữ số.");
                }
                break;

            case 'mst':
                // Mã số thuế doanh nghiệp: 10 chữ số hoặc 13 chữ số (chi nhánh)
                if (!is_string($val) || !preg_match('/^[0-9]{10}(-[0-9]{3}|[0-9]{3})?$/', trim($val))) {
                    $this->addError($field, 'mst', "Mã số thuế không đúng định dạng (10 số hoặc 13 số).");
                }
                break;

            case 'phone':
                // Số điện thoại Việt Nam 10 số (bắt đầu bằng 03, 05, 07, 08, 09)
                if (!is_string($val) || !preg_match('/^(03|05|07|08|09)[0-9]{8}$/', trim($val))) {
                    $this->addError($field, 'phone', "Số điện thoại di động không hợp lệ (phải gồm 10 số, đầu mạng VN).");
                }
                break;

            case 'numeric':
                if (!is_numeric($val)) {
                    $this->addError($field, 'numeric', "Trường {$field} phải là chữ số.");
                }
                break;

            case 'min':
                $min = (int)$param;
                if (is_numeric($val) && (float)$val < $min) {
                    $this->addError($field, 'min', "Giá trị {$field} tối thiểu phải là {$min}.");
                } elseif (is_string($val) && mb_strlen($val, 'UTF-8') < $min) {
                    $this->addError($field, 'min', "Trường {$field} phải có độ dài tối thiểu {$min} ký tự.");
                }
                break;

            case 'max':
                $max = (int)$param;
                if (is_numeric($val) && (float)$val > $max) {
                    $this->addError($field, 'max', "Giá trị {$field} tối đa không được vượt quá {$max}.");
                } elseif (is_string($val) && mb_strlen($val, 'UTF-8') > $max) {
                    $this->addError($field, 'max', "Trường {$field} không được vượt quá {$max} ký tự.");
                }
                break;

            case 'date':
                $format = $param ?: 'Y-m-d';
                $d = DateTime::createFromFormat($format, (string)$val);
                if (!$d || $d->format($format) !== (string)$val) {
                    $this->addError($field, 'date', "Trường {$field} không phải ngày hợp lệ theo định dạng {$format}.");
                }
                break;

            case 'in':
                $allowed = explode(',', $param);
                if (!in_array((string)$val, $allowed, true)) {
                    $this->addError($field, 'in', "Giá trị {$field} phải là một trong các giá trị: " . implode(', ', $allowed));
                }
                break;

            case 'unique':
                // format: unique:table,column,except_id,id_column
                $params = explode(',', $param);
                $table = $params[0] ?? '';
                $column = $params[1] ?? $field;
                $exceptId = $params[2] ?? null;
                $idColumn = $params[3] ?? 'id';

                if (!empty($table)) {
                    $sql = "SELECT COUNT(*) as count FROM `{$table}` WHERE `{$column}` = :val";
                    $bindings = [':val' => $val];

                    if ($exceptId !== null && $exceptId !== '') {
                        $sql .= " AND `{$idColumn}` != :except_id";
                        $bindings[':except_id'] = $exceptId;
                    }

                    $stmt = Database::getReadPdo()->prepare($sql);
                    $stmt->execute($bindings);
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ((int)($row['count'] ?? 0) > 0) {
                        $this->addError($field, 'unique', "Trường {$field} đã tồn tại trên hệ thống.");
                    }
                }
                break;

            case 'file_mime':
                $allowedMimes = explode(',', $param);
                $file = $this->data[$field] ?? ($_FILES[$field] ?? null);
                if (is_array($file) && !empty($file['tmp_name']) && file_exists($file['tmp_name'])) {
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime = $finfo ? finfo_file($finfo, $file['tmp_name']) : '';
                    if ($finfo) {
                        finfo_close($finfo);
                    }
                    if (!in_array($mime, $allowedMimes, true)) {
                        $this->addError($field, 'file_mime', "Định dạng tệp không được chấp nhận ({$mime}).");
                    }
                }
                break;

            case 'file_size':
                // max size in MB
                $maxMb = (float)$param;
                $maxBytes = $maxMb * 1024 * 1024;
                $file = $this->data[$field] ?? ($_FILES[$field] ?? null);
                if (is_array($file) && isset($file['size']) && (float)$file['size'] > $maxBytes) {
                    $this->addError($field, 'file_size', "Dung lượng tệp vượt quá mức cho phép ({$maxMb}MB).");
                }
                break;
        }
    }

    /**
     * Thêm thông báo lỗi
     */
    private function addError(string $field, string $rule, string $defaultMessage): void
    {
        $customKey = "{$field}.{$rule}";
        $message = $this->customMessages[$customKey] ??
            $this->customMessages[$field] ??
            $defaultMessage;

        $this->errors[$field][] = $message;
    }
}
