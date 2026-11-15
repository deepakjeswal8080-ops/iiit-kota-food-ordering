<?php
session_start();
include 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'customer') {
    header("Location: index.php");
    exit();
}

$uid = $_SESSION['user_id'];
$orders = mysqli_query($conn, "SELECT * FROM orders WHERE user_id=$uid ORDER BY id DESC");
?>
<!DOCTYPE html>
<html>
<head>
    <title>My Orders - Food Ordering</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div style="background:#28a745; color:#fff; padding:15px; display:flex; justify-content:space-between;">
        <h3>My Orders</h3>
        <div>
            <a href="menu.php" style="background:#fff; color:#28a745; padding:8px 12px; text-decoration:none; border-radius:4px; margin-right:10px; font-weight:bold;">Back to Menu</a>
            <a href="logout.php" style="background:#dc3545; color:#fff; padding:8px 12px; text-decoration:none; border-radius:4px;">Logout</a>
        </div>
    </div>

    <div style="max-width:800px; margin:30px auto; background:#fff; padding:20px; border-radius:8px;">
        <h2>Order History</h2>
        <table border="1" cellpadding="10" cellspacing="0" style="width:100%; border-collapse:collapse; text-align:left;">
            <tr style="background:#f2f2f2;">
                <th>Order ID</th>
                <th>Items Ordered</th>
                <th>Total</th>
                <th>Status</th>
                <th>Date</th>
            </tr>
            <?php while($ord = mysqli_fetch_assoc($orders)): ?>
            <tr>
                <td>#<?php echo $ord['id']; ?></td>
                <td>
                    <?php
                    $oid = $ord['id'];
                    $items = mysqli_query($conn, "SELECT * FROM order_items WHERE order_id=$oid");
                    while($it = mysqli_fetch_assoc($items)) {
                        echo $it['food_name'] . " x " . $it['quantity'] . "<br>";
                    }
                    ?>
                </td>
                <td>₹<?php echo $ord['total_amount']; ?></td>
                <td><b><?php echo $ord['status']; ?></b></td>
                <td><?php echo $ord['created_at']; ?></td>
            </tr>
            <?php endwhile; ?>
        </table>
    </div>
</body>
</html>