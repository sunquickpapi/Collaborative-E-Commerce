<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['proceed_checkout'])) {
    $payment_method = htmlspecialchars($_POST['payment_method']);
    $agent_id = htmlspecialchars($_POST['agent_id']);
    $custom_id = isset($_POST['custom_id']) ? htmlspecialchars($_POST['custom_id']) : '';
    $design_id = isset($_POST['design_id']) ? htmlspecialchars($_POST['design_id']) : '';

    // Database connection
    include("connection.php");

    // Auto-assign an agent (if not already assigned)
    if (empty($agent_id)) {
        $sql = "SELECT agent_id FROM agent ORDER BY RAND() LIMIT 1";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            $agent = $result->fetch_assoc();
            $agent_id = $agent['agent_id'];
        } else {
            $agent_id = null; // Handle the case when there are no agents
        }
    }

    // Insert new shipment to get shipment_id
    $sql = "INSERT INTO shipment () VALUES ()"; // Adjust this query to your shipments table structure
    if ($conn->query($sql) === TRUE) {
        $shipment_id = $conn->insert_id;
    } else {
        die("Error inserting shipment: " . $conn->error);
    }

    // Initialize order details variable
    $order_details = '';

    // Fetch the design title based on custom ID and design ID
    if ($custom_id && $design_id) {
        $sql = "SELECT design_title FROM designs WHERE design_id=?";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("i", $design_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result && $result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $design_title = htmlspecialchars($row['design_title']);
                $order_details = $design_title . ' (Custom)';
            }
            $stmt->close();
        } else {
            echo "Error preparing statement: " . $conn->error;
        }
    }

    // Fetch total price from custom table for the specific custom ID
    $cartSubtotal = 0;
    if (!empty($custom_id)) {
        $sql = "SELECT total_price FROM custom WHERE customer_id=? AND custom_id=?";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("ii", $_SESSION['user_id'], $custom_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result && $result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $cartSubtotal = $row['total_price'];
            } else {
                echo "No total_price found for custom_id: $custom_id<br>";
            }
            $stmt->close();
        } else {
            echo "Error preparing statement: " . $conn->error;
        }
    } else {
        // echo "No custom_id provided<br>";
    }
    $order_total = $cartSubtotal;

    // Ensure order_details is not empty
    if (empty($order_details)) {
        $order_details = 'Custom Order'; // Default value if no design title is found
    }

    // Debugging output
    echo "Order Details: " . $order_details . "<br>";
    echo "Order Total: " . $order_total . "<br>";

    // Insert order into the orders table
    $sql = "INSERT INTO orders (order_detail, order_totPrice, customer_id, agent_id, shipment_id, order_time) VALUES (?, ?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die("prepare() failed: " . htmlspecialchars($conn->error));
    }

    $stmt->bind_param("sdiii", $order_details, $order_total, $_SESSION['user_id'], $agent_id, $shipment_id);
    if ($stmt->execute()) {
        $order_id = $stmt->insert_id; // Get the last inserted order ID
        echo "Order inserted with ID: $order_id<br>";

        // Insert payment details into the payment table
        $sql = "INSERT INTO payment (order_id, payment_method, customer_id) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            die("prepare() failed: " . htmlspecialchars($conn->error));
        }

        $stmt->bind_param("isi", $order_id, $payment_method, $_SESSION['user_id']);
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
                echo "<script>alert('Thanks for choosing us!');</script>";
                exit();
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
    $conn->close();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = htmlspecialchars($_SESSION['user_id']);
$user_username = htmlspecialchars($_SESSION['user_username']);
$user_role = htmlspecialchars($_SESSION['user_role']);

// Database connection
include("connection.php");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch all agents with unread message notification
$sql = "
    SELECT agent.agent_id, agent.agent_username, 
           MAX(CASE WHEN messages.read_status = 0 THEN 1 ELSE 0 END) AS has_unread
    FROM agent
    LEFT JOIN messages ON agent.agent_id = messages.sender_id AND messages.receiver_id = ? AND messages.receiver_role = 'customer'
    GROUP BY agent.agent_id, agent.agent_username";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$agents = [];
