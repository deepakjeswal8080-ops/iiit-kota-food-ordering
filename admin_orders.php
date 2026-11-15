<?php
session_start();
include 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit();
}

// Order Status Update Karna
if (isset($_POST['update_status'])) {
    $order_id = intval($_POST['order_id']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    mysqli_query($conn, "UPDATE orders SET status='$status' WHERE id=$order_id");
}

$orders = mysqli_query($conn, "SELECT * FROM orders ORDER BY id DESC");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Orders - Food Ordering</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div style="background:#333; color:#fff; padding:15px; display:flex; justify-content:space-between;">
        <h3>Admin Panel - Customer Orders</h3>
        <div>
            <a href="admin_menu.php" style="background:#28a745; color:#fff; padding:8px 12px; text-decoration:none; border-radius:4px; margin-right:10px;">Manage Menu</a>
            <a href="logout.php" style="background:#dc3545; color:#fff; padding:8px 12px; text-decoration:none; border-radius:4px;">Logout</a>
        </div>
    </div>

    <div style="max-width:900px; margin:30px auto; background:#fff; padding:20px; border-radius:8px;">
        <h2>All Orders</h2>
        <table border="1" cellpadding="10" cellspacing="0" style="width:100%; border-collapse:collapse; text-align:left;">
            <tr style="background:#f2f2f2;">
                <th>Order ID</th>
                <th>Customer</th>
                <th>Phone & Address</th>
                <th>Items</th>
                <th>Total</th>
                <th>Status</th>
            </tr>
            <?php while($ord = mysqli_fetch_assoc($orders)): ?>
            <tr>
                <td>#<?php echo $ord['id']; ?></td>
                <td><?php echo $ord['customer_name']; ?></td>
                <td><?php echo $ord['phone']; ?><br><small><?php echo $ord['address']; ?></small></td>
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
                <td>
                    <form method="POST">
                        <input type="hidden" name="order_id" value="<?php echo $ord['id']; ?>">
                        <select name="status">
                            <option <?php if($ord['status']=='Pending') echo 'selected'; ?>>Pending</option>
                            <option <?php if($ord['status']=='Preparing') echo 'selected'; ?>>Preparing</option>
                            <option <?php if($ord['status']=='Delivered') echo 'selected'; ?>>Delivered</option>
                        </select>
                        <button type="submit" name="update_status">Update</button>
                    </form>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>
    </div>
</body>
</html>