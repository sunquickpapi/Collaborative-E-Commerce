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
        <title>LVNASTUDIO CUSTOMER DASHBOARD - about</title>
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
                    <li><a href="blog.php">Blog</a></li>
                    <li><a class="active" href="about.php">About</a></li>
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

        <section id="page-header" class="about-header">
            
            <h2>#lvnaorigin</h2>

            <p>Discover the Story Behind Our Passion</p>
        </section>

        <section id="about-head" class="section-p1">
            <img src="img/about/a6.jpg" alt="">
            <div>
                <h2>Who We Are?</h2>
                <p>In June 2022, we opened our first office at Cyberjaya. Luna Studio serves as a dynamic workspace, a versatile photoshoot studio, and a captivating showroom for our jersey designs. Our company comprises three essential departments: Administration, Marketing, and Production. Founded by MUHAMMAD ISKANDAR ZULKARNAIN, we prioritize the meticulous organization of all our databases using Notion software. This ensures that in the event of any unforeseen circumstances, such as disasters, our data remains safely retrievable. Our innovative approach enables us to efficiently manage customer and staff databases, assigning tasks seamlessly to the responsible staff or designers based on customer preferences.</p>
                
                <abbr title="">Your satisfaction is our commitment. Shop with us today and discover the difference!</abbr>
                <br><br>
                <marquee bgcolor="#ccc" loop="-1" scrollamount="5" width="100%">Embrace quality, innovation, and personalized service.</marquee>
            </div>
        </section>

        <section id="about-video" class="section-p1">
            <h1>Watch some <a href="#">videos</a> of ours</h1>
            <div class="video">
                <video controls muted loop src="img/about/1.mp4"></video>
            </div><br><br><br><br>
            <h1>Collaboration</h1>
        </section>

        <section id="feature" class="section-p1">

            <div class="fe-box">
                <img src="img/about/c1.jpg" alt="">
                <h6>SVG</h6>
            </div>
            <div class="fe-box">
                <img src="img/about/c2.jpg" alt="">
                <h6>Homebois</h6>
            </div>
            <div class="fe-box">
                <img src="img/about/c3.jpg" alt="">
                <h6>Artix.</h6>
            </div>
            <div class="fe-box">
                <img src="img/about/c4.jpg" alt="">
                <h6>OPT</h6>
            </div>
            <div class="fe-box">
                <img src="img/about/c5.jpg" alt="">
                <h6>Publr.</h6>
            </div>
            <div class="fe-box">
                <img src="img/about/c6.jpg" alt="">
                <h6>Blnk.</h6>
            </div>
        </section>

        <section id="contact-detail" class="section-p1">
            <div class="details">
                <span>Get In Touch</span>
                <h2>Visit one of our studio or contact us</h2>
                <h3>Head Studio</h3>
                <div>
                    <li>
                        <i class="fa-solid fa-map-location-dot"></i>
                        <p>Block 4807-3A-19, Jalan Perdana CBD Pedana 2, 63000 Cyberjaya Selangor</p>
                    </li>
                    <li>
                        <i class="fa-regular fa-envelope"></i>
                        <p>lunastudioofficial@gmail.com</p>
                    </li>
                    <li>
                        <i class="fa-solid fa-phone-volume"></i>
                        <p>601 2345 6789</p>
                    </li>
                    <li>
                        <i class="fa-solid fa-business-time"></i>
                        <p>Monday - Saturday, 10:00 - 18:00</p>
                    </li>
                </div>
            </div>
            <div class="map">
                <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3984.614984811226!2d101.6512719!3d2.9265045!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x31cdb7129fe595d9%3A0x3bdac78daee7c17a!2sLuna%20Studio!5e0!3m2!1sen!2smy!4v1716057765262!5m2!1sen!2smy" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
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