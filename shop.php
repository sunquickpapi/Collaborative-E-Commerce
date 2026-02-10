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

// Fetch products
$sql = "SELECT * FROM products";
$products = $conn->query($sql);

$conn->close();

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LVNASTUDIO CUSTOMER DASHBOARD - shop</title>
    <script src="https://kit.fontawesome.com/1b07de51b4.js"></script>
    <link rel="stylesheet" href="style.css">
    <style>
        .pro-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
        }
        .product {
            width: 300px;
            margin: 20px;
            border: 1px solid #ccc;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .product img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        .product-details {
            padding: 15px;
        }
        .product-details h4 {
            margin: 0 0 10px;
            font-size: 18px;
        }
        .product-details p {
            margin: 0 0 5px;
            font-size: 14px;
        }
        #product-filter {
            display: block;
            margin: 20px auto;
            padding: 10px;
            font-size: 16px;
        }
        #no-products {
            text-align: center;
            font-size: 18px;
            color: red;
            margin-top: 20px;
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
                <li><a class="active" href="shop.php">Shop</a></li>
                <li><a href="blog.php">Blog</a></li>
                <li><a href="about.php">About</a></li>
                <li><a href="contact.php">Custom</a></li>
                <li id="lg-bag"><a href="cart.php"><i class="fa-solid fa-bag-shopping"></i></a></li>
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

    <section id="page-header">
        <h2>#stayinstyle</h2>
        <p>Save more with membership & up to 70% off</p>
    </section>

    <section>
        <select id="product-filter" onchange="filterProducts()">
            <option value="all">ALL TYPE</option>
            <option value="NFL Shirt">NFL Shirt</option>
            <option value="Retro Collar">Retro Collar</option>
            <option value="V-Neck Shirt">V-Neck Shirt</option>
            <option value="Button Shirt">Button Shirt</option>
            <option value="Round Neck Shirt">Round Neck Shirt</option>
        </select>
    </section>

    <section id="product1" class="section-p1">
        <div id="no-products" style="display:none;">Product sold out</div>
        <div class="pro-container">
            <?php while ($product = $products->fetch_assoc()): ?>
                <div class="pro" data-type="<?php echo htmlspecialchars($product['product_type']); ?>" onclick="window.location.href='sproduct1.php?id=<?php echo $product['product_id']; ?>';">
                    <img src="LVNAlatest/lvnaProducts/<?php echo htmlspecialchars($product['product_image_1']); ?>" alt="">
                    <div class="des">
                        <span><?php echo htmlspecialchars($product['product_type']); ?></span>
                        <h5><?php echo htmlspecialchars($product['product_name']); ?></h5>
                        <p>Items left: <?php echo htmlspecialchars($product['product_quantity']); ?></p>
                        <div class="star">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star-half-stroke"></i>
                            <i class="fa-regular fa-star"></i>
                        </div>
                        <h4>RM<?php echo htmlspecialchars($product['product_price']); ?></h4>
                    </div>
                    <a href="#" class="add-to-cart" data-product='{"name": "<?php echo htmlspecialchars($product['product_name']); ?>", "image": "LVNAlatest/lvnaProducts/<?php echo htmlspecialchars($product['product_image_1']); ?>", "price": <?php echo htmlspecialchars($product['product_price']); ?>}'><i class="fa-solid fa-cart-shopping cart"></i></a>
                </div>
            <?php endwhile; ?>
        </div>
    </section>

    <footer class="section-p1">
        <div class="col">
            <img class="logo" src="img/logo.png" alt="">
            <h4>Contact</h4>
            <p><strong>Address: </strong>Block 4807-3A-19, Jalan Perdana CBD Pedana 2, 63000 Cyberjaya Selangor</p>
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
        // Add to cart functionality
        document.querySelectorAll('.add-to-cart').forEach(button => {
            button.addEventListener('click', function(event) {
                event.preventDefault();
                const product = JSON.parse(this.getAttribute('data-product'));
                addToCart(product);
            });
        });

        function addToCart(product) {
            let cart = JSON.parse(localStorage.getItem('cart')) || [];
            cart.push(product);
            localStorage.setItem('cart', JSON.stringify(cart));
            alert('Product added to cart!');
        }

        // Filter products based on selected type
        function filterProducts() {
            const filter = document.getElementById('product-filter').value.toLowerCase();
            const products = document.querySelectorAll('.pro');
            let productFound = false;

            products.forEach(product => {
                const productType = product.getAttribute('data-type').toLowerCase();
                if (filter === 'all' || productType === filter) {
                    product.style.display = 'block';
                    productFound = true;
                } else {
                    product.style.display = 'none';
                }
            });

            document.getElementById('no-products').style.display = productFound ? 'none' : 'block';
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

    // Replace with actual user data from your server or session
    var userId = '<?php echo $user_id; ?>'; // Example user ID
    var userUsername = '<?php echo $user_username; ?>'; // Example user name
    var userRole = '<?php echo $user_role; ?>'; // Example user role

    document.getElementById('user-id').textContent = userId;
    document.getElementById('user-username').textContent = userUsername;
    document.getElementById('user-role').textContent = userRole;
</script>

</body>

</html>
