<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = htmlspecialchars($_SESSION['user_id']);
$user_username = htmlspecialchars($_SESSION['user_username']);
$user_role = htmlspecialchars($_SESSION['user_role']);

// Database connection
$conn = new mysqli("localhost", "root", "", "lunadb");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "Connected to the database successfully.<br>";

// Handle order saving logic
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['proceed_checkout'])) {
    $order_details = htmlspecialchars($_POST['order_details']);
    $order_total = htmlspecialchars($_POST['order_total']);
    $payment_method = htmlspecialchars($_POST['payment_method']);
    $cart = json_decode($_POST['cart'], true); // Assuming the cart data is sent as a hidden input

    if (empty($cart)) {
        die("Cart data is missing or invalid.");
    }

    echo "Cart data received: ";
    print_r($cart);
    echo "<br>";

    // Auto-assign an agent
    $sql = "SELECT agent_id FROM agent ORDER BY RAND() LIMIT 1";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        $agent = $result->fetch_assoc();
        $agent_id = $agent['agent_id'];
    } else {
        $agent_id = null; // Handle the case when there are no agents
    }

    // Insert order into the orders table
    $sql = "INSERT INTO orders (order_detail, order_totPrice, customer_id, agent_id) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die("prepare() failed: " . htmlspecialchars($conn->error));
    }

    $stmt->bind_param("sdii", $order_details, $order_total, $user_id, $agent_id);
    if ($stmt->execute()) {
        $order_id = $stmt->insert_id; // Get the last inserted order ID
        echo "Order inserted with ID: $order_id<br>";

        // Insert payment details into the payment table
        $sql = "INSERT INTO payment (order_id, payment_method, customer_id) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            die("prepare() failed: " . htmlspecialchars($conn->error));
        }

        $stmt->bind_param("isi", $order_id, $payment_method, $user_id);
        if ($stmt->execute()) {
            $payment_id = $stmt->insert_id; // Get the last inserted payment ID
            echo "Payment inserted with ID: $payment_id<br>";

            // Update the order with the payment ID
            $sql = "UPDATE orders SET payment_id = ? WHERE order_id = ?";
            $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                die("prepare() failed: " . htmlspecialchars($conn->error));
            }

            $stmt->bind_param("ii", $payment_id, $order_id);
            if ($stmt->execute()) {
                echo "Order updated with payment ID: $payment_id<br>";

                // Deduct the quantity of each product from the database
                foreach ($cart as $item) {
                    if (!isset($item['id']) || !isset($item['quantity'])) {
                        echo "Invalid cart item structure: ";
                        print_r($item);
                        echo "<br>";
                        continue;
                    }

                    $product_id = htmlspecialchars($item['id']);
                    $quantity = htmlspecialchars($item['quantity']);

                    echo "Updating product ID $product_id with quantity $quantity<br>"; // Add logging here

                    $sql = "UPDATE products SET product_quantity = product_quantity - ? WHERE product_id = ?";
                    $stmt = $conn->prepare($sql);
                    if ($stmt === false) {
                        die("prepare() failed: " . htmlspecialchars($conn->error));
                    }

                    $stmt->bind_param("ii", $quantity, $product_id);
                    if (!$stmt->execute()) {
                        echo "Error updating product quantity for product ID $product_id: " . htmlspecialchars($stmt->error) . "<br>";
                    } else {
                        echo "Successfully deducted $quantity from product ID $product_id.<br>";

                        // Additional debug to check the current product quantity
                        $check_sql = "SELECT product_quantity FROM products WHERE product_id = ?";
                        $check_stmt = $conn->prepare($check_sql);
                        if ($check_stmt === false) {
                            die("prepare() failed: " . htmlspecialchars($conn->error));
                        }
                        $check_stmt->bind_param("i", $product_id);
                        if ($check_stmt->execute()) {
                            $check_result = $check_stmt->get_result();
                            if ($check_result->num_rows > 0) {
                                $row = $check_result->fetch_assoc();
                                echo "Updated quantity for product ID $product_id is now: " . htmlspecialchars($row['product_quantity']) . "<br>";
                            } else {
                                echo "No rows found when retrieving updated quantity for product ID $product_id<br>";
                            }
                        } else {
                            echo "Error executing statement to retrieve updated quantity for product ID $product_id: " . htmlspecialchars($check_stmt->error) . "<br>";
                        }
                        $check_stmt->close();
                    }
                }

                

                // Set timeArrival to 3 to 5 days from now
                $daysToAdd = rand(3, 5);
                $timeArrival = date('Y-m-d H:i:s', strtotime("+$daysToAdd days"));

                // Insert shipment into the shipment table
                $sql = "INSERT INTO shipment (customer_id, supplier_id, timeArrival) VALUES (?, ?, ?)";
                $stmt = $conn->prepare($sql);
                if ($stmt === false) {
                    die("prepare() failed: " . htmlspecialchars($conn->error));
                }

                $stmt->bind_param("iis", $user_id, $supplier_id, $timeArrival);
                if ($stmt->execute()) {
                    $shipment_id = $stmt->insert_id;
                    echo "Shipment inserted with ID: $shipment_id<br>";

                    // Update the order with the shipment ID
                    $sql = "UPDATE orders SET shipment_id = ? WHERE order_id = ?";
                    $stmt = $conn->prepare($sql);
                    if ($stmt === false) {
                        die("prepare() failed: " . htmlspecialchars($conn->error));
                    }

                    $stmt->bind_param("ii", $shipment_id, $order_id);
                    if ($stmt->execute()) {
                        echo "Order updated with shipment ID: $shipment_id<br>";

                        echo "<script>
                            alert('Thanks for choosing us!');
                            localStorage.removeItem('cart');
                        </script>";
                        exit();
                    } else {
                        echo "Error updating order with shipment ID: " . htmlspecialchars($stmt->error) . "<br>";
                    }
                } else {
                    echo "Error inserting shipment: " . htmlspecialchars($stmt->error) . "<br>";
                }
            } else {
                echo "Error updating order with payment ID: " . htmlspecialchars($stmt->error) . "<br>";
            }
        } else {
            echo "Error inserting payment: " . htmlspecialchars($stmt->error) . "<br>";
        }
    } else {
        echo "Error inserting order: " . htmlspecialchars($stmt->error) . "<br>";
    }

    $stmt->close();
}

