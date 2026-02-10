<?php
session_start();

// File-based counter for visitor count
$counterFile = 'visitor_count.txt';

if (!file_exists($counterFile)) {
    file_put_contents($counterFile, 0);
}

$visitorCount = file_get_contents($counterFile);
$visitorCount++;
file_put_contents($counterFile, $visitorCount);

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'customers') {
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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];
    $username = $_POST['username'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];

    // Update query
    $sql = "UPDATE customers SET c_username='$username', customer_phoneNum='$phone', customer_address='$address' WHERE customer_id=$id";

    if ($conn->query($sql) === TRUE) {
        $_SESSION['user_username'] = $username;
        echo "Record updated successfully";
    } else {
        echo "Error updating record: " . $conn->error;
    }
}

/// Fetch user data
$sql = "SELECT customer_phoneNum, customer_address FROM customers WHERE customer_id=$user_id";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $user_phone = htmlspecialchars($row['customer_phoneNum']);
    $user_address = htmlspecialchars($row['customer_address']);
} else {
    $user_phone = '';
    $user_address = '';
}

// Check if visitor_count column exists, and add it if not
$result = $conn->query("SHOW COLUMNS FROM admin LIKE 'visitor_count'");
if ($result->num_rows == 0) {
    $conn->query("ALTER TABLE admin ADD visitor_count INT DEFAULT 0");
}

