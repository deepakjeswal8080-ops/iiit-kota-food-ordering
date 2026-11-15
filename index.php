
<?php
session_start();
include 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'customer') {
    header("Location: index.php");
    exit();
}

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Add item to cart
if (isset($_POST['add_to_cart'])) {
    $id = intval($_POST['food_id']);
    $name = $_POST['food_name'];
    $price = floatval($_POST['food_price']);

    if (isset($_SESSION['cart'][$id])) {
        $_SESSION['cart'][$id]['qty'] += 1;
    } else {
        $_SESSION['cart'][$id] = ['name' => $name, 'price' => $price, 'qty' => 1];
    }
    header("Location: menu.php");
    exit();
}

// Increase / Decrease quantity
if (isset($_GET['inc'])) {
    $id = intval($_GET['inc']);
    if (isset($_SESSION['cart'][$id])) $_SESSION['cart'][$id]['qty'] += 1;
    header("Location: menu.php");
    exit();
}

if (isset($_GET['dec'])) {
    $id = intval($_GET['dec']);
    if (isset($_SESSION['cart'][$id])) {
        $_SESSION['cart'][$id]['qty'] -= 1;
        if ($_SESSION['cart'][$id]['qty'] <= 0) unset($_SESSION['cart'][$id]);
    }
    header("Location: menu.php");
    exit();
}

if (isset($_GET['clear_cart'])) {
    $_SESSION['cart'] = [];
    header("Location: menu.php");
    exit();
}