// Fetch user data
$sql = "SELECT c_username, customer_phoneNum, customer_address FROM customers WHERE customer_id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $customer_username = htmlspecialchars($row['c_username']);
    $customer_phoneNum = htmlspecialchars($row['customer_phoneNum']);
    $customer_address = htmlspecialchars($row['customer_address']);
} else {
    $customer_username = '';
    $customer_phoneNum = '';
    $customer_address = '';
}

$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LVNASTUDIO CUSTOMER DASHBOARD - cart</title>
    <script src="https://kit.fontawesome.com/1b07de51b4.js"></script>
    <link rel="stylesheet" href="style.css">
    <style>
        #customer-info {
            width: 50%;
            margin-bottom: 30px;
            border: 1px solid #e2e9e1;
            padding: 30px;
        }

        #customer-info table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        #customer-info table, #customer-info th, #customer-info td {
            border: 1px solid #ddd;
        }

        #customer-info th, #customer-info td {
            padding: 8px;
            text-align: left;
        }

        #customer-info th {
            background-color: #f2f2f2;
            font-weight: bold;
        }

        #customer-info h3 {
            margin-bottom: 15px;
        }

        #payment-method {
            margin-bottom: 20px;
        }

        #payment-method select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: #f2f2f2;
            font-size: 16px;
        }
    </style>
</head>

<body>

