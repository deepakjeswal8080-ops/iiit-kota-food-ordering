
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
    if (isset($_SESSION['cart'][$id])) {
        $_SESSION['cart'][$id]['qty'] += 1;
    }
    header("Location: menu.php");
    exit();
}

if (isset($_GET['dec'])) {
    $id = intval($_GET['dec']);
    if (isset($_SESSION['cart'][$id])) {
        $_SESSION['cart'][$id]['qty'] -= 1;
        if ($_SESSION['cart'][$id]['qty'] <= 0) {
            unset($_SESSION['cart'][$id]);
        }
    }
    header("Location: menu.php");
    exit();
}

if (isset($_GET['clear_cart'])) {
    $_SESSION['cart'] = [];
    header("Location: menu.php");
    exit();
}

// Place order with Hostel + Room details
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
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background: #f8f9fa; color: #1c1c1c; }

        .navbar {
            background: #ffffff;
            padding: 14px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 15px rgba(0,0,0,0.06);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .brand { display: flex; align-items: center; gap: 10px; }
        .brand-logo {
            background: linear-gradient(135deg, #fc8019, #e23744);
            color: #fff;
            font-weight: 700;
            padding: 8px 12px;
            border-radius: 10px;
            font-size: 18px;
        }
        .brand-text h2 { font-size: 20px; color: #1c1c1c; line-height: 1.1; }
        .brand-text span { font-size: 12px; color: #686b78; }
        .nav-links { display: flex; align-items: center; gap: 15px; }
        .nav-user { font-size: 14px; font-weight: 500; color: #3d4152; }
        .btn-orders {
            background: #fff3eb; color: #fc8019; padding: 8px 16px;
            border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 14px;
            border: 1px solid #ffd8be; transition: 0.2s;
        }
        .btn-orders:hover { background: #fc8019; color: #fff; }
        .btn-logout {
            background: #fee2e2; color: #dc2626; padding: 8px 16px;
            border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 14px;
        }
        .btn-logout:hover { background: #dc2626; color: #fff; }

        .hero {
            background: linear-gradient(120deg, #1f1c2c 0%, #fc8019 100%);
            color: #fff;
            padding: 38px 40px;
            margin: 25px auto 0;
            max-width: 1240px;
            border-radius: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
            box-shadow: 0 12px 30px rgba(252, 128, 25, 0.2);
        }
        .hero h1 { font-size: 28px; font-weight: 700; margin-bottom: 6px; }
        .hero p { font-size: 14px; opacity: 0.9; }
        .search-box {
            background: #fff;
            padding: 6px 14px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            width: 340px;
            max-width: 100%;
        }
        .search-box input {
            border: none; outline: none; width: 100%; padding: 8px; font-size: 14px; color: #1c1c1c;
        }

        .main-wrapper {
            max-width: 1240px;
            margin: 25px auto 60px;
            padding: 0 15px;
            display: grid;
            grid-template-columns: 1fr 370px;
            gap: 28px;
            align-items: start;
        }
        @media (max-width: 920px) {
            .main-wrapper { grid-template-columns: 1fr; }
            .navbar { padding: 14px 20px; }
        }

        .cat-bar { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 22px; }
        .cat-pill {
            background: #fff; border: 1px solid #e2e8f0; color: #3d4152;
            padding: 8px 18px; border-radius: 25px; font-size: 13px; font-weight: 600;
            cursor: pointer; transition: 0.2s;
        }
        .cat-pill.active, .cat-pill:hover {
            background: #fc8019; color: #fff; border-color: #fc8019;
        }

        .food-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(245px, 1fr));
            gap: 22px;
        }
        .food-card {
            background: #fff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 16px rgba(0,0,0,0.05);
            border: 1px solid #f0f0f5;
            transition: transform 0.25s ease, box-shadow 0.25s ease;
            display: flex;
            flex-direction: column;
        }
        .food-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 14px 28px rgba(0,0,0,0.1);
        }
        .food-img-wrap { position: relative; height: 165px; overflow: hidden; }
        .food-img-wrap img { width: 100%; height: 100%; object-fit: cover; transition: 0.3s; }
        .food-card:hover .food-img-wrap img { transform: scale(1.06); }
        .cat-badge {
            position: absolute; top: 12px; left: 12px;
            background: rgba(0,0,0,0.7); color: #fff;
            font-size: 11px; padding: 4px 10px; border-radius: 20px; backdrop-filter: blur(4px);
        }
        .rating-badge {
            position: absolute; bottom: 10px; right: 10px;
            background: #24963f; color: #fff; font-weight: 600;
            font-size: 12px; padding: 3px 8px; border-radius: 6px;
        }
        .food-body { padding: 16px; flex: 1; display: flex; flex-direction: column; justify-content: space-between; }
        .food-title { font-size: 16px; font-weight: 600; color: #1c1c1c; margin-bottom: 4px; }
        .food-meta { font-size: 12px; color: #686b78; margin-bottom: 14px; }
        .food-footer { display: flex; justify-content: space-between; align-items: center; }
        .price { font-size: 18px; font-weight: 700; color: #1c1c1c; }
        .btn-add {
            background: #fff; color: #24963f; border: 1.5px solid #24963f;
            padding: 7px 18px; border-radius: 8px; font-weight: 700; font-size: 13px;
            cursor: pointer; transition: 0.2s;
        }
        .btn-add:hover { background: #24963f; color: #fff; }

        .cart-panel {
            background: #fff;
            border-radius: 18px;
            padding: 22px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.07);
            border: 1px solid #f0f0f5;
            position: sticky;
            top: 85px;
        }
        .cart-header {
            display: flex; justify-content: space-between; align-items: center;
            border-bottom: 1px solid #f0f0f5; padding-bottom: 12px; margin-bottom: 15px;
        }
        .cart-header h3 { font-size: 18px; color: #1c1c1c; }
        .clear-link { color: #e23744; font-size: 12px; text-decoration: none; font-weight: 600; }
        .cart-item {
            display: flex; justify-content: space-between; align-items: center;
            padding: 10px 0; border-bottom: 1px dashed #ececec; font-size: 14px;
        }
        .qty-box {
            display: flex; align-items: center; gap: 8px;
            border: 1px solid #d4d5d9; border-radius: 6px; padding: 2px 8px;
        }
        .qty-box a { text-decoration: none; color: #24963f; font-weight: 700; font-size: 16px; }
        .bill-box {
            background: #f8f9fa; padding: 12px; border-radius: 10px; margin: 15px 0;
        }
        .bill-row { display: flex; justify-content: space-between; font-size: 13px; color: #686b78; margin-bottom: 5px; }
        .bill-total { display: flex; justify-content: space-between; font-size: 16px; font-weight: 700; color: #1c1c1c; border-top: 1px solid #e2e8f0; padding-top: 8px; margin-top: 6px; }

        .form-group { margin-bottom: 12px; }
        .form-group label { display: block; font-size: 12px; font-weight: 600; color: #3d4152; margin-bottom: 4px; }
        .form-group input, .form-group select {
            width: 100%; padding: 10px 12px; border: 1.5px solid #e2e8f0;
            border-radius: 8px; font-size: 13px; outline: none;
        }
        .form-group input:focus, .form-group select:focus { border-color: #fc8019; }
        .btn-checkout {
            width: 100%; padding: 13px; background: linear-gradient(135deg, #24963f, #1e7e34);
            color: #fff; border: none; border-radius: 10px; font-size: 15px;
            font-weight: 600; cursor: pointer; margin-top: 5px; transition: 0.2s;
        }
        .btn-checkout:hover { opacity: 0.92; transform: translateY(-1px); }

        .success-banner {
            background: #dcfce7; color: #166534; border: 1px solid #86efac;
            padding: 14px 18px; border-radius: 12px; margin-bottom: 20px; font-weight: 500; font-size: 14px;
        }
    </style>
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

    <div style="padding: 0 15px;">
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
                                <form method="POST" style="margin:0;">
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
                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
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

    <script>
        let currentCat = 'all';

        function filterCategory(cat, btn) {
            currentCat = cat;
            document.querySelectorAll('.cat-pill').forEach(p => p.classList.remove('active'));
            btn.classList.add('active');
            filterMenu();
        }

        function filterMenu() {
            const query = document.getElementById('searchInput').value.toLowerCase();
            document.querySelectorAll('.food-card').forEach(card => {
                const name = card.getAttribute('data-name');
                const cat = card.getAttribute('data-cat');
                const matchSearch = name.includes(query);
                const matchCat = (currentCat === 'all' || cat === currentCat);
                card.style.display = (matchSearch && matchCat) ? 'flex' : 'none';
            });
        }
    </script>
</body>
</html>