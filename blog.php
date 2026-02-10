<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = htmlspecialchars($_SESSION['user_id']);
$user_username = htmlspecialchars($_SESSION['user_username']);
$user_role = htmlspecialchars($_SESSION['user_role']);
?>
<!DOCTYPE html>
<html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>LVNASTUDIO CUSTOMER DASHBOARD - blog</title>
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
                    <li><a href="shop.php">Shop</a></li>
                    <li><a class="active" href="blog.php">Blog</a></li>
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

        <section id="page-header" class="blog-header">
            
            <h2>#customerechoes</h2>

            <p>Connect & Be Part Of LVNA Family</p>
        </section>

        <section id="blog">
            <div class="blog-box">
                <div class="blog-img">
                    <img src="img/blog/b1.jpg" alt="">
                </div>
                <div class="blog-details">
                    <h4>Material</h4>
                    <p class="summary">Jerseys crafted from a combination of premium materials offer athletes the perfect blend of comfort, performance, and durability. Here's a breakdown of the materials used...</p>
                    <p class="full-content" style="display: none;">Jerseys crafted from a combination of premium materials offer athletes the perfect blend of comfort, performance, and durability. Here’s a breakdown of the materials used:<br>
                        <strong>Lycra:</strong> Known for its exceptional elasticity, Lycra provides jerseys with a snug and flexible fit, allowing athletes to move freely without feeling restricted. It offers excellent stretch and recovery properties, ensuring that the jersey retains its shape over time.<br>
                        <strong>Microfiber Eyelet:</strong> Microfiber eyelet fabric is designed to enhance breathability and moisture management. Its textured surface promotes airflow, keeping athletes cool and dry during intense workouts or matches. This material wicks away sweat from the skin, preventing discomfort and chafing.<br>
                        <strong>Microfiber Diamond:</strong> Microfiber diamond fabric adds a touch of durability to jerseys. Its woven construction features a diamond-shaped pattern that enhances strength and resilience, making it suitable for rigorous activities. This material is also lightweight, ensuring that the jersey feels comfortable to wear for extended periods.<br>
                        <strong>Microfiber RJPK:</strong> Microfiber RJPK: Microfiber RJPK fabric is engineered for superior moisture absorption and quick-drying capabilities. Its smooth texture feels soft against the skin, while its moisture-wicking properties keep athletes feeling fresh and comfortable throughout their performance. This material is ideal for high-intensity sports where staying dry is essential.<br>
                        <strong>Microfiber Interlock:</strong> Microfiber interlock fabric offers a balance of stretch and stability. Its double-knit construction provides added durability and shape retention, ensuring that the jersey maintains its form even after repeated wear and washing. This material offers a smooth surface and a soft feel, making it comfortable to wear during training sessions or competitions.<br>
                        <strong>Microfiber Mini Eyelet:</strong> Microfiber mini eyelet fabric combines the benefits of breathability and moisture management in a lightweight package. Its small perforations create a subtle texture that promotes airflow, keeping athletes cool and comfortable in warm conditions. This material is perfect for jerseys worn during outdoor activities or under intense heat.</p>
                    <a href="#" class="toggle-link">CONTINUE READING</a>
                </div>
                <h1>001</h1>
            </div>
            <div class="blog-box">
                <div class="blog-img">
                    <img src="img/blog/b2.jpg" alt="">
                </div>
                <div class="blog-details">
                    <h4>Our Shop</h4>
                    <p class="summary">Luna Studio offers exclusive benefits such as early access to new products, special discounts, and updates about the brand. Luna Studio prioritizes their customers through several key initiatives...</p>
                    <p class="full-content" style="display: none;">Luna Studio offers exclusive benefits such as early access to new products, special discounts, and updates about the brand. Luna Studio prioritizes their customers through several key initiatives:<br>
                        <strong>Exclusive Community Membership:</strong>  Through "Lvna Familia," Luna Studio creates a loyal community by offering members exclusive benefits such as early access to new products and special discounts.<br>
                        <strong>Personalized Experiences:</strong>  They focus on providing a personalized and engaging experience, making each customer feel valued and connected to the brand.<br>
                        <strong>Customer-Centric Updates:</strong>  Members receive regular updates about the brand, ensuring they are always informed and involved in the latest developments.<br>
                        <strong>Special Discounts:</strong>  Luna Studio provides special discounts to "Lvna Familia" members, enhancing customer satisfaction and loyalty.<br>
                        By implementing these strategies, Luna Studio demonstrates a strong commitment to<strong>prioritizing their customers' needs</strong>  and fostering a <strong>sense of belonging</strong>  within their community.</p>
                    <a href="#" class="toggle-link">CONTINUE READING</a>
                </div>
                <h1>002</h1>
            </div>
            <div class="blog-box">
                <div class="blog-img">
                    <img src="img/blog/b3.jpg" alt="">
                </div>
                <div class="blog-details">
                    <h4>Feedback</h4>
                    <p class="summary">Luna Studio places a high value on customer feedback, using it to continuously improve their products and services. Customers are consistently satisfied with Luna Studio's offerings for several reasons...</p>
                    <p class="full-content" style="display: none;">Luna Studio places a high value on customer feedback, using it to continuously improve their products and services. Customers are consistently satisfied with Luna Studio's offerings for several reasons:<br>
                        <strong>High-Quality Materials:</strong>Products are crafted from premium materials ensuring durability and aesthetic appeal.<br>
                        <strong>Innovative Designs:</strong> The studio consistently releases unique and stylish designs that meet current fashion trends.<br>
                        <strong>Exceptional Customer Service:</strong> The responsive and friendly customer service team promptly addresses inquiries and issues.<br>
                        <strong>Personalized Shopping Experience:</strong>Customers enjoy tailored experiences, enhancing their overall satisfaction.<br>
                        <strong>Exclusive Member Benefits:</strong> "Lvna Familia" members receive special discounts and early access to new collections.</p>
                    <a href="#" class="toggle-link">CONTINUE READING</a>
                </div>
                <h1>003</h1>
            </div>
        </section>

        <section id="pagination" class="section-p1">
            <a href="#">1</a>
            <a href="#">2</a>
            <a href="#"><i class="fa-solid fa-arrow-right"></i></a>
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