while ($row = $result->fetch_assoc()) {
    $agents[] = $row;
}
$stmt->close();

// Function to mark messages as read
function markMessagesAsRead($conn, $receiver_id, $sender_id) {
    $sql = "UPDATE messages SET read_status = 1 WHERE receiver_id = ? AND sender_id = ? AND receiver_role = 'customer'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $receiver_id, $sender_id);
    $stmt->execute();
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_as_read'])) {
    $receiver_id = $_POST['receiver_id'];
    $sender_id = $_POST['sender_id'];
    markMessagesAsRead($conn, $receiver_id, $sender_id);
    echo json_encode(['success' => true]);
    exit();
}

// Fetch user data based on user role
if ($user_role == 'customers') {
    $sql = "SELECT customer_phoneNum, customer_address FROM customers WHERE customer_id=?";
} else {
    // Handle other roles if needed
}

$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $user_phone = htmlspecialchars($row['customer_phoneNum']);
        $user_address = htmlspecialchars($row['customer_address']);
    } else {
        $user_phone = '';
        $user_address = '';
    }
    $stmt->close();
} else {
    echo "Error preparing statement: " . $conn->error;
}

$custom_id = isset($_POST['custom_id']) ? htmlspecialchars($_POST['custom_id']) : '';

// Fetch total price from custom table for the specific custom ID
$cartSubtotal = 0;
if ($custom_id) {
    $sql = "SELECT total_price FROM custom WHERE customer_id=? AND custom_id=?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("ii", $user_id, $custom_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $cartSubtotal = $row['total_price'];
        } else {
            echo "No total_price found for custom_id: $custom_id<br>";
        }
        $stmt->close();
    } else {
        echo "Error preparing statement: " . $conn->error;
    }
} else {
    // echo "No custom_id provided<br>";
}


$conn->close()
?>
<?php
include("connection.php");

$sql = "SELECT agent.agent_id, agent.agent_username, 
               MAX(CASE WHEN messages.read_status = 0 THEN 1 ELSE 0 END) AS has_unread
        FROM agent
        LEFT JOIN messages ON agent.agent_id = messages.sender_id AND messages.receiver_id = ? AND messages.receiver_role = 'customer'
        GROUP BY agent.agent_id, agent.agent_username";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$agents = [];
