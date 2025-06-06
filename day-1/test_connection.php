<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Thực hành SQL tổng hợp</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <div class="container mt-5">
        <h1 class="mb-4 text-primary">🧩 Báo cáo SQL tổng hợp</h1>
        <?php
        require 'db.php'; // include file kết nối ở đây
        function showTable($result, $columns)
        {
            echo "<table class='table table-bordered table-striped'>";
            echo "<thead><tr>";
            foreach ($columns as $col) echo "<th>$col</th>";
            echo "</tr></thead><tbody>";
            while ($row = $result->fetch_assoc()) {
                echo "<tr>";
                foreach ($columns as $col) echo "<td>{$row[$col]}</td>";
                echo "</tr>";
            }
            echo "</tbody></table>";
        }

        // 1
        echo "<h4>1. Khách hàng ở Hà Nội</h4>";
        $result = $conn->query("SELECT name, city FROM Customers WHERE city = 'Hanoi'");
        showTable($result, ['name', 'city']);

        // 2
        echo "<h4>2. Đơn hàng > 400.000 và sau 31/01/2023</h4>";
        $result = $conn->query("SELECT order_id, total_amount, order_date FROM Orders WHERE total_amount > 400000 AND order_date > '2023-01-31'");
        showTable($result, ['order_id', 'order_date', 'total_amount']);

        // 3
        echo "<h4>3. Khách hàng chưa có email</h4>";
        $result = $conn->query("SELECT name, city FROM Customers WHERE email IS NULL");
        showTable($result, ['name', 'city']);

        // 4
        echo "<h4>4. Danh sách đơn hàng (giảm dần theo tổng tiền)</h4>";
        $result = $conn->query("SELECT * FROM Orders ORDER BY total_amount DESC");
        showTable($result, ['order_id', 'customer_id', 'order_date', 'total_amount']);

        // 5
        echo "<h4>5. Thêm khách hàng mới</h4>";
        $conn->query("INSERT INTO Customers (name, city, email) VALUES ('Pham Thanh', 'Can Tho', NULL)");
        echo "<div class='alert alert-success'>✔️ Đã thêm Pham Thanh</div>";

        // 6
        echo "<h4>6. Cập nhật email khách hàng mã 2</h4>";
        $conn->query("UPDATE Customers SET email = 'binh.tran@email.com' WHERE customer_id = 2");
        echo "<div class='alert alert-info'>✔️ Đã cập nhật email khách hàng 2</div>";

        // 7
        echo "<h4>7. Xóa đơn hàng 103</h4>";
        $conn->query("DELETE FROM Orders WHERE order_id = 103");
        echo "<div class='alert alert-warning'>✔️ Đã xóa đơn hàng 103</div>";

        // 8
        echo "<h4>8. 2 khách hàng đầu tiên</h4>";
        $result = $conn->query("SELECT name FROM Customers LIMIT 2");
        showTable($result, ['name']);

        // 9
        echo "<h4>9. Đơn hàng lớn nhất & nhỏ nhất</h4>";
        $result = $conn->query("SELECT MAX(total_amount) AS max_val, MIN(total_amount) AS min_val FROM Orders");
        $row = $result->fetch_assoc();
        echo "<p>🔺 Lớn nhất: <strong>" . $row['max_val'] . "</strong> – 🔻 Nhỏ nhất: <strong>" . $row['min_val'] . "</strong></p>";

        // 10
        echo "<h4>10. Thống kê đơn hàng</h4>";
        $result = $conn->query("SELECT COUNT(*) AS so_luong, SUM(total_amount) AS tong_tien, AVG(total_amount) AS tb FROM Orders");
        $row = $result->fetch_assoc();
        echo "<ul>
        <li>Số đơn: <strong>{$row['so_luong']}</strong></li>
        <li>Tổng tiền: <strong>{$row['tong_tien']}</strong></li>
        <li>Trung bình: <strong>" . round($row['tb']) . "</strong></li>
    </ul>";

        // 11
        echo "<h4>11. Sản phẩm bắt đầu bằng 'Laptop'</h4>";
        $result = $conn->query("SELECT name, price FROM Products WHERE name LIKE 'Laptop%'");
        showTable($result, ['name', 'price']);

        // 12. Mô tả RDBMS
        echo "<h3>12. Mô tả RDBMS</h3>";
        echo "<ul>";
        echo "<li><strong>RDBMS (Relational Database Management System)</strong> là hệ quản trị cơ sở dữ liệu quan hệ, nơi dữ liệu được lưu trong các bảng có quan hệ với nhau qua khóa chính (Primary Key) và khóa ngoại (Foreign Key).</li>";
        echo "<li>Các mối quan hệ giúp tổ chức dữ liệu tốt hơn, tránh trùng lặp và dễ dàng truy vấn thông tin giữa các bảng như khách hàng - đơn hàng - sản phẩm.</li>";
        echo "<li>Ví dụ: bảng <em>Orders</em> có khóa ngoại <code>customer_id</code> liên kết với bảng <em>Customers</em>.</li>";
        echo "</ul>";

        $conn->close();
        ?>
    </div>
</body>

</html>