<section id="header">
    <div>
        <div id="profile-icon">
            <i class="fa-solid fa-user"></i>
            <div id="profile-details">
                <p><strong>ID:</strong> <span id="user-id"><?php echo $user_id; ?></span></p>
                <p><strong>Username:</strong> <span id="user-username"><?php echo $user_username; ?></span></p>
                <p><strong>Role:</strong> <span id="user-role"><?php echo $user_role; ?></span></p>
            </div>
        </div>
        <a href="#"><img src="img/logo.png" class="logo" alt=""></a>
    </div>
    
    <div>
        <ul id="navbar">
            <li><a href="index.php">Home</a></li>
            <li><a href="shop.php">Shop</a></li>
            <li><a href="blog.php">Blog</a></li>
            <li><a href="about.php">About</a></li>
            <li><a href="contact.php">Custom</a></li>
            <li id="lg-bag"><a class="active" href="cart.php"><i class="fa-solid fa-bag-shopping"></i></a></li>
            <a href="#" id="close"><i class="fa-regular fa-circle-xmark"></i></a>
        </ul>
    </div>
    <div id="mobile">
        <a href="cart.php"><i class="fa-solid fa-bag-shopping"></i></a>
        <i id="bar" class="fas fa-outdent"></i>
    </div>
</section>

<div id="overlay"></div>
<div id="profile-popup">
    <span class="close-btn">&times;</span>
    <h3>User Profile</h3>
    <p><strong>ID:</strong> <?php echo $user_id; ?></p>
    <p><strong>Username:</strong> <?php echo $user_username; ?></p>
    <p><strong>Role:</strong> <?php echo $user_role; ?></p>
</div>

<section id="page-header" class="cart-header">
    <h2>#whatsINtheCART</h2>
    <p>View the products you have added</p>
</section>

<section id="cart" class="section-p1">
    <table width="100%">
        <thead>
            <tr>
                <td>Remove</td>
                <td>Image</td>
                <td>Product</td>
                <td>Size</td>
                <td>Price</td>
                <td>Quantity</td>
                <td>Subtotal</td>
            </tr>
        </thead>
        <tbody id="cartItems">
            <!-- Cart items will be injected here -->
        </tbody>
    </table>
</section>

<section id="cart-add" class="section-p1">
    <div id="coupon">
        <h3>Apply Coupon</h3>
        <div id="space">
            <select id="couponSelect">
                <option value="">Select a Coupon</option>
                <option value="10">SAVE10 - 10% off</option>
                <option value="20">SAVE20 - 20% off</option>
                <option value="30">SAVE30 - 30% off</option>
            </select>
            <button class="normal" onclick="applyCoupon()">Apply</button>
            <button class="normal" onclick="cancelCoupon()">Cancel</button>
        </div>
    </div>
    <div id="customer-info">
        <h3>Customer's Information</h3>
        <table>
            <tr>
                <th>Name</th>
                <td><?php echo $customer_username; ?></td>
            </tr>
            <tr>
                <th>Phone Number</th>
                <td><?php echo $customer_phoneNum; ?></td>
            </tr>
            <tr>
                <th>Address</th>
                <td><?php echo $customer_address; ?></td>
            </tr>
        </table>
    </div>

    <div id="payment-method">
        <h3>Payment Method</h3>
        <select name="payment_method" form="checkoutForm" required>
            <option value="">Select Payment Method</option>
            <option value="Credit Card">Credit Card</option>
            <option value="Debit Card">Debit Card</option>
            <option value="PayPal">PayPal</option>
            <option value="Bank Transfer">Bank Transfer</option>
        </select>
    </div>

    <div id="subtotal">
        <h3>Cart Total</h3>
        <table>
            <tr>
                <td>Cart Subtotal</td>
                <td id="cartSubtotal">RM0</td>
            </tr>
            <tr>
                <td>Shipping</td>
                <td>Free</td>
            </tr>
            <tr id="discountRow" style="display:none;">
                <td>Discount</td>
                <td id="discount">0%</</td>
            </tr>
            <tr>
                <td><strong>Total</strong></td>
                <td><strong id="cartTotal">RM0</strong></td>
            </tr>
        </table>
        <form method="POST" id="checkoutForm" onsubmit="return submitOrder()">
            <input type="hidden" name="order_details" id="orderDetails">
            <input type="hidden" name="order_total" id="orderTotal">
            <input type="hidden" name="cart" id="cartData">
            <button type="submit" class="normal" name="proceed_checkout">Proceed to Checkout</button>
        </form>
    </div>
