<?php
// 1. Thông tin kết nối
$servername = "localhost";   // thường localhost nếu Laragon cài mặc định
$username = "root";          // user mặc định của MySQL trên Laragon
$password = "";              // thường để trống trên Laragon
$dbname = "day-3";      // tên database bạn đã tạo

// 2. Tạo kết nối
$conn = new mysqli($servername, $username, $password, $dbname);

// 3. Kiểm tra kết nối
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}