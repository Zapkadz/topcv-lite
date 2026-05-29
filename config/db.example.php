<?php
/**
 * Mẫu cấu hình database — KHÔNG chứa secret production.
 *
 * Sao chép thành db.local.php (file đó nằm trong .gitignore):
 *   copy config\db.example.php config\db.local.php
 *
 * db.php sẽ merge mảng này lên giá trị mặc định XAMPP.
 */
return [
    'host'     => 'localhost',
    'dbname'   => 'topcv_lite',
    'username' => 'root',
    'password' => '',
    'base_url' => 'http://localhost/topcv_lite/',
];