while ($row = $result->fetch_assoc()) {
    $agents[] = $row;
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
    <title>LVNASTUDIO CUSTOMER DASHBOARD - custom</title>
    <script src="https://kit.fontawesome.com/1b07de51b4.js"></script>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <section id="header">
        <div>
            <div id="profile-icon">
                <i class="fa-solid fa-user"></i>
                <div id="profile-details">
                    <p><strong>ID:</strong> <span id="user-id"></span></p>
                    <p><strong>Username:</strong> <span id="user-username"></span></p>
                    <p><strong>Role:</strong> <span id="user-role"></span></p>
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
                <li><a class="active" href="contact.php">Custom</a></li>
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
        <form method="post" action="">
            <input type="hidden" name="id" value="<?php echo $user_id; ?>">
            <p><strong>ID:</strong> <?php echo $user_id; ?></p>
            <p><strong>Username:</strong> <input type="text" name="username" value="<?php echo $user_username; ?>" required></p>
            <p><strong>Phone Number:</strong> <input type="text" name="phone" value="<?php echo $user_phone; ?>"></p>
            <p><strong>Address:</strong> <input type="text" name="address" value="<?php echo $user_address; ?>"></p>
            <p><strong>Role:</strong> <?php echo $user_role; ?></p>
            <input type="submit" value="Save Changes">
        </form>
    </div>

    <section id="page-header" class="contact-header">
        <h2>#customYourOwn!</h2>
        <p>Unleash your creativity here at LVNA</p>
    </section>

    <!-- Size Chart Section -->
    <section id="size-chart">
        <table>
            <thead>
                <tr>
                    <th>Size</th>
                    <th>XS</th>
                    <th>S</th>
                    <th>M</th>
                    <th>L</th>
                    <th>XL</th>
                    <th>2XL</th>
                    <th>3XL</th>
                    <th>4XL</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Width</td>
                    <td>18</td>
                    <td>19</td>
                    <td>20</td>
                    <td>21</td>
                    <td>22</td>
                    <td>23</td>
                    <td>24</td>
                    <td>25</td>
                </tr>
                <tr>
                    <td>Length</td>
                    <td>25</td>
                    <td>26</td>
                    <td>27</td>
                    <td>28</td>
                    <td>29</td>
                    <td>30</td>
                    <td>31</td>
                    <td>32</td>
                </tr>
                <tr>
                    <td>Shoulder</td>
                    <td>15</td>
                    <td>16</td>
                    <td>17</td>
                    <td>18</td>
                    <td>29</td>
                    <td>20</td>
                    <td>21</td>
                    <td>22</td>
                </tr>
                <tr>
                    <td>Sleeve (Short)</td>
                    <td>8</td>
                    <td>8.5</td>
                    <td>9</td>
                    <td>9.5</td>
                    <td>10</td>
                    <td>10.5</td>
                    <td>11</td>
                    <td>11.5</td>
                </tr>
                <tr>
                    <td>Sleeve (Long)</td>
                    <td>22</td>
                    <td>22.5</td>
                    <td>23</td>
                    <td>23.5</td>
                    <td>24</td>
                    <td>24.5</td>
                    <td>25</td>
                    <td>25.5</td>
                </tr>
            </tbody>
        </table>
    </section>

    <!-- Custom Jersey Section -->
    <section id="custom-jersey">
        <h2>Customize Your Jersey</h2>
        <form id="custom-jersey-form">
            <table>
                <tr>
                    <td><label>Jersey Fabric:</label></td>
                    <td>
                        <select id="jersey_fabric" name="jersey_fabric">
                            <option value="Lycra">Lycra</option>
                            <option value="Microfiber">Microfiber</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td><label>Jersey Colour:</label></td>
                    <td><input type="text" id="jersey_colour" name="jersey_colour"></td>
                </tr>
                <tr>
                    <td><label>Front Logo:</label></td>
                    <td><input type="file" id="front_logo" name="front_logo"></td>
                </tr>
                <tr>
                    <td><label>Front Logo Position:</label></td>
                    <td>
                        <select id="front_logo_position" name="front_logo_position">
                            <option value="left">Left</option>
                            <option value="middle">Middle</option>
                            <option value="right">Right</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td><label>Back Logo:</label></td>
                    <td><input type="file" id="back_logo" name="back_logo"></td>
                </tr>
                <tr>
                    <td><label>Back Logo Position:</label></td>
                    <td>
                        <select id="back_logo_position" name="back_logo_position">
                            <option value="left">Left</option>
                            <option value="middle">Middle</option>
                            <option value="right">Right</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td><label>Design Description:</label></td>
                    <td><textarea id="design_description" name="design_description"></textarea></td>
                </tr>
                <tr>
                    <td><label>Quantity:</label></td>
                    <td>
                        <label>Size S: <input type="number" id="quantity_s" name="quantity_s" value="0"></label>
                        <label>Size M: <input type="number" id="quantity_m" name="quantity_m" value="0"></label>
                        <label>Size L: <input type="number" id="quantity_l" name="quantity_l" value="0"></label>
                        <label>Size XL: <input type="number" id="quantity_xl" name="quantity_xl" value="0"></label>
                        <label>Size 2XL: <input type="number" id="quantity_2xl" name="quantity_2xl" value="0"></label>
                        <label>Size 3XL: <input type="number" id="quantity_3xl" name="quantity_3xl" value="0"></label>
                        <label>Size 4XL: <input type="number" id="quantity_4xl" name="quantity_4xl" value="0"></label>
                    </td>
                </tr>
                <tr>
                    <td>Total Price:</td>
                    <td><span id="total-price">0</span></td>
                </tr>
                <tr>
                    <td><button type="submit">Submit</button></td>
                    <td><button type="button" id="edit-btn" style="display:none;">Edit</button></td>
                </tr>
                <tr>
                    <td>Custom ID:</td>
                    <td><span id="custom-id"></span></td>
                </tr>
                <tr>
                    <td colspan="2"><span id="error-message" style="color: red;"></span></td>
                </tr>
            </table>
        </form>
    </section>

    <section id="chat-section">
        <div id="agent-list">
            <h3>Agents</h3>
            <?php foreach ($agents as $agent) : ?>
                <div class="agent" data-id="<?php echo htmlspecialchars($agent['agent_id']); ?>">
                    <?php echo htmlspecialchars($agent['agent_username']); ?>
                    <?php if ($agent['has_unread'] > 0) : ?>
                        <span class="badge">New Message</span>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <div id="chat-box">
            <div id="messages"></div>
            <form id="message-form">
                <input type="hidden" id="receiver-id" name="receiver_id" value="">
                <input type="hidden" id="receiver-role" name="receiver_role" value="agent">
                <textarea id="message" name="message" placeholder="Type your message here"></textarea>
                <button type="submit">Send</button>
                <button type="button" id="complete-order-btn">Complete Order</button>
            </form>
        </div>
    </section>

    <section id="design-section">
        <h2>View Final Design</h2>
        <form id="design-id-form">
            <label for="design-id">Enter Design ID:</label>
            <input type="number" id="design-id" name="design-id" required>
            <button type="submit">View Design</button>
        </form>
    </section>

    <div id="design-popup">
        <span class="close-design-btn">&times;</span>
        <h3>Design Details</h3>
        <div id="design-details"></div>
    </div>

    <div id="overlay"></div>

    <!-- Complete Order and Checkout Section -->
    <section id="complete-order-section">
        <h2>Complete and Checkout Order</h2>
        <form id="complete-order-form" method="POST">
            <input type="number" id="custom-id-input" name="custom_id" placeholder="Enter Custom ID" required>
            <input type="number" id="design-id-input" name="design_id" placeholder="Enter Design ID" required>
            <button type="submit">Complete Order</button>
        </form>
        <div id="order-details-container">
            <!-- Order details will be injected here -->
        </div>

        <div id="customer-info">
            <h3>Customer's Information</h3>
            <table>
                <tr>
                    <th>Name</th>
                    <td id="customer-name"><?php echo $user_username; ?></td>
                </tr>
                <tr>
                    <th>Phone Number</th>
                    <td id="customer-phone"><?php echo $user_phone; ?></td>
                </tr>
                <tr>
                    <th>Address</th>
                    <td id="customer-address"><?php echo $user_address; ?></td>
                </tr>
            </table>
        </div>
        <!-- Cart Total Section -->
        <div id="subtotal">
            <h3>Cart Total</h3>
            <table>
                <tr>
                    <td>Cart Subtotal</td>
                    <td id="cart-subtotal">RM0.00</td>
                </tr>
                <tr>
                    <td>Shipping</td>
                    <td>Free</td>
                </tr>
                <tr id="discountRow" style="display:none;">
                    <td>Discount</td>
                    <td id="discount">0%</td>
                </tr>
                <tr>
                    <td><strong>Total</strong></td>
                    <td><strong id="cart-total-amount">RM0.00</strong></td>
                </tr>
            </table>
            <form method="POST" id="checkoutForm" onsubmit="return submitOrder()">
                <input type="hidden" name="order_details" id="order-details">
                <input type="hidden" name="order_total" id="order-total">
                <input type="hidden" name="agent_id" id="agentId">
                <input type="hidden" name="custom_id" id="customId"> <!-- Ensure this hidden input exists -->
                <select name="payment_method" id="payment-method-select" required>
                    <option value="">Select Payment Method</option>
                    <option value="Credit Card">Credit Card</option>
                    <option value="Debit Card">Debit Card</option>
                    <option value="PayPal">PayPal</option>
                    <option value="Bank Transfer">Bank Transfer</option>
                </select>
                <button type="submit" class="normal" name="proceed_checkout">Proceed to Checkout</button>
            </form>
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
        document.addEventListener('DOMContentLoaded', () => {
            const agentList = document.querySelectorAll('.agent');
            let selectedAgent = null;

            agentList.forEach(agent => {
                agent.addEventListener('click', () => {
                    if (selectedAgent === agent) return;

                    selectedAgent = agent;
                    agentList.forEach(a => {
                        if (a !== agent) {
                            a.classList.add('unclickable');
                        } else {
                            a.classList.remove('unclickable');
                        }
                    });

                    const receiverId = agent.getAttribute('data-id');
                    document.getElementById('receiver-id').value = receiverId;
                    document.getElementById('agentId').value = receiverId;
                    document.getElementById('receiver-role').value = 'agent';
                    fetchMessages(receiverId);

                    // Mark messages as read
                    fetch('contact.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `mark_as_read=true&receiver_id=${user_id}&sender_id=${receiverId}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        const badge = agent.querySelector('.badge');
                        if (badge) {
                            badge.remove();
                        }
                        // Update the New Message count
                        document.querySelector('.box-info .bxs-message + .text h3').textContent = data.unread_count;
                    });
                });
            });

            document.getElementById('message-form').addEventListener('submit', (e) => {
                e.preventDefault();
                sendMessage();
            });
            
            document.getElementById('complete-order-btn').addEventListener('click', () => {
                document.getElementById('complete-order-section').scrollIntoView({ behavior: 'smooth' });
            });

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

            function fetchMessages(receiverId) {
                fetch(`fetch_messages.php?receiver_id=${receiverId}&receiver_role=agent`)
                    .then(response => response.json())
                    .then(messages => {
                        const messageBox = document.getElementById('messages');
                        messageBox.innerHTML = '';
                        if (messages.length === 0) {
                            messageBox.innerHTML = '<div class="message">No messages yet.</div>';
                        } else {
                            messages.forEach(msg => {
                                const messageElement = document.createElement('div');
                                messageElement.className = 'message';
                                messageElement.innerHTML = `<strong>${msg.sender_name}:</strong> ${msg.message}`;
                                messageBox.appendChild(messageElement);
                            });
                        }
                    });
            }

            function sendMessage() {
                const form = document.getElementById('message-form');
                const formData = new FormData(form);
                fetch('send_message.php', {
                    method: 'POST',
                    body: formData
                })
                    .then(response => response.text())
                    .then(result => {
                        const receiverId = document.getElementById('receiver-id').value;
                        fetchMessages(receiverId);
                    });
            }

            document.getElementById('design-id-form').addEventListener('submit', function (e) {
                e.preventDefault();
                const designId = document.getElementById('design-id').value;
                fetchDesignDetails(designId);
            });

            function fetchDesignDetails(designId) {
                fetch(`fetch_design.php?design_id=${designId}`)
                    .then(response => response.json())
                    .then(data => {
                        const designDetails = document.getElementById('design-details');
                        if (data.error) {
                            designDetails.innerHTML = `<p>${data.error}</p>`;
                        } else {
                            designDetails.innerHTML = `
                                <p><strong>Design ID:</strong> ${data.id}</p>
                                <p><strong>Agent Username:</strong> ${data.agent_username}</p>
                                <p><strong>Design Title:</strong> ${data.design_title}</p>
                                <p><strong>Design Description:</strong> ${data.design_description}</p>
                                <img src="${data.design_image}" alt="Design Image">
                            `;
                        }
                        document.getElementById('overlay').style.display = 'block';
                        document.getElementById('design-popup').style.display = 'block';
                    })
                    .catch(error => {
                        console.error('Error fetching design details:', error);
                    });
            }

            document.querySelector('#design-popup .close-design-btn').addEventListener('click', function () {
                document.getElementById('overlay').style.display = 'none';
                document.getElementById('design-popup').style.display = 'none';
            });

            document.getElementById('overlay').addEventListener('click', function () {
                document.getElementById('overlay').style.display = 'none';
                document.getElementById('design-popup').style.display = 'none';
            });

            document.getElementById('complete-order-form').addEventListener('submit', function(e) {
                e.preventDefault();
                const customId = document.getElementById('custom-id-input').value;
                const designId = document.getElementById('design-id-input').value;
                fetchOrderDetails(customId, designId);
            });

            // Ensure updateCartTotal is called when the complete order form is submitted
            document.getElementById('complete-order-form').addEventListener('submit', function(e) {
                e.preventDefault();
                const customId = document.getElementById('custom-id-input').value;
                updateCartTotal(customId);
            });

            function updateCartTotal(customId) {
                if (customId) {
                    fetch(`get_custom_total.php?custom_id=${customId}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                document.getElementById('cart-subtotal').textContent = 'RM' + parseFloat(data.total_price).toFixed(2);
                                document.getElementById('cart-total-amount').textContent = 'RM' + parseFloat(data.total_price).toFixed(2);
                            } else {
                                document.getElementById('cart-subtotal').textContent = 'RM0.00';
                                document.getElementById('cart-total-amount').textContent = 'RM0.00';
                                alert(data.message);
                            }
                        })
                        .catch(error => console.error('Error fetching cart total:', error));
                }
            }

            function fetchOrderDetails(customId, designId) {
                fetch(`fetch_order_details.php?custom_id=${customId}&design_id=${designId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.error) {
                            alert(data.error);
                        } else {
                            displayOrderDetails(data);
                            updateCartTotal(customId); // Update the cart total
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching order details:', error);
                    });
            }

            function displayOrderDetails(data) {
                const orderDetailsContainer = document.getElementById('order-details-container');
                orderDetailsContainer.innerHTML = `
                    <h3>Order Details</h3>
                    <table>
                        <tr><th>Custom ID</th><td>${data.custom_id}</td></tr>
                        <tr><th>Customer ID</th><td>${data.customer_id}</td></tr>
                        <tr><th>Jersey Fabric</th><td>${data.jersey_fabric}</td></tr>
                        <tr><th>Jersey Color</th><td>${data.jersey_color}</td></tr>
                        <tr><th>Design Description</th><td>${data.design_description}</td></tr>
                        <tr><th>Quantity S</th><td>${data.quantity_s}</td></tr>
                        <tr><th>Quantity M</th><td>${data.quantity_m}</td></tr>
                        <tr><th>Quantity L</th><td>${data.quantity_l}</td></tr>
                        <tr><th>Quantity XL</th><td>${data.quantity_xl}</td></tr>
                        <tr><th>Quantity 2XL</th><td>${data.quantity_2xl}</td></tr>
                        <tr><th>Quantity 3XL</th><td>${data.quantity_3xl}</td></tr>
                        <tr><th>Quantity 4XL</th><td>${data.quantity_4xl}</td></tr>
                        <tr><th>Total Price</th><td id="total-price">RM${data.total_price}</td></tr>
                        <tr><th>Design ID</th><td>${data.id}</td></tr>
                        <tr><th>Design Image</th><td><img src="${data.design_image}" alt="Design Image" style="max-width: 200px;"></td></tr>
                        <tr><th>Design Title</th><td>${data.design_title}</td></tr>
                    </table>
                `;
            }

            window.submitOrder = function() {
                const paymentMethod = document.getElementById('payment-method-select').value;
                if (!paymentMethod) {
                    alert("Please select a payment method.");
                    return false;
                }

                const customId = document.getElementById('custom-id-input').value; // or however you get the custom_id
                const designId = document.getElementById('design-id-input').value;

                document.getElementById('order-details').value = `Custom ID: ${customId}, Design ID: ${designId}`; // Add actual order details here
                document.getElementById('order-total').value = parseFloat(document.getElementById('cart-total-amount').textContent.replace('RM', ''));
                document.getElementById('customId').value = customId; // Set the hidden input field for custom_id
                return true;
            };
        });

document.addEventListener('DOMContentLoaded', () => {
        const customForm = document.getElementById('custom-jersey-form');
        const editBtn = document.getElementById('edit-btn');

        customForm.addEventListener('submit', function (e) {
            e.preventDefault();

            const formData = new FormData(customForm);
            fetch('save_custom_jersey.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        document.getElementById('error-message').textContent = data.error;
                    } else {
                        document.getElementById('total-price').textContent = data.total_price;
                        document.getElementById('custom-id').textContent = data.custom_id;
                        document.getElementById('error-message').textContent = '';
                        editBtn.style.display = 'inline-block';
                    }
                })
                .catch(error => console.error('Error:', error));
        });

        editBtn.addEventListener('click', function () {
            const customId = document.getElementById('custom-id').textContent;
            fetch(`get_custom_jersey.php?custom_id=${customId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        document.getElementById('error-message').textContent = data.error;
                    } else {
                        document.getElementById('jersey_fabric').value = data.jersey_fabric;
                        document.getElementById('jersey_colour').value = data.jersey_colour;
                        document.getElementById('front_logo_position').value = data.front_logo_position;
                        document.getElementById('back_logo_position').value = data.back_logo_position;
                        document.getElementById('design_description').value = data.design_description;
                        document.getElementById('quantity_s').value = data.quantity_s;
                        document.getElementById('quantity_m').value = data.quantity_m;
                        document.getElementById('quantity_l').value = data.quantity_l;
                        document.getElementById('quantity_xl').value = data.quantity_xl;
                        document.getElementById('quantity_2xl').value = data.quantity_2xl;
                        document.getElementById('quantity_3xl').value = data.quantity_3xl;
                        document.getElementById('quantity_4xl').value = data.quantity_4xl;
                    }
                })
                .catch(error => console.error('Error:', error));
        });
    });


    </script>

    <style>
        /* General Styles */
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            margin: 0;
            padding: 0;
            color: #333;
        }

        #size-chart {
            width: 100%;
            margin: 20px 20px;
        }

        #size-chart table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        #size-chart th, #size-chart td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: center;
        }

        #size-chart th {
            background-color: #333;
            color: #fff;
        }

        #size-chart td {
            background-color: #f9f9f9;
        }

        /* Custom Jersey Table Styles */
        #custom-jersey h2 {
            padding: 10px;
            text-align: center;
        }

        #custom-jersey table {
            width: 100%;
            border-collapse: collapse;
        }

        #custom-jersey th, #custom-jersey td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }

        #custom-jersey th {
            background-color: #333;
            color: #fff;
        }

        #custom-jersey td {
            background-color: #f9f9f9;
        }

        #custom-jersey label {
            display: block;
            margin-bottom: 10px;
        }

        #custom-jersey input[type="text"],
        #custom-jersey input[type="number"],
        #custom-jersey input[type="file"],
        #custom-jersey select,
        #custom-jersey textarea {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        #custom-jersey button {
            padding: 10px 20px;
            border: none;
            background-color: #4caf50;
            color: white;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        #custom-jersey button:hover {
            background-color: #45a049;
        }

        /* Chat Section Styles */
        #chat-section {
            display: flex;
            height: 80vh;
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
            margin: 20px;
        }

        /* Agent List Styles */
        #agent-list {
            width: 25%;
            border-right: 1px solid #ddd;
            padding: 20px;
            background-color: #f9f9f9;
        }

        #agent-list h3 {
            font-size: 18px;
            margin-bottom: 10px;
            color: #555;
        }

        .agent {
            cursor: pointer;
            padding: 10px;
            border-bottom: 1px solid #ddd;
            transition: background-color 0.3s ease;
        }

        .agent:hover {
            background-color: #e1e1e1;
        }

        .agent.selected {
            background-color: #d1e7ff;
            font-weight: bold;
        }

        .unclickable {
            pointer-events: none;
            opacity: 0.6;
        }

        /* Chat Box Styles */
        #chat-box {
            width: 75%;
            padding: 20px;
            display: flex;
            flex-direction: column;
        }

        #messages {
            flex: 1;
            overflow-y: auto;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 10px;
            background-color: #fefefe;
            margin-bottom: 10px;
        }

        .message {
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 10px;
            background-color: #f1f1f1;
        }

        #message-form {
            display: flex;
            flex-direction: column;
        }

        #message-form textarea {
            resize: none;
            height: 100px;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 10px;
            margin-bottom: 10px;
        }

        #message-form button {
            align-self: flex-end;
            padding: 10px 20px;
            border: none;
            background-color: #4caf50;
            color: white;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        #message-form button:hover {
            background-color: #45a049;
        }

        /* Design Popup Styles */
        #design-section {
            text-align: center;
            margin-top: 20px;
        }

        #design-section h2 {
            font-size: 2.5em;
            margin-bottom: 10px;
        }

        #design-id-form {
            display: inline-block;
            text-align: left;
        }

        #design-id-form label {
            font-size: 1.2em;
            margin-right: 10px;
        }

        #design-id-form input[type="number"] {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            width: 150px;
            font-size: 1em;
        }

        #design-id-form button {
            padding: 10px 20px;
            border: none;
            background-color: #4caf50;
            color: white;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1em;
            transition: background-color 0.3s ease;
            margin-left: 10px;
        }

        #design-id-form button:hover {
            background-color: #45a049;
        }

        #design-popup {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 20px;
            border-radius: 8px;
            z-index: 20;
            width: 50%; /* Adjust the width as needed */
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            overflow-y: auto;
            max-height: 80vh; /* Ensure it doesn't overflow the viewport height */
        }

        #design-popup h3 {
            margin-bottom: 20px;
            color: #555;
            text-align: center;
        }

        #design-popup p {
            margin: 5px 0;
            word-wrap: break-word; /* Ensure long words wrap correctly */
        }

        #design-popup img {
            max-width: 100%;
            border-radius: 8px;
            margin-top: 10px;
        }

        #design-popup .close-design-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            cursor: pointer;
            color: #555;
            font-size: 1.5em;
        }

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
        /* Notification Badge Styles */
        .badge {
            background-color: red;
            color: white;
            border-radius: 50%;
            padding: 5px 10px;
            font-size: 12px;
            position: absolute;
            top: 5px;
            right: 5px;
        }
        .agent {
            position: relative;
            padding-right: 25px; /* Ensure space for the badge */
        }


        /* Complete Order Section */
        #complete-order-section {
            margin: 20px;
        }

        #order-details-container {
            margin-top: 20px;
        }

        #order-details-container table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        #order-details-container th,
        #order-details-container td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        #order-details-container th {
            background-color: #333;
            color: white;
            width: 20%;
        }

        #order-details-container img {
            max-width: auto;
            height: 200px;
        }





        #complete-order-section {
            text-align: center;
            margin: 20px;
        }

        #complete-order-section h2 {
            font-size: 2.5em;
            margin-bottom: 10px;
        }

        #complete-order-form {
            display: inline-block;
            text-align: left;
            margin-bottom: 20px;
        }

        #complete-order-form input[type="number"] {
            padding: 10px;
            margin: 5px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1em;
            width: 150px;
        }

        #complete-order-form button {
            padding: 10px 20px;
            border: none;
            background-color: #4caf50;
            color: white;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1em;
            transition: background-color 0.3s ease;
            margin-left: 10px;
        }

        #complete-order-form button:hover {
            background-color: #45a049;
        }

        #order-details-container {
            margin-top: 20px;
        }




        #checkout-section {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }

        #apply-coupon, #payment-method, #customer-info, #cart-total {
            width: 45%;
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            padding: 20px;
            border-radius: 8px;
        }

        #apply-coupon h3, #payment-method h3, #customer-info h3, #cart-total h3 {
            margin-bottom: 15px;
        }

        #apply-coupon select, #payment-method select {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        #apply-coupon button, #payment-method button {
            padding: 10px 20px;
            border: none;
            background-color: #4caf50;
            color: white;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            margin-right: 10px;
        }

        #apply-coupon button:hover, #payment-method button:hover {
            background-color: #45a049;
        }

        #customer-info table, #cart-total table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        #customer-info th, #cart-total th, #customer-info td, #cart-total td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        #customer-info th {
            background-color: #f2f2f2;
            font-weight: bold;
        }

        #cart-total th, #cart-total td {
            text-align: right;
        }

        #cart-total th {
            background-color: #f2f2f2;
            font-weight: bold;
        }

        #cart-total td {
            font-size: 1.1em;
        }

        #cart-total #total {
            font-weight: bold;
            color: #d9534f;
        }

        #proceed-to-checkout {
            padding: 10px 20px;
            border: none;
            background-color: #4caf50;
            color: white;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1.2em;
            transition: background-color 0.3s ease;
        }

        #proceed-to-checkout:hover {
            background-color: #45a049;
        }


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
