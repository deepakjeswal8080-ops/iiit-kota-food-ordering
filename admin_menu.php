<?php
session_start();
include 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_food'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $price = $_POST['price'];
    mysqli_query($conn, "INSERT INTO food_items (name, category, price) VALUES ('$name', '$category', '$price')");
}

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    mysqli_query($conn, "DELETE FROM food_items WHERE id=$id");
    header("Location: admin_menu.php");
    exit();
}

$foods = mysqli_query($conn, "SELECT * FROM food_items ORDER BY id DESC");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Panel - Manage Menu</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div style="background:#333; color:#fff; padding:15px; display:flex; justify-content:space-between;">
        <h3>Admin Panel - Manage Menu</h3>
        <div>
            <a href="admin_orders.php" style="background:#ffc107; color:#000; padding:8px 12px; text-decoration:none; border-radius:4px; margin-right:10px;">View Orders</a>
            <a href="logout.php" style="background:#dc3545; color:#fff; padding:8px 12px; text-decoration:none; border-radius:4px;">Logout</a>
        </div>
    </div>

    <div style="max-width:800px; margin:30px auto; background:#fff; padding:20px; border-radius:8px;">
        <h2>Add New Food Item</h2>
        <form method="POST">
            <label>Dish Name:</label><br>
            <input type="text" name="name" placeholder="e.g., Veg Burger" required style="width:100%; padding:8px; margin:6px 0;"><br>
            <label>Category:</label><br>
            <select name="category" style="width:100%; padding:8px; margin:6px 0;">
                <option value="Fast Food">Fast Food</option>
                <option value="Main Course">Main Course</option>
                <option value="Drinks">Drinks</option>
                <option value="Dessert">Dessert</option>
            </select><br>
            <label>Price (₹):</label><br>
            <input type="number" step="0.01" name="price" placeholder="99" required style="width:100%; padding:8px; margin:6px 0;"><br>
            <button type="submit" name="add_food" style="width:100%; padding:10px; background:#28a745; color:#fff; border:none; cursor:pointer; margin-top:10px; font-weight:bold;">Add Dish</button>
        </form>

        <h2 style="margin-top:30px;">Current Menu List</h2>
        <table border="1" cellpadding="10" cellspacing="0" style="width:100%; border-collapse:collapse; text-align:left;">
            <tr style="background:#f2f2f2;">
                <th>Dish Name</th>
                <th>Category</th>
                <th>Price (₹)</th>
                <th>Action</th>
            </tr>
            <?php while($row = mysqli_fetch_assoc($foods)): ?>
            <tr>
                <td><?php echo $row['name']; ?></td>
                <td><?php echo $row['category']; ?></td>
                <td>₹<?php echo $row['price']; ?></td>
                <td><a href="admin_menu.php?delete=<?php echo $row['id']; ?>" style="color:red;" onclick="return confirm('Are you sure you want to delete this item?')">Delete</a></td>
            </tr>
            <?php endwhile; ?>
        </table>
    </div>
</body>
</html>