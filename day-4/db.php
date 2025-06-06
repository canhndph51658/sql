<?php
// Kết nối PDO đến database OnlineLearning
$host = 'localhost';
$db   = 'day-4';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Hiện lỗi
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);

    // 1. Thêm cột 'status' vào bảng Enrollments
    $sqlAlter = "ALTER TABLE Enrollments ADD COLUMN IF NOT EXISTS status VARCHAR(20) DEFAULT 'active'";
    // MySQL không hỗ trợ IF NOT EXISTS cho ADD COLUMN, nên cần kiểm tra thủ công hoặc bỏ IF NOT EXISTS
    // Thay thế bằng cách kiểm tra cột trước khi chạy lệnh:
    $stmt = $pdo->query("SHOW COLUMNS FROM Enrollments LIKE 'status'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE Enrollments ADD COLUMN status VARCHAR(20) DEFAULT 'active'");
        echo "Đã thêm cột 'status' vào bảng Enrollments.<br>";
    } else {
        echo "Cột 'status' đã tồn tại trong bảng Enrollments.<br>";
    }

    // 2. Xóa bảng Enrollments nếu cần (bật dòng dưới để xóa)
    // $pdo->exec("DROP TABLE IF EXISTS Enrollments");
    // echo "Đã xóa bảng Enrollments.<br>";

    // 3. Tạo VIEW StudentCourseView
    $sqlView = "CREATE OR REPLACE VIEW StudentCourseView AS
                SELECT s.full_name, s.email, c.title AS course_title, e.enroll_date, e.status
                FROM Enrollments e
                JOIN Students s ON e.student_id = s.student_id
                JOIN Courses c ON e.course_id = c.course_id";
    $pdo->exec($sqlView);
    echo "Đã tạo VIEW StudentCourseView.<br>";

    // 4. Tạo INDEX trên cột title của bảng Courses
    // Kiểm tra nếu index chưa tồn tại mới tạo (MySQL không hỗ trợ IF NOT EXISTS cho CREATE INDEX)
    $indexName = 'idx_course_title';
    $stmt = $pdo->prepare("SHOW INDEX FROM Courses WHERE Key_name = :indexName");
    $stmt->execute(['indexName' => $indexName]);
    if ($stmt->rowCount() == 0) {
        $pdo->exec("CREATE INDEX $indexName ON Courses(title)");
        echo "Đã tạo INDEX trên cột title của bảng Courses.<br>";
    } else {
        echo "INDEX trên cột title của bảng Courses đã tồn tại.<br>";
    }

} catch (PDOException $e) {
    echo "Lỗi: " . $e->getMessage();
}
?>