</section>

<footer class="section-p1">
    <div class="col">
        <img class="logo" src="img/logo.png" alt="">
        <h4>Contact</h4>
        <p><strong>Address: </strong>Block 4807-3A-19, Jalan Perdana CBD Perdana 2, 63000 Cyberjaya Selangor</p>
        <p><strong>Phone: </strong>+60 14-732 3612</p>
        <p><strong>Hours: </strong>10:00 - 18:00, Monday - Saturday</p>
        <div class="follow">
            <h4>Follow Us</h4>
            <div class="Icon">
                <i class="fa-brands fa-facebook-f"></i>
                <i class="fa-brands fa-twitter"></i>
                <i class="fa-brands fa-square-instagram"></i>
                <i class="fa-brands fa-square-whatsapp"></i>
            </div>
        </div>
    </div>
    <div class="col">
        <h4>About</h4>
        <a href="#">About us</a>
        <a href="#">Delivery Information</a>
        <a href="#">Privacy Policy</a>
        <a href="#">Terms & Condition</a>
        <a href="#">Contact Us</a>
    </div>

    <div class="col">
        <h4>My Account</h4>
        <a href="#">Sign In</a>
        <a href="#">View Cart</a>
        <a href="#">My Wishlist</a>
        <a href="#">Track Order</a>
        <a href="#">Help</a>
    </div>

    <div class="col install">
        <h4>Install App</h4>
        <p>From App Store or Google Play</p>
        <div class="row">
            <img src="img/pay/app.jpg" alt="">
            <img src="img/pay/play.jpg" alt="">
        </div>
        <p>Secure Payment Gateways</p>
        <img src="img/pay/pay.png" alt="">
    </div>

    <div class="copyright">
        <p>2024, LVNASTUDIO Ecommerce Website</p>
    </div>
</footer>