// Place order
$order_msg = "";
if (isset($_POST['place_order']) && !empty($_SESSION['cart'])) {
    $user_id = $_SESSION['user_id'];
    $cname = mysqli_real_escape_string($conn, $_SESSION['user_name']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $hostel = mysqli_real_escape_string($conn, $_POST['hostel']);
    $room = mysqli_real_escape_string($conn, $_POST['room']);
    $note = mysqli_real_escape_string($conn, $_POST['address']);

    $full_address = $hostel . " | Room: " . $room . ($note ? " (" . $note . ")" : "");
    $total = 0;
    foreach ($_SESSION['cart'] as $item) {
        $total += $item['price'] * $item['qty'];
    }

    mysqli_query($conn, "INSERT INTO orders (user_id, customer_name, phone, address, total_amount) VALUES ('$user_id', '$cname', '$phone', '$full_address', '$total')");
    $order_id = mysqli_insert_id($conn);

    foreach ($_SESSION['cart'] as $item) {
        $fname = mysqli_real_escape_string($conn, $item['name']);
        $fprice = $item['price'];
        $fqty = $item['qty'];
        mysqli_query($conn, "INSERT INTO order_items (order_id, food_name, price, quantity) VALUES ('$order_id', '$fname', '$fprice', '$fqty')");
    }

    $_SESSION['cart'] = [];
    $order_msg = "Order #$order_id confirmed! Hot food is on the way to $hostel (Room $room).";
}

$foods_res = mysqli_query($conn, "SELECT * FROM food_items WHERE available=1");
$food_list = [];
$categories = [];
while ($row = mysqli_fetch_assoc($foods_res)) {
    $food_list[] = $row;
    if (!empty($row['category']) && !in_array($row['category'], $categories)) {
        $categories[] = $row['category'];
    }
}

function getFoodImage($name, $cat) {
    $text = strtolower($name . ' ' . $cat);
    if (strpos($text, 'burger') !== false) return 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=500&q=80';
    if (strpos($text, 'pizza') !== false) return 'https://images.unsplash.com/photo-1513104890138-7c749659a591?w=500&q=80';
    if (strpos($text, 'biryani') !== false || strpos($text, 'rice') !== false) return 'https://images.unsplash.com/photo-1563379091339-03b21ab4a4f8?w=500&q=80';
    if (strpos($text, 'paneer') !== false || strpos($text, 'thali') !== false || strpos($text, 'curry') !== false) return 'https://images.unsplash.com/photo-1631452180519-c014fe946bc7?w=500&q=80';
    if (strpos($text, 'dosa') !== false || strpos($text, 'idli') !== false || strpos($text, 'south') !== false) return 'https://images.unsplash.com/photo-1668236543090-82eba5ee5976?w=500&q=80';
    if (strpos($text, 'noodle') !== false || strpos($text, 'chowmein') !== false || strpos($text, 'momos') !== false || strpos($text, 'chinese') !== false) return 'https://images.unsplash.com/photo-1585032226651-759b368d7246?w=500&q=80';
    if (strpos($text, 'coffee') !== false || strpos($text, 'tea') !== false || strpos($text, 'chai') !== false || strpos($text, 'shake') !== false || strpos($text, 'drink') !== false) return 'https://images.unsplash.com/photo-1544145945-f90425340c7e?w=500&q=80';
    if (strpos($text, 'samosa') !== false || strpos($text, 'fries') !== false || strpos($text, 'snack') !== false || strpos($text, 'maggi') !== false) return 'https://images.unsplash.com/photo-1601050690597-df0568f70950?w=500&q=80';
    if (strpos($text, 'cake') !== false || strpos($text, 'ice') !== false || strpos($text, 'sweet') !== false || strpos($text, 'dessert') !== false) return 'https://images.unsplash.com/photo-1551024601-bec78aea704b?w=500&q=80';
    return 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=500&q=80';
}

$total_items = 0;
$grand_total = 0;
foreach ($_SESSION['cart'] as $c) {
    $total_items += $c['qty'];
    $grand_total += $c['price'] * $c['qty'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IIIT Kota Food | Order Online</title>
    <!-- External CSS File -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

    <nav class="navbar">
        <div class="brand">
            <div class="brand-logo">IK</div>
            <div class="brand-text">
                <h2>IIIT Kota Food</h2>
                <span>Campus Express Delivery</span>
            </div>
        </div>
        <div class="nav-links">
            <span class="nav-user">Hi, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
            <a href="my_orders.php" class="btn-orders">My Orders</a>
            <a href="logout.php" class="btn-logout">Logout</a>
        </div>
    </nav>

    <div class="hero-wrap">
        <div class="hero">
            <div>
                <h1>Late-Night Cravings at IIIT Kota?</h1>
                <p>Freshly prepared meals delivered straight to your Hostel Room in 20 mins.</p>
            </div>
            <div class="search-box">
                <span>🔍</span>
                <input type="text" id="searchInput" onkeyup="filterMenu()" placeholder="Search burger, dosa, biryani, chai...">
            </div>
        </div>
    </div>

    <div class="main-wrapper">
        <div>
            <?php if($order_msg): ?>
                <div class="success-banner">✅ <?php echo $order_msg; ?></div>
            <?php endif; ?>

            <div class="cat-bar">
                <button class="cat-pill active" onclick="filterCategory('all', this)">All Items</button>
                <?php foreach($categories as $cat): ?>
                    <button class="cat-pill" onclick="filterCategory('<?php echo strtolower(htmlspecialchars($cat)); ?>', this)">
                        <?php echo htmlspecialchars($cat); ?>
                    </button>
                <?php endforeach; ?>
            </div>

            <div class="food-grid" id="foodGrid">
                <?php foreach($food_list as $f): 
                    $img = getFoodImage($f['name'], $f['category']);
                    $in_cart_qty = isset($_SESSION['cart'][$f['id']]) ? $_SESSION['cart'][$f['id']]['qty'] : 0;
                ?>
                <div class="food-card" data-name="<?php echo strtolower(htmlspecialchars($f['name'])); ?>" data-cat="<?php echo strtolower(htmlspecialchars($f['category'])); ?>">
                    <div class="food-img-wrap">
                        <img src="<?php echo $img; ?>" alt="<?php echo htmlspecialchars($f['name']); ?>">
                        <span class="cat-badge"><?php echo htmlspecialchars($f['category']); ?></span>
                        <span class="rating-badge">4.5 ★</span>
                    </div>
                    <div class="food-body">
                        <div>
                            <div class="food-title"><?php echo htmlspecialchars($f['name']); ?></div>
                            <div class="food-meta">🟢 Freshly Made • 15–20 mins</div>
                        </div>
                        <div class="food-footer">
                            <span class="price">₹<?php echo $f['price']; ?></span>
                            <?php if($in_cart_qty > 0): ?>
                                <div class="qty-box">
                                    <a href="menu.php?dec=<?php echo $f['id']; ?>">−</a>
                                    <span><b><?php echo $in_cart_qty; ?></b></span>
                                    <a href="menu.php?inc=<?php echo $f['id']; ?>">+</a>
                                </div>
                            <?php else: ?>
                                <form method="POST">
                                    <input type="hidden" name="food_id" value="<?php echo $f['id']; ?>">
                                    <input type="hidden" name="food_name" value="<?php echo htmlspecialchars($f['name']); ?>">
                                    <input type="hidden" name="food_price" value="<?php echo $f['price']; ?>">
                                    <button type="submit" name="add_to_cart" class="btn-add">+ ADD</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="cart-panel">
            <div class="cart-header">
                <h3>🛒 Your Cart (<?php echo $total_items; ?>)</h3>
                <?php if(!empty($_SESSION['cart'])): ?>
                    <a href="menu.php?clear_cart=1" class="clear-link">Clear All</a>
                <?php endif; ?>
            </div>

            <?php if(empty($_SESSION['cart'])): ?>
                <div style="text-align:center; padding: 30px 10px; color:#686b78;">
                    <div style="font-size:42px; margin-bottom:8px;">🍽️</div>
                    <p style="font-size:14px;">Your plate is empty!<br>Add delicious items from the menu.</p>
                </div>
            <?php else: ?>
                <?php foreach($_SESSION['cart'] as $id => $c): ?>
                <div class="cart-item">
                    <div>
                        <div style="font-weight:600;"><?php echo htmlspecialchars($c['name']); ?></div>
                        <div style="font-size:12px; color:#686b78;">₹<?php echo $c['price']; ?> × <?php echo $c['qty']; ?></div>
                    </div>
                    <div style="display:flex; align-items:center; gap:10px;">
                        <div class="qty-box">
                            <a href="menu.php?dec=<?php echo $id; ?>">−</a>
                            <span><?php echo $c['qty']; ?></span>
                            <a href="menu.php?inc=<?php echo $id; ?>">+</a>
                        </div>
                        <span style="font-weight:600;">₹<?php echo $c['price'] * $c['qty']; ?></span>
                    </div>
                </div>
                <?php endforeach; ?>

                <div class="bill-box">
                    <div class="bill-row"><span>Item Total</span><span>₹<?php echo $grand_total; ?></span></div>
                    <div class="bill-row"><span>Campus Delivery Fee</span><span style="color:#24963f; font-weight:600;">FREE</span></div>
                    <div class="bill-total"><span>To Pay</span><span>₹<?php echo $grand_total; ?></span></div>
                </div>

                <form method="POST">
                    <div class="form-group">
                        <label>Hostel / Campus Location</label>
                        <select name="hostel" required>
                            <option value="Boys Hostel 1 (BH-1)">Boys Hostel 1 (BH-1)</option>
                            <option value="Boys Hostel 2 (BH-2)">Boys Hostel 2 (BH-2)</option>
                            <option value="Boys Hostel 3 (BH-3)">Boys Hostel 3 (BH-3)</option>
                            <option value="Girls Hostel (GH)">Girls Hostel (GH)</option>
                            <option value="Academic Block / Canteen">Academic Block / Canteen</option>
                            <option value="Main Gate / Faculty Quarters">Main Gate / Faculty Quarters</option>
                        </select>
                    </div>
                    <div class="form-grid-2">
                        <div class="form-group">
                            <label>Room No.</label>
                            <input type="text" name="room" placeholder="e.g. B-204" required>
                        </div>
                        <div class="form-group">
                            <label>Mobile No.</label>
                            <input type="text" name="phone" placeholder="10-digit no." required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Cooking / Delivery Note (Optional)</label>
                        <input type="text" name="address" placeholder="Extra spicy, call at gate...">
                    </div>
                    <button type="submit" name="place_order" class="btn-checkout">Place Order • ₹<?php echo $grand_total; ?></button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- External JavaScript File -->
    <script src="assets/js/app.js"></script>
</body>
</html>