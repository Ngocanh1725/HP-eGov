<?php

declare(strict_types=1);

$dir = new RecursiveDirectoryIterator(dirname(__DIR__));
$it = new RecursiveIteratorIterator($dir);
$errs = 0;
$count = 0;
$errorFiles = [];

foreach ($it as $f) {
    if (!$f->isFile() || $f->getExtension() !== 'php') {
        continue;
    }

    $path = $f->getPathname();
    // Bỏ qua vendor nếu có
    if (str_contains($path, DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR)) {
        continue;
    }

    $count++;
    $output = [];
    $retCode = 0;
    exec('php -l "' . str_replace('"', '', $path) . '"', $output, $retCode);

    if ($retCode !== 0) {
        $errs++;
        $errorFiles[] = [
            'file' => $path,
            'output' => implode("\n", $output)
        ];
    }
}

echo "======================================================================\n";
echo " KIỂM TRA CÚ PHÁP TOÀN DỰ ÁN HP-eGov (PHP LINT - PHP 8.2+)\n";
echo "======================================================================\n";
echo "Tổng số tệp PHP quét được: {$count}\n";
echo "Lỗi cú pháp phát hiện: {$errs}\n";

if ($errs > 0) {
    echo "\nDanh sách lỗi:\n";
    foreach ($errorFiles as $item) {
        echo "- {$item['file']}:\n{$item['output']}\n";
    }
    exit(1);
} else {
    echo "✔ 100% tệp PHP sạch và hợp lệ cú pháp tuyệt đối!\n";
    exit(0);
}
