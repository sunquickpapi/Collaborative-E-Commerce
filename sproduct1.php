<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = htmlspecialchars($_SESSION['user_id']);
$user_username = htmlspecialchars($_SESSION['user_username']);
$user_role = htmlspecialchars($_SESSION['user_role']);

$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($product_id == 0) {
    die("Invalid product ID.");
}

// Database connection
$conn = new mysqli("localhost", "root", "", "lunadb");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch product details
$sql = "SELECT * FROM products WHERE product_id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    die("Product not found.");
}

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

    <section id="prodetails" class="section-p1">
        <div class="single-pro-image">
            <img src="LVNAlatest/lvnaProducts/<?php echo htmlspecialchars($product['product_image_1']); ?>" width="100%" id="MainImg" alt="">
            <div class="small-img-group">
                <?php for ($i = 1; $i <= 4; $i++): ?>
                    <?php if (!empty($product["product_image_$i"])): ?>
                        <div class="small-img-col">
                            <img src="LVNAlatest/lvnaProducts/<?php echo htmlspecialchars($product["product_image_$i"]); ?>" width="100%" class="small-img" alt="">
                        </div>
                    <?php endif; ?>
                <?php endfor; ?>
            </div>
        </div>

        <div class="single-pro-details">
            <h6><?php echo htmlspecialchars($product['product_type']); ?></h6>
            <h4><?php echo htmlspecialchars($product['product_name']); ?></h4>
            <h2>RM<?php echo htmlspecialchars($product['product_price']); ?></h2>
            <select id="productSize">
                <option>Select Size</option>
                <option>XL</option>
                <option>XXL</option>
                <option>S</option>
                <option>L</option>
            </select>
            <input type="number" value="1" id="quantity" min="1">
            <button class="normal" id="addToCartBtn">Add to cart</button>
            <h4>Product Details</h4>
            <span><?php echo htmlspecialchars($product['product_details']); ?></span>
        </div>
    </section>

    <section id="product1" class="section-p1">
        <h2>Featured Products</h2>
        <p>New Modern & Sport Design</p>
        <div class="pro-container">
            <div class="pro">
                <img src="img/products/n1.png" alt="">
                <div class="des">
                    <span>Retro Collar</span>
                    <h5>Jade Emerald</h5>
                    <div class="star">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star-half-stroke"></i>
                    </div>
                    <h4>RM79</h4>
                </div>
                <a href="#"><i class="fa-solid fa-cart-shopping cart"></i></a>
            </div>
            <div class="pro">
                <img src="img/products/n2.png" alt="">
                <div class="des">
                    <span>Green Gridlock</span>
                    <h5>Eclipse Hour</h5>
                    <div class="star">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fa-regular fa-star"></i>
                        <i class="fa-regular fa-star"></i>
                    </div>
                    <h4>RM60</h4>
                </div>
                <a href="#"><i class="fa-solid fa-cart-shopping cart"></i></a>
            </div>
            <div class="pro">
                <img src="img/products/n3.png" alt="">
                <div class="des">
                    <span>Retro Collar</span>
                    <h5>Metallic Slate</h5>
                    <div class="star">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fa-regular fa-star"></i>
                    </div>
                    <h4>RM60</h4>
                </div>
                <a href="#"><i class="fa-solid fa-cart-shopping cart"></i></a>
            </div>
            <div class="pro">
                <img src="img/products/n4.png" alt="">
                <div class="des">
                    <span>Retro Collar</span>
                    <h5>Evervibe</h5>
                    <div class="star">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star-half-stroke"></i>
                    </div>
                    <h4>RM79</h4>
                </div>
                <a href="#"><i class="fa-solid fa-cart-shopping cart"></i></a>
            </div>
        </div>
    </section>

    <section id="newsletter" class="section-p1 section-m1">
        <div class="newstext">
            <h4>Sign Up For Newsletter</h4>
            <p>Get email update about our latest shop & <span>special offers.</span></p>
        </div>
        <div class="form">
            <input type="text" placeholder="Your Email address">
            <button class="normal">Sign Up</button>
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
        var MainImg = document.getElementById("MainImg");
        var smallimg = document.getElementsByClassName("small-img");

        smallimg[0].onclick = function () {
            MainImg.src = smallimg[0].src;
        }
        smallimg[1].onclick = function () {
            MainImg.src = smallimg[1].src;
        }
        smallimg[2].onclick = function () {
            MainImg.src = smallimg[2].src;
        }
        smallimg[3].onclick = function () {
            MainImg.src = smallimg[3].src;
        }

        document.getElementById('addToCartBtn').addEventListener('click', function () {
            const quantity = parseInt(document.getElementById('quantity').value);
            const size = document.getElementById('productSize').value;
            if (size === "Select Size") {
                alert("Please select a size");
                return;
            }
            const product = {
                name: '<?php echo htmlspecialchars($product['product_name']); ?>',
                image: 'LVNAlatest/lvnaProducts/<?php echo htmlspecialchars($product['product_image_1']); ?>',
                price: <?php echo htmlspecialchars($product['product_price']); ?>,
                quantity: quantity,
                size: size
            };
            addToCart(product);
            window.location.href = 'cart.php';
        });

        function addToCart(product) {
            let cart = JSON.parse(localStorage.getItem('cart')) || [];
            cart.push(product);
            localStorage.setItem('cart', JSON.stringify(cart));
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

    <script src="script.js"></script>
</body>

</html>
