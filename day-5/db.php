<?php
// Config kết nối DB
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "HotelBookingDB";

try {
    $pdo = new PDO("mysql:host=$servername;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Tạo database nếu chưa có
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$dbname`");

    // 2. Tạo bảng nếu chưa có
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS Rooms (
            room_id INT AUTO_INCREMENT PRIMARY KEY,
            room_number VARCHAR(10) UNIQUE NOT NULL,
            type VARCHAR(20) NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'Available',
            price INT NOT NULL CHECK (price >= 0)
        );
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS Guests (
            guest_id INT AUTO_INCREMENT PRIMARY KEY,
            full_name VARCHAR(100) NOT NULL,
            phone VARCHAR(20) NOT NULL
        );
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS Bookings (
            booking_id INT AUTO_INCREMENT PRIMARY KEY,
            guest_id INT NOT NULL,
            room_id INT NOT NULL,
            check_in DATE NOT NULL,
            check_out DATE NOT NULL,
            status VARCHAR(20) NOT NULL,
            FOREIGN KEY (guest_id) REFERENCES Guests(guest_id) ON DELETE CASCADE,
            FOREIGN KEY (room_id) REFERENCES Rooms(room_id) ON DELETE CASCADE
        );
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS Invoices (
            invoice_id INT AUTO_INCREMENT PRIMARY KEY,
            booking_id INT NOT NULL,
            total_amount INT NOT NULL,
            generated_date DATE NOT NULL,
            FOREIGN KEY (booking_id) REFERENCES Bookings(booking_id) ON DELETE CASCADE
        );
    ");

    // 3. Thêm dữ liệu mẫu (nếu chưa có)
    $pdo->exec("
        INSERT IGNORE INTO Rooms (room_number, type, status, price) VALUES
        ('101', 'Standard', 'Available', 500000),
        ('102', 'VIP', 'Available', 1000000),
        ('103', 'Suite', 'Available', 2000000);
    ");

    $pdo->exec("
        INSERT IGNORE INTO Guests (guest_id, full_name, phone) VALUES
        (1, 'Nguyen Van A', '0901234567'),
        (2, 'Tran Thi B', '0912345678'),
        (3, 'Le Van C', '0923456789');
    ");

    // 4. Tạo Stored Procedure MakeBooking
    $pdo->exec("DROP PROCEDURE IF EXISTS MakeBooking");
    $pdo->exec("
    CREATE PROCEDURE MakeBooking(
        IN p_guest_id INT,
        IN p_room_id INT,
        IN p_check_in DATE,
        IN p_check_out DATE
    )
    BEGIN
        DECLARE room_status VARCHAR(20);
        DECLARE overlapping_count INT;

        SELECT status INTO room_status
        FROM Rooms
        WHERE room_id = p_room_id;

        IF room_status IS NULL THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Phòng không tồn tại.';
        ELSEIF room_status <> 'Available' THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Phòng không có trạng thái Available.';
        END IF;

        SELECT COUNT(*) INTO overlapping_count
        FROM Bookings
        WHERE room_id = p_room_id
          AND status = 'Confirmed'
          AND NOT (p_check_out <= check_in OR p_check_in >= check_out);

        IF overlapping_count > 0 THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Phòng đã được đặt trong thời gian này.';
        END IF;

        INSERT INTO Bookings(guest_id, room_id, check_in, check_out, status)
        VALUES (p_guest_id, p_room_id, p_check_in, p_check_out, 'Confirmed');

        UPDATE Rooms SET status = 'Occupied' WHERE room_id = p_room_id;
    END
    ");

    // 5. Tạo Trigger after_booking_cancel (đã sửa đúng chuẩn)
    $pdo->exec("DROP TRIGGER IF EXISTS after_booking_cancel");
    $pdo->exec("
    CREATE TRIGGER after_booking_cancel
    AFTER UPDATE ON Bookings
    FOR EACH ROW
    BEGIN
        DECLARE count_confirmed INT DEFAULT 0;

        IF NEW.status = 'Cancelled' AND OLD.status <> 'Cancelled' THEN
            SELECT COUNT(*) INTO count_confirmed
            FROM Bookings
            WHERE room_id = NEW.room_id
              AND status = 'Confirmed'
              AND check_out > CURDATE();

            IF count_confirmed = 0 THEN
                UPDATE Rooms SET status = 'Available' WHERE room_id = NEW.room_id;
            END IF;
        END IF;
    END
    ");

    // 6. Tạo Stored Procedure GenerateInvoice
    $pdo->exec("DROP PROCEDURE IF EXISTS GenerateInvoice");
    $pdo->exec("
    CREATE PROCEDURE GenerateInvoice(
        IN p_booking_id INT
    )
    BEGIN
        DECLARE v_check_in DATE;
        DECLARE v_check_out DATE;
        DECLARE v_price INT;
        DECLARE v_nights INT;
        DECLARE v_total INT;

        SELECT b.check_in, b.check_out, r.price
        INTO v_check_in, v_check_out, v_price
        FROM Bookings b
        JOIN Rooms r ON b.room_id = r.room_id
        WHERE b.booking_id = p_booking_id;

        SET v_nights = DATEDIFF(v_check_out, v_check_in);
        IF v_nights <= 0 THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Ngày trả phòng phải lớn hơn ngày nhận phòng.';
        END IF;

        SET v_total = v_nights * v_price;

        INSERT INTO Invoices (booking_id, total_amount, generated_date)
        VALUES (p_booking_id, v_total, CURDATE());
    END
    ");

    // 7. Hàm PHP gọi MakeBooking
    function makeBooking($pdo, $guestId, $roomId, $checkIn, $checkOut) {
        $stmt = $pdo->prepare("CALL MakeBooking(:guest_id, :room_id, :check_in, :check_out)");
        $stmt->bindParam(':guest_id', $guestId, PDO::PARAM_INT);
        $stmt->bindParam(':room_id', $roomId, PDO::PARAM_INT);
        $stmt->bindParam(':check_in', $checkIn);
        $stmt->bindParam(':check_out', $checkOut);

        try {
            $stmt->execute();
            echo "Đặt phòng thành công!<br>";
        } catch (PDOException $e) {
            echo "Lỗi: " . $e->getMessage() . "<br>";
        }
    }

    // 8. Hàm PHP gọi GenerateInvoice
    function generateInvoice($pdo, $bookingId) {
        $stmt = $pdo->prepare("CALL GenerateInvoice(:booking_id)");
        $stmt->bindParam(':booking_id', $bookingId, PDO::PARAM_INT);

        try {
            $stmt->execute();
            echo "Tạo hóa đơn thành công!<br>";
        } catch (PDOException $e) {
            echo "Lỗi: " . $e->getMessage() . "<br>";
        }
    }

    // 9. Demo gọi thử
    echo "<pre>";
    makeBooking($pdo, 1, 1, '2025-06-20', '2025-06-25');  // Đặt phòng
    // Giả sử booking_id = 1 (bạn có thể lấy chính xác id bằng query riêng nếu muốn)
    generateInvoice($pdo, 1); // Tạo hóa đơn
    echo "</pre>";

} catch (PDOException $e) {
    die("Lỗi kết nối hoặc thực thi: " . $e->getMessage());
}
