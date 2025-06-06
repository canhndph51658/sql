<?php
require 'db.php'; // include file kết nối ở đây

// Hàm chạy truy vấn và in bảng HTML đẹp hơn với CSS
function runQueryAndPrintTable(PDO $pdo, string $sql, string $title) {
    echo "<h3 style='color:#2F4F4F;'>$title</h3>";
    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($rows) === 0) {
        echo "<p>Không có dữ liệu.</p>";
        return;
    }

    // CSS cho bảng
   echo <<<HTML
<style>
    table.custom-table {
        border-collapse: collapse;
        width: 90%;
        max-width: 900px;
        margin: 20px auto 40px; /* căn giữa bảng */
        font-family: Arial, sans-serif;
        font-size: 14px;
        table-layout: fixed; /* chia đều cột */
        word-wrap: break-word; /* xuống dòng nếu chữ quá dài */
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        border: 1px solid #ccc;
    }
    table.custom-table th, table.custom-table td {
        border: 1px solid #ddd;
        padding: 10px 12px;
        text-align: center;
        vertical-align: middle;
        overflow: hidden; /* tránh tràn */
        white-space: nowrap; /* không xuống dòng cho cột id, số */
        text-overflow: ellipsis; /* dấu ... khi quá dài */
    }
    /* Cho phép các cột text dài được xuống dòng */
    table.custom-table td:nth-child(n+2) {
        white-space: normal;
    }
    table.custom-table th {
        background-color: #2F4F4F;
        color: white;
        font-weight: 600;
        user-select: none;
    }
    table.custom-table tr:nth-child(even) {
        background-color: #f9f9f9;
    }
    table.custom-table tr:hover {
        background-color: #d4e6d4;
    }
    /* Tiêu đề bảng */
    h3 {
        text-align: center;
        color: #2F4F4F;
        margin-top: 40px;
        margin-bottom: 10px;
        font-weight: 700;
    }
    p {
        text-align: center;
        font-style: italic;
        color: #666;
        margin-bottom: 40px;
    }
</style>
HTML;

    echo "<table class='custom-table'>";
    // header
    echo "<thead><tr>";
    foreach (array_keys($rows[0]) as $colName) {
        echo "<th>" . htmlspecialchars($colName) . "</th>";
    }
    echo "</tr></thead>";

    // data rows
    echo "<tbody>";
    foreach ($rows as $row) {
        echo "<tr>";
        foreach ($row as $val) {
            echo "<td>" . htmlspecialchars($val ?? '') . "</td>";
        }
        echo "</tr>";
    }
    echo "</tbody>";
    echo "</table>";
}

// --- Các truy vấn SQL giữ nguyên ---

$sql1 = "
SELECT p.category, SUM(p.price * oi.quantity) AS total_revenue
FROM Orders o
JOIN OrderItems oi ON o.order_id = oi.order_id
JOIN Products p ON oi.product_id = p.product_id
WHERE o.status = 'completed'
GROUP BY p.category
ORDER BY total_revenue DESC
";
runQueryAndPrintTable($pdo, $sql1, "1. Tổng doanh thu theo danh mục sản phẩm");

$sql2 = "
SELECT u.user_id, u.full_name, r.full_name AS referrer_name
FROM Users u
LEFT JOIN Users r ON u.referrer_id = r.user_id
ORDER BY u.user_id
";
runQueryAndPrintTable($pdo, $sql2, "2. Danh sách người dùng kèm tên người giới thiệu");

$sql3 = "
SELECT DISTINCT p.product_id, p.product_name, p.category
FROM Products p
JOIN OrderItems oi ON p.product_id = oi.product_id
WHERE p.is_active = 0
";
runQueryAndPrintTable($pdo, $sql3, "3. Sản phẩm đã từng đặt mua nhưng không còn active");

$sql4 = "
SELECT u.user_id, u.full_name
FROM Users u
LEFT JOIN Orders o ON u.user_id = o.user_id
WHERE o.order_id IS NULL
";
runQueryAndPrintTable($pdo, $sql4, "4. Người dùng chưa từng đặt đơn hàng");

$sql5 = "
SELECT o.user_id, o.order_id, o.order_date
FROM Orders o
INNER JOIN (
    SELECT user_id, MIN(order_date) AS first_order_date
    FROM Orders
    GROUP BY user_id
) fo ON o.user_id = fo.user_id AND o.order_date = fo.first_order_date
ORDER BY o.user_id
";
runQueryAndPrintTable($pdo, $sql5, "5. Đơn hàng đầu tiên của từng người dùng");

$sql6 = "
SELECT u.user_id, u.full_name, 
       COALESCE(SUM(p.price * oi.quantity), 0) AS total_spent
FROM Users u
LEFT JOIN Orders o ON u.user_id = o.user_id AND o.status = 'completed'
LEFT JOIN OrderItems oi ON o.order_id = oi.order_id
LEFT JOIN Products p ON oi.product_id = p.product_id
GROUP BY u.user_id, u.full_name
ORDER BY total_spent DESC
";
runQueryAndPrintTable($pdo, $sql6, "6. Tổng chi tiêu của mỗi người dùng (completed orders)");

$sql7 = "
SELECT u.user_id, u.full_name, 
       SUM(p.price * oi.quantity) AS total_spent
FROM Users u
JOIN Orders o ON u.user_id = o.user_id AND o.status = 'completed'
JOIN OrderItems oi ON o.order_id = oi.order_id
JOIN Products p ON oi.product_id = p.product_id
GROUP BY u.user_id, u.full_name
HAVING total_spent > 25000000
ORDER BY total_spent DESC
";
runQueryAndPrintTable($pdo, $sql7, "7. Người dùng chi tiêu > 25 triệu");

$sql8 = "
SELECT u.city, 
       COUNT(DISTINCT o.order_id) AS total_orders, 
       COALESCE(SUM(p.price * oi.quantity), 0) AS total_revenue
FROM Users u
LEFT JOIN Orders o ON u.user_id = o.user_id AND o.status = 'completed'
LEFT JOIN OrderItems oi ON o.order_id = oi.order_id
LEFT JOIN Products p ON oi.product_id = p.product_id
GROUP BY u.city
ORDER BY total_revenue DESC
";
runQueryAndPrintTable($pdo, $sql8, "8. Tổng số đơn hàng và doanh thu theo thành phố");

$sql9 = "
SELECT u.user_id, u.full_name, COUNT(o.order_id) AS completed_orders
FROM Users u
JOIN Orders o ON u.user_id = o.user_id AND o.status = 'completed'
GROUP BY u.user_id, u.full_name
HAVING completed_orders >= 2
ORDER BY completed_orders DESC
";
runQueryAndPrintTable($pdo, $sql9, "9. Người dùng có ít nhất 2 đơn hàng completed");

$sql10 = "
SELECT oi.order_id
FROM OrderItems oi
JOIN Products p ON oi.product_id = p.product_id
GROUP BY oi.order_id
HAVING COUNT(DISTINCT p.category) > 1
";
runQueryAndPrintTable($pdo, $sql10, "10. Đơn hàng có sản phẩm thuộc nhiều hơn 1 danh mục");

$sql11 = "
SELECT DISTINCT u.user_id, u.full_name, 'placed_order' AS source
FROM Users u
JOIN Orders o ON u.user_id = o.user_id

UNION

SELECT DISTINCT u.user_id, u.full_name, 'referred' AS source
FROM Users u
WHERE u.referrer_id IS NOT NULL
ORDER BY user_id
";
runQueryAndPrintTable($pdo, $sql11, "11. Kết hợp danh sách người dùng đặt hàng và được giới thiệu");

?>