// Increment visitor count
$sql = "UPDATE admin SET visitor_count = visitor_count + 1 WHERE admin_id = 1"; // Assuming admin_id = 1 is the relevant admin
$conn->query($sql);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LVNASTUDIO CUSTOMER DASHBOARD - home</title>
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
                <li><a class="active" href="index.php">Home</a></li>
                <li><a href="shop.php">Shop</a></li>
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
        <form id="profile-form" method="post" action="">
            <input type="hidden" name="id" value="<?php echo $user_id; ?>">
            <p><strong>ID:</strong> <?php echo $user_id; ?></p>
            <p><strong>Username:</strong> <input type="text" name="username" value="<?php echo $user_username; ?>" required></p>
            <p><strong>Phone Number:</strong> <input type="text" name="phone" value="<?php echo $user_phone; ?>"></p>
            <p><strong>Address:</strong> <input type="text" name="address" value="<?php echo $user_address; ?>"></p>
            <p><strong>Role:</strong> <?php echo $user_role; ?></p>
            <input type="submit" value="Save Changes">
        </form>
    </div>

    <section id="hero">
        <h4>Raya Haji Offer</h4>
        <h2>Super Cheap Deals</h2>
        <h1>On all Product</h1>
        <p>Save more with membership & up to 70% off</p>
        <button onclick="window.location.href='shop.php';">Shop Now</button>
    </section>

    <section id="feature" class="section-p1">
        <div class="fe-box">
            <img src="img/features/f1.png" alt="">
            <h6>NFL Shirt</h6>
        </div>
        <div class="fe-box">
            <img src="img/features/f2.png" alt="">
            <h6>Retro Collar</h6>
        </div>
        <div class="fe-box">
            <img src="img/features/f3.png" alt="">
            <h6>V-Neck Shirt</h6>
        </div>
        <div class="fe-box">
            <img src="img/features/f4.png" alt="">
            <h6>Button Shirt</h6>
        </div>
        <div class="fe-box">
            <img src="img/features/f5.png" alt="">
            <h6>Round Neck Shirt</h6>
        </div>
        <div class="fe-box">
            <img src="img/features/f6.png" alt="">
            <h6>Sleeveless</h6>
        </div>
    </section>

    <section id="product1" class="section-p1">
        <h2>Featured Products</h2>
        <p>New Modern Design</p>
        <div class="pro-container">
            <div class="pro">
                <img src="img/products/f1.png" alt="">
                <div class="des">
                    <span>V-Neck Shirt</span>
                    <h5>Evergreen Essence</h5>
                    <div class="star">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star-half-stroke"></i>
                        <i class="fa-regular fa-star"></i>
                    </div>
                    <h4>RM69</h4>
                </div>
                <a href="#"><i class="fa-solid fa-cart-shopping cart"></i></a>
            </div>
            <div class="pro">
                <img src="img/products/f2.png" alt="">
                <div class="des">
                    <span>Button Shirt</span>
                    <h5>Eclipse Hour</h5>
                    <div class="star">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fa-regular fa-star"></i>
                        <i class="fa-regular fa-star"></i>
                    </div>
                    <h4>RM59</h4>
                </div>
                <a href="#"><i class="fa-solid fa-cart-shopping cart"></i></a>
            </div>
            <div class="pro">
                <img src="img/products/f3.png" alt="">
                <div class="des">
                    <span>Retro Collar</span>
                    <h5>Petal Wave</h5>
                    <div class="star">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fa-regular fa-star"></i>
                        <i class="fa-regular fa-star"></i>
                    </div>
                    <h4>RM59</h4>
                </div>
                <a href="#"><i class="fa-solid fa-cart-shopping cart"></i></a>
            </div>
            <div class="pro">
                <img src="img/products/f4.png" alt="">
                <div class="des">
                    <span>NFL Shirt</span>
                    <h5>Shadow Frost</h5>
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
                <img src="img/products/f5.png" alt="">
                <div class="des">
                    <span>Button Shirt</span>
                    <h5>Vanilla Sky</h5>
                    <div class="star">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star-half-stroke"></i>
                        <i class="fa-regular fa-star"></i>
                        <i class="fa-regular fa-star"></i>
                    </div>
                    <h4>RM60</h4>
                </div>
                <a href="#"><i class="fa-solid fa-cart-shopping cart"></i></a>
            </div>
            <div class="pro">
                <img src="img/products/f6.png" alt="">
                <div class="des">
                    <span>V-Neck Shirt</span>
                    <h5>Bayside Bliss</h5>
                    <div class="star">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fa-regular fa-star"></i>
                        <i class="fa-regular fa-star"></i>
                    </div>
                    <h4>RM69</h4>
                </div>
                <a href="#"><i class="fa-solid fa-cart-shopping cart"></i></a>
            </div>
            <div class="pro">
                <img src="img/products/f7.png" alt="">
                <div class="des">
                    <span>V-Neck Shirt</span>
                    <h5>Tiger Blaze</h5>
                    <div class="star">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fa-regular fa-star"></i>
                        <i class="fa-regular fa-star"></i>
                    </div>
                    <h4>RM75</h4>
                </div>
                <a href="#"><i class="fa-solid fa-cart-shopping cart"></i></a>
            </div>
            <div class="pro">
                <img src="img/products/f8.png" alt="">
                <div class="des">
                    <span>V-Neck Shirt</span>
                    <h5>Pink Whisper</h5>
                    <div class="star">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star-half-stroke"></i>
                        <i class="fa-regular fa-star"></i>
                        <i class="fa-regular fa-star"></i>
                    </div>
                    <h4>RM69</h4>
                </div>
                <a href="#"><i class="fa-solid fa-cart-shopping cart"></i></a>
            </div>
        </div>
    </section>

    <section id="banner" class="section-m1">
        <h4>Repair Services</h4>
        <h2>Up to <span>70% Off</span> - All t-Shirts and Jerseys</h2>
        <button class="normal">Explore More</button>
    </section>

    <section id="product1" class="section-p1">
        <h2>New Arrival</h2>
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
                        <h4>RM79</h4>
                    </div>
                    <a href="#"><i class="fa-solid fa-cart-shopping cart"></i></a>
                </div>
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
                        <h4>RM60</h4>
                    </div>
                    <a href="#"><i class="fa-solid fa-cart-shopping cart"></i></a>
                </div>
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
                        <h4>RM60</h4>
                    </div>
                    <a href="#"><i class="fa-solid fa-cart-shopping cart"></i></a>
                </div>
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
                        <h4>RM79</h4>
                    </div>
                    <a href="#"><i class="fa-solid fa-cart-shopping cart"></i></a>
                </div>
            </div>
            <div class="pro">
                <img src="img/products/n5.png" alt="">
                <div class="des">
                    <span>V-Neck Shirt</span>
                    <h5>Glass Serenity</h5>
                    <div class="star">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star-half-stroke"></i>
                        <i class="fa-regular fa-star"></i>
                        <h4>RM60</h4>
                    </div>
                    <a href="#"><i class="fa-solid fa-cart-shopping cart"></i></a>
                </div>
            </div>
            <div class="pro">
                <img src="img/products/n6.png" alt="">
                <div class="des">
                    <span>Round Neck Shirt</span>
                    <h5>Twin Flames</h5>
                    <div class="star">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fa-regular fa-star"></i>
                        <i class="fa-regular fa-star"></i>
                        <h4>RM59</h4>
                    </div>
                    <a href="#"><i class="fa-solid fa-cart-shopping cart"></i></a>
                </div>
            </div>
            <div class="pro">
                <img src="img/products/n7.png" alt="">
                <div class="des">
                    <span>Retro Collar</span>
                    <h5>Velvet Rose</h5>
                    <div class="star">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fa-regular fa-star"></i>
                        <i class="fa-regular fa-star"></i>
                        <h4>RM70</h4>
                    </div>
                    <a href="#"><i class="fa-solid fa-cart-shopping cart"></i></a>
                </div>
            </div>
            <div class="pro">
                <img src="img/products/n8.png" alt="">
                <div class="des">
                    <span>Round Neck Shirt</span>
                    <h5>Blazing Blue</h5>
                    <div class="star">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star-half-stroke"></i>
                        <i class="fa-regular fa-star"></i>
                        <i class="fa-regular fa-star"></i>
                        <h4>RM70</h4>
                    </div>
                    <a href="#"><i class="fa-solid fa-cart-shopping cart"></i></a>
                </div>
            </div>
        </div>
    </section>

    <section id="sm-banner" class="section-p1">
        <div class="banner-box">
            <h4>crazy deals</h4>
            <h2>buy 1 get free 1</h2>
            <span>The best jerseys in town</span>
            <button class="white">Learn More</button>
        </div>
        <div class="banner-box banner-box2">
            <h4>Jersey/Modern</h4>
            <h2>upcoming season</h2>
            <span>The best jerseys in town</span>
            <button class="white">Collection</button>
        </div>
    </section>

    <section id="banner3">
        <div class="banner-box">
            <h2>SEASONAL SALE</h2>
            <h3>Modern Collection -30% OFF</h3>
        </div>
        <div class="banner-box banner-box2">
            <h2>CRAFT YOUR CONFIDENCE</h2>
            <h3>NFL Collection -30% OFF</h3>
        </div>
        <div class="banner-box banner-box3">
            <h2>STYLE MEETS COMFORT</h2>
            <h3>Jersey Collection -20% OFF</h3>
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
        var userId = '<?php echo $user_id; ?>';
        var userUsername = '<?php echo $user_username; ?>';
        var userRole = '<?php echo $user_role; ?>';
        var userPhone = '<?php echo $user_phone; ?>';
        var userAddress = '<?php echo $user_address; ?>';

        document.getElementById('user-id').textContent = userId;
        document.getElementById('user-username').textContent = userUsername;
        document.getElementById('user-role').textContent = userRole;

        document.getElementById('profile-form').addEventListener('submit', function (e) {
            e.preventDefault();
            updateProfile();
        });

        document.getElementById('profile-id').value = userId;
        document.getElementById('profile-id-text').textContent = userId;
        document.getElementById('profile-username').value = userUsername;
        document.getElementById('profile-phone').value = userPhone;
        document.getElementById('profile-address').value = userAddress;
        document.getElementById('profile-role').textContent = userRole;

        function updateProfile() {
            const form = document.getElementById('profile-form');
            const formData = new FormData(form);
            fetch('update_profile.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(result => {
                alert(result);
                document.getElementById('overlay').style.display = 'none';
                document.getElementById('profile-popup').style.display = 'none';
            });
        }
    </script>

    <style>
        /* Profile Popup Styles */
#overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    display: none;
    z-index: 10;
}

#profile-popup {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: white;
    padding: 20px;
    border-radius: 8px;
    z-index: 20;
    display: none;
    width: 400px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

#profile-popup h3 {
    margin-bottom: 20px;
    color: #555;
}

#profile-popup p {
    margin: 5px 0;
}

#profile-popup input[type="text"] {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 8px;
    margin-bottom: 10px;
}

#profile-popup .close-btn {
    position: absolute;
    top: 10px;
    right: 10px;
    cursor: pointer;
    color: #555;
}

#profile-popup input[type="submit"] {
    padding: 10px 20px;
    border: none;
    background-color: #4caf50;
    color: white;
    border-radius: 8px;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

#profile-popup input[type="submit"]:hover {
    background-color: #45a049;
}

    </style>
    <script src="script.js"></script>
</body>
</html>