<script>
    const bar = document.getElementById('bar');
    const navbar = document.getElementById('navbar');
    const close = document.getElementById('close');

    if (bar) {
        bar.addEventListener('click', () => {
            navbar.classList.add('show');
        });
    }

    if (close) {
        close.addEventListener('click', () => {
            navbar.classList.remove('show');
        });
    }

    function loadCart() {
        let cart = JSON.parse(localStorage.getItem('cart')) || [];
        const cartItemsContainer = document.getElementById('cartItems');
        cartItemsContainer.innerHTML = '';
        let subtotal = 0;

        if (cart.length === 0) {
            cartItemsContainer.innerHTML = '<tr><td colspan="7">Your cart is empty</td></tr>';
        } else {
            cart.forEach((product, index) => {
                const productSubtotal = product.price * product.quantity;
                subtotal += productSubtotal;

                const productRow = document.createElement('tr');
                productRow.innerHTML = `
                    <td><a href="#" onclick="removeFromCart(${index})"><i class="fa-regular fa-circle-xmark"></i></a></td>
                    <td><img src="${product.image}" alt="${product.name}"></td>
                    <td>${product.name}</td>
                    <td>${product.size}</td>
                    <td>RM${product.price}</td>
                    <td><input type="number" value="${product.quantity}" min="1" onchange="updateQuantity(${index}, this.value)"></td>
                    <td>RM${productSubtotal.toFixed(2)}</td>
                `;
                cartItemsContainer.appendChild(productRow);
            });
        }

        document.getElementById('cartSubtotal').innerText = `RM${subtotal.toFixed(2)}`;
        document.getElementById('cartTotal').innerText = `RM${subtotal.toFixed(2)}`;
    }

    function removeFromCart(index) {
        let cart = JSON.parse(localStorage.getItem('cart')) || [];
        cart.splice(index, 1);
        localStorage.setItem('cart', JSON.stringify(cart));
        loadCart();
    }

    function updateQuantity(index, quantity) {
        let cart = JSON.parse(localStorage.getItem('cart')) || [];
        quantity = parseInt(quantity);
        if (quantity < 1) {
            quantity = 1;
        }
        cart[index].quantity = quantity;
        localStorage.setItem('cart', JSON.stringify(cart));
        loadCart();
    }

    function applyCoupon() {
        const couponSelect = document.getElementById('couponSelect');
        const discountValue = parseInt(couponSelect.value);
        const discountRow = document.getElementById('discountRow');
        const discountCell = document.getElementById('discount');
        let subtotal = parseFloat(document.getElementById('cartSubtotal').innerText.replace('RM', ''));

        if (discountValue) {
            discountRow.style.display = 'table-row';
            discountCell.innerText = `${discountValue}%`;

            const discountAmount = subtotal * (discountValue / 100);
            const total = subtotal - discountAmount;

            document.getElementById('cartTotal').innerText = `RM${total.toFixed(2)}`;
        } else {
            discountRow.style.display = 'none';
            discountCell.innerText = `0%`;
            document.getElementById('cartTotal').innerText = `RM${subtotal.toFixed(2)}`;
        }
    }

    function cancelCoupon() {
        document.getElementById('couponSelect').value = '';
        const discountRow = document.getElementById('discountRow');
        const discountCell = document.getElementById('discount');
        let subtotal = parseFloat(document.getElementById('cartSubtotal').innerText.replace('RM', ''));

        discountRow.style.display = 'none';
        discountCell.innerText = `0%`;
        document.getElementById('cartTotal').innerText = `RM${subtotal.toFixed(2)}`;
    }

    function submitOrder() {
        let cart = JSON.parse(localStorage.getItem('cart')) || [];
        if (cart.length === 0) {
            alert("Your cart is empty.");
            return false;
        }

        let orderDetails = cart.map(product => product.name).join(", ");
        let orderTotal = parseFloat(document.getElementById('cartTotal').innerText.replace('RM', ''));

        document.getElementById('orderDetails').value = orderDetails;
        document.getElementById('orderTotal').value = orderTotal;
        document.getElementById('cartData').value = JSON.stringify(cart);

        return true;
    }

    window.onload = loadCart;

    // Example function to add a product to the cart
    function addToCart(productId, productName, productImage, productPrice, productQuantity, productSize) {
        let cart = JSON.parse(localStorage.getItem('cart')) || [];

        // Check if the product already exists in the cart
        let existingProductIndex = cart.findIndex(item => item.id === productId);

        if (existingProductIndex !== -1) {
            // Update the quantity of the existing product
            cart[existingProductIndex].quantity += productQuantity;
        } else {
            // Add the new product to the cart
            let newProduct = {
                id: productId, // Ensure the id field is included
                name: productName,
                image: productImage,
                price: productPrice,
                quantity: productQuantity,
                size: productSize
            };
            cart.push(newProduct);
        }

        // Save the updated cart to local storage
        localStorage.setItem('cart', JSON.stringify(cart));

        // Update the cart display (if necessary)
        loadCart();
    }
</script>

<script>
    document.getElementById('profile-icon').addEventListener('click', function () {
        document.getElementById('overlay').style.display = 'block';
        document.getElementById('profile-popup').style.display = 'block';
    });

    document.querySelector('#profile-popup .close-btn').addEventListener('click', function () {
        document.getElementById('overlay').style.display = 'none';
        document.getElementById('profile-popup').style.display = 'none';
    });

    document.getElementById('overlay').addEventListener('click', function () {
        document.getElementById('overlay').style.display = 'none';
        document.getElementById('profile-popup').style.display = 'none';
    });

    var userId = '<?php echo $user_id; ?>';
    var userUsername = '<?php echo $user_username; ?>';
    var userRole = '<?php echo $user_role; ?>';

    document.getElementById('user-id').textContent = userId;
    document.getElementById('user-username').textContent = userUsername;
    document.getElementById('user-role').textContent = userRole;
</script>

</body>
</html>
