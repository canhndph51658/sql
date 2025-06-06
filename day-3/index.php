<?php
require 'db.php'; // Kết nối CSDL

echo "<!DOCTYPE html><html lang='vi'><head>
    <meta charset='UTF-8'>
    <title>Truy vấn quản lý tuyển dụng</title>
    <style>
        body { font-family: Arial; padding: 20px; }
        h3 { color: #2c3e50; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; }
        th { background-color: #f2f2f2; }
        hr { margin: 40px 0; }
    </style>
</head><body>";

/* 1. EXISTS - Ứng viên ứng tuyển IT */
echo "<h3>1. Ứng viên đã từng ứng tuyển công việc phòng 'IT' (EXISTS)</h3>";
$sql1 = "
SELECT DISTINCT c.*
FROM Candidates c
WHERE EXISTS (
    SELECT 1
    FROM Applications a
    JOIN Jobs j ON a.job_id = j.job_id
    WHERE a.candidate_id = c.candidate_id
      AND j.department = 'IT'
)";
$result1 = $conn->query($sql1);
if ($result1->num_rows > 0) {
    echo "<table><tr><th>ID</th><th>Họ tên</th><th>Email</th><th>Phone</th></tr>";
    while($row = $result1->fetch_assoc()) {
        echo "<tr><td>{$row['candidate_id']}</td><td>{$row['full_name']}</td><td>{$row['email']}</td><td>{$row['phone']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "Không tìm thấy ứng viên nào.";
}
echo "<hr>";

/* 2. ANY - Job có max_salary > bất kỳ expected_salary */
echo "<h3>2. Công việc có lương tối đa lớn hơn mong đợi của bất kỳ ứng viên nào (ANY)</h3>";
$sql2 = "
SELECT *
FROM Jobs
WHERE max_salary > ANY (
    SELECT expected_salary FROM Candidates
)";
$result2 = $conn->query($sql2);
if ($result2->num_rows > 0) {
    echo "<table><tr><th>ID</th><th>Chức danh</th><th>Phòng</th><th>Max Lương</th></tr>";
    while($row = $result2->fetch_assoc()) {
        echo "<tr><td>{$row['job_id']}</td><td>{$row['title']}</td><td>{$row['department']}</td><td>{$row['max_salary']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "Không có công việc thỏa điều kiện.";
}
echo "<hr>";

/* 3. ALL - Job có min_salary > tất cả expected_salary */
echo "<h3>3. Công việc có lương tối thiểu lớn hơn mọi mức mong đợi của ứng viên (ALL)</h3>";
$sql3 = "
SELECT *
FROM Jobs
WHERE min_salary > ALL (
    SELECT expected_salary FROM Candidates
)";
$result3 = $conn->query($sql3);
if ($result3->num_rows > 0) {
    echo "<table><tr><th>ID</th><th>Chức danh</th><th>Phòng</th><th>Min Lương</th></tr>";
    while($row = $result3->fetch_assoc()) {
        echo "<tr><td>{$row['job_id']}</td><td>{$row['title']}</td><td>{$row['department']}</td><td>{$row['min_salary']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "Không có công việc thỏa điều kiện.";
}
echo "<hr>";

/* 4. INSERT SELECT - Accepted => ShortlistedCandidates */
echo "<h3>4. Chèn vào bảng ShortlistedCandidates các ứng viên có trạng thái 'Accepted'</h3>";
$sql4 = "
INSERT INTO ShortlistedCandidates (candidate_id, job_id, selection_date)
SELECT candidate_id, job_id, CURDATE()
FROM Applications
WHERE status = 'Accepted'
";
if ($conn->query($sql4) === TRUE) {
    echo "✅ Đã chèn dữ liệu thành công vào bảng ShortlistedCandidates.";
} else {
    echo "❌ Lỗi khi chèn dữ liệu: " . $conn->error;
}
echo "<hr>";

/* 5. CASE - đánh giá mức kinh nghiệm */
echo "<h3>5. Đánh giá mức kinh nghiệm ứng viên (CASE)</h3>";
$sql5 = "
SELECT candidate_id, full_name, years_exp,
  CASE
    WHEN years_exp < 1 THEN 'Fresher'
    WHEN years_exp BETWEEN 1 AND 3 THEN 'Junior'
    WHEN years_exp BETWEEN 4 AND 6 THEN 'Mid-level'
    ELSE 'Senior'
  END AS experience_level
FROM Candidates
";
$result5 = $conn->query($sql5);
if ($result5->num_rows > 0) {
    echo "<table><tr><th>ID</th><th>Họ tên</th><th>Số năm KN</th><th>Phân loại</th></tr>";
    while($row = $result5->fetch_assoc()) {
        echo "<tr><td>{$row['candidate_id']}</td><td>{$row['full_name']}</td><td>{$row['years_exp']}</td><td>{$row['experience_level']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "Không có ứng viên nào.";
}
echo "<hr>";

/* 6. COALESCE - xử lý số điện thoại NULL */
echo "<h3>6. Danh sách ứng viên, nếu thiếu số điện thoại thì ghi là 'Chưa cung cấp' (COALESCE)</h3>";
$sql6 = "
SELECT candidate_id, full_name, email,
       COALESCE(phone, 'Chưa cung cấp') AS phone
FROM Candidates
";
$result6 = $conn->query($sql6);
if ($result6->num_rows > 0) {
    echo "<table><tr><th>ID</th><th>Họ tên</th><th>Email</th><th>Phone</th></tr>";
    while($row = $result6->fetch_assoc()) {
        echo "<tr><td>{$row['candidate_id']}</td><td>{$row['full_name']}</td><td>{$row['email']}</td><td>{$row['phone']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "Không có ứng viên nào.";
}
echo "<hr>";

/* 7. != AND >= - lọc job theo lương */
echo "<h3>7. Công việc có lương tối đa ≠ lương tối thiểu và ≥ 1000</h3>";
$sql7 = "
SELECT *
FROM Jobs
WHERE max_salary != min_salary
  AND max_salary >= 1000
";
$result7 = $conn->query($sql7);
if ($result7->num_rows > 0) {
    echo "<table><tr><th>ID</th><th>Chức danh</th><th>Min Salary</th><th>Max Salary</th></tr>";
    while($row = $result7->fetch_assoc()) {
        echo "<tr><td>{$row['job_id']}</td><td>{$row['title']}</td><td>{$row['min_salary']}</td><td>{$row['max_salary']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "Không có công việc thỏa điều kiện.";
}

$conn->close();
echo "</body></html>";
?>
