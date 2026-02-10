<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'agent') {
    header("Location: login.php");
    exit();
}

$agent_id = htmlspecialchars($_SESSION['user_id']);

// Database connection
include("connection.php");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch all customers with unread messages
$customer_sql = "
    SELECT customers.customer_id, customers.c_username, 
           MAX(CASE WHEN messages.read_status = 0 THEN 1 ELSE 0 END) AS has_unread
    FROM customers
    LEFT JOIN messages ON customers.customer_id = messages.sender_id AND messages.receiver_id = ? AND messages.receiver_role = 'agent'
    GROUP BY customers.customer_id, customers.c_username";
$stmt = $conn->prepare($customer_sql);
$stmt->bind_param('i', $agent_id);
$stmt->execute();
$customer_result = $stmt->get_result();
$customers = [];
while ($customer_row = $customer_result->fetch_assoc()) {
    $customers[] = $customer_row;
}
$stmt->close();

// Function to mark messages as read
function markMessagesAsRead($conn, $receiver_id, $sender_id) {
    $sql = "UPDATE messages SET read_status = 1 WHERE receiver_id = ? AND sender_id = ? AND receiver_role = 'agent'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $receiver_id, $sender_id);
    $stmt->execute();
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_as_read'])) {
    $receiver_id = $_POST['receiver_id'];
    $sender_id = $_POST['sender_id'];
    markMessagesAsRead($conn, $receiver_id, $sender_id);
    exit();
}

// Handle complete custom
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_custom'])) {
    $custom_id = $_POST['custom_id'];
    $design_id = $_POST['design_id'];

    // Fetch custom details
    $sql_custom = "SELECT customer_id, total_price FROM custom WHERE custom_id = ?";
    $stmt_custom = $conn->prepare($sql_custom);
    $stmt_custom->bind_param('i', $custom_id);
    $stmt_custom->execute();
    $result_custom = $stmt_custom->get_result();
    $custom = $result_custom->fetch_assoc();
    $stmt_custom->close();

    if ($custom) {
        $customer_id = $custom['customer_id'];
        $total_price = $custom['total_price'];

        // Fetch customer name
        $sql_customer = "SELECT c_username FROM customers WHERE customer_id = ?";
        $stmt_customer = $conn->prepare($sql_customer);
        $stmt_customer->bind_param('i', $customer_id);
        $stmt_customer->execute();
        $result_customer = $stmt_customer->get_result();
        $customer = $result_customer->fetch_assoc();
        $stmt_customer->close();

        if ($customer) {
            $customer_name = $customer['c_username'];

            // Fetch design image
            $sql_design = "SELECT design_image FROM designs WHERE id = ?";
            $stmt_design = $conn->prepare($sql_design);
            $stmt_design->bind_param('i', $design_id);
            $stmt_design->execute();
            $result_design = $stmt_design->get_result();
            $design = $result_design->fetch_assoc();
            $stmt_design->close();

            if ($design) {
                $design_image = $design['design_image'];

                // Insert into session data for display
                $_SESSION['completed_customs'][] = [
                    'customer_name' => $customer_name,
                    'total_price' => $total_price,
                    'design_image' => $design_image
                ];

                // Increment completed customs count
                $_SESSION['completed_customs_count'] = isset($_SESSION['completed_customs_count']) ? $_SESSION['completed_customs_count'] + 1 : 1;

                echo "Custom order completed successfully!";
                exit();
            }
        }
    }

    echo "Error completing custom order.";
    exit();
}

// Fetch agent details
$sql_agent = "SELECT agent_username, agent_phoneNum, role FROM agent WHERE agent_id=?";
$stmt_agent = $conn->prepare($sql_agent);
$stmt_agent->bind_param("i", $agent_id);
$stmt_agent->execute();
$result_agent = $stmt_agent->get_result();
$agent = $result_agent->fetch_assoc();

$agent_username = htmlspecialchars($agent['agent_username']);
$agent_phoneNum = htmlspecialchars($agent['agent_phoneNum']);
$agent_role = htmlspecialchars($agent['role']);

// Update agent data if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $agent_username = htmlspecialchars($_POST['agent_username']);
    $agent_phoneNum = htmlspecialchars($_POST['agent_phoneNum']);
    
    $sql = "UPDATE agent SET agent_username=?, agent_phoneNum=? WHERE agent_id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $agent_username, $agent_phoneNum, $agent_id);
    
    if ($stmt->execute()) {
        echo "<script>alert('Profile updated successfully');</script>";
    } else {
        echo "<script>alert('Error updating profile');</script>";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Boxicons -->
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <!-- Font Awesome for Profile Icon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- My CSS -->
    <link rel="stylesheet" href="style.css">
    <title>Agent Message</title>
    <style>
        /* Basic Layout */
        .chat-container {
            display: flex;
            flex-direction: column;
            height: calc(100vh - 60px); /* Adjust the height */
        }

        #customer-list {
            display: flex;
            flex-direction: column;
            border: 1px solid #ddd;
            padding: 10px;
            margin-bottom: 10px;
            height: 25%; /* Adjust the height */
            overflow-y: auto;
            background-color: #f9f9f9;
            border-radius: 10px;
        }

        .customer {
            padding: 10px;
            border-bottom: 1px solid #ddd;
            cursor: pointer;
            position: relative;
            transition: background-color 0.3s;
        }

        .customer:hover {
            background-color: #e9e9e9;
        }

        .badge {
            position: absolute;
            top: 50%;
            right: 10px;
            transform: translateY(-50%);
            background-color: red;
            color: white;
            border-radius: 50%;
            padding: 5px 10px;
        }

        #messages {
            flex: 1;
            overflow-y: auto;
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 10px;
            background-color: #fff;
        }

        .message {
            padding: 10px;
            border-radius: 10px;
            margin-bottom: 10px;
            background-color: #f1f1f1;
            transition: background-color 0.3s;
        }

        #message-form {
            display: flex;
            flex-direction: column;
            margin-top: 10px;
        }

        #message-form textarea {
            resize: none;
            height: 100px;
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 10px;
            margin-bottom: 10px;
            background-color: #f9f9f9;
        }

        #message-form button {
            align-self: flex-end;
            padding: 10px 20px;
            border: none;
            background-color: #4caf50;
            color: white;
            border-radius: 10px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        #message-form button:hover {
            background-color: #45a049;
        }

        /* Complete Custom Section */
        .complete-custom {
        margin-top: 20px;
        background-color: #fff;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    }

    .complete-custom h3 {
        margin-bottom: 20px;
        font-size: 24px;
    }

    .complete-custom input {
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 5px;
        margin-right: 10px;
        width: 200px;
        background-color: #f9f9f9;
    }

    .complete-custom button {
        padding: 10px 20px;
        border: none;
        background-color: #4caf50;
        color: white;
        border-radius: 5px;
        cursor: pointer;
        transition: background-color 0.3s ease;
    }

    .complete-custom button:hover {
        background-color: #45a049;
    }

    
    body.dark .complete-custom {
        background-color: #333;
        box-shadow: 0 0 10px rgba(255, 255, 255, 0.1);
    }

    body.dark .complete-custom h3 {
        color: #fff;
    }

    body.dark #message-form textArea {
        background-color: #555;
        border: 1px solid #444;
        color: #fff;
    }

    body.dark .complete-custom input {
        background-color: #555;
        border: 1px solid #444;
        color: #fff;
    }

    body.dark .complete-custom button {
        background-color: #4caf50;
    }

    body.dark .complete-custom button:hover {
        background-color: #45a049;
    }


        /* Profile Popup */
        #overlay, #logout-overlay {
            position: fixed;
            display: none;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 2;
        }

        .profile-popup, .logout-popup {
            display: none;
            position: fixed;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
            padding: 20px;
            z-index: 3;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 300px;
            text-align: left;
        }

        .logout-popup h2 {
            font-size: 20px;
        }

        .close-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 20px;
            cursor: pointer;
        }

        #profile-popup input[type="submit"] {
            padding: 10px 20px;
            border: none;
            background-color: #4caf50;
            color: white;
            border-radius: 10px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .logout-btn {
            background-color: red;
            color: white;
            padding: 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-right: 10px;
        }

        .cancel-btn {
            background-color: gray;
            color: white;
            padding: 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .profile-popup form input {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        /* Dark mode styles */
        body.dark {
            background-color: #121212;
            color: #e0e0e0;
        }

        body.dark #customer-list,
        body.dark #messages,
        body.dark #message-form textarea,
        body.dark .complete-custom input,
        body.dark .profile-popup,
        body.dark .logout-popup {
            background-color: #333;
            border-color: #555;
        }

        body.dark .customer {
            border-bottom-color: #555;
        }

        body.dark .customer:hover {
            background-color: #444;
        }

        body.dark .message {
            background-color: #444;
        }

        body.dark #message-form button,
        body.dark .complete-custom button,
        body.dark #profile-popup input[type="submit"],
        body.dark .logout-btn,
        body.dark .cancel-btn {
            background-color: #4caf50;
        }

        body.dark #message-form button:hover,
        body.dark .complete-custom button:hover,
        body.dark #profile-popup input[type="submit"]:hover,
        body.dark .logout-btn:hover,
        body.dark .cancel-btn:hover {
            background-color: #45a049;
        }

        body.dark .logout-btn {
            background-color: red;
        }

        body.dark .cancel-btn {
            background-color: gray;
        }
    </style>
</head>
<body>
    <!-- SIDEBAR -->
    <section id="sidebar">
        <a href="#" class="brand">
            <i class='bx bxs-circle'></i>
            <span class="text">LVNAAGENT</span>
        </a>
        <ul class="side-menu top">
            <li>
                <a href="agent.php">
                    <i class='bx bxs-shopping-bag'></i>
                    <span class="text">Dashboard</span>
                </a>
            </li>
            <li class="active">
                <a href="agent_message.php">
                    <i class='bx bxs-dashboard'></i>
                    <span class="text">Customer Message</span>
                </a>
            </li>
            <li>
                <a href="agent_designer.php">
                    <i class='bx bxs-group'></i>
                    <span class="text">Designer Message</span>
                </a>
            </li>
        </ul>
        <ul class="side-menu">
            <li>
                <a href="#" class="logout">
                    <i class='bx bxs-log-out-circle'></i>
                    <span class="text">Logout</span>
                </a>
            </li>
        </ul>
    </section>
    <!-- SIDEBAR -->

    <!-- CONTENT -->
    <section id="content">
        <!-- NAVBAR -->
        <nav>
            <i class='bx bx-menu'></i>
            <a href="#" class="nav-link">AGENT</a>
            <form action="#">
            <div class="form-input">
                    <button type="submit" class="search-btn" hidden><i class='bx bx-briefcase-alt'></i></button>
                </div>
            </form>
                <input type="checkbox" id="switch-mode" hidden>
                <label for="switch-mode" class="switch-mode"></label>
            <div class="profile">
                <i class="fa-regular fa-address-card" id="profile-icon"></i>
            </div>
        </nav>
        <!-- NAVBAR -->

        <!-- MAIN -->
        <main>
            <div class="head-title">
                <div class="left">
                    <h1>Customer Messages</h1>
                    <ul class="breadcrumb">
                        <li>
                            <a href="#">Agents</a>
                        </li>
                        <li><i class='bx bx-chevron-right'></i></li>
                        <li>
                            <a class="active" href="#">Customer Messages</a>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="chat-container">
                <div id="customer-list">
                    <?php foreach ($customers as $customer) : ?>
                        <div class="customer" data-id="<?php echo htmlspecialchars($customer['customer_id']); ?>">
                            <?php echo htmlspecialchars($customer['c_username']); ?>
                            <?php if ($customer['has_unread'] > 0) : ?>
                                <span class="badge">New Message</span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div id="messages">
                    <!-- Messages will be loaded here -->
                </div>
                <form id="message-form">
                    <input type="hidden" id="receiver-id" name="receiver_id" value="">
                    <input type="hidden" id="receiver-role" name="receiver_role" value="customer">
                    <textarea id="message" name="message" placeholder="Type your message here"></textarea>
                    <button type="submit">Send</button>
                </form>
            </div>

            <div class="complete-custom">
                <h3>Complete Custom</h3>
                <form id="complete-custom-form" method="POST" action="agent_message.php">
                    <input type="hidden" name="complete_custom" value="1">
                    <input type="number" id="custom_id" name="custom_id" placeholder="Enter Custom ID" required>
                    <input type="number" id="design_id" name="design_id" placeholder="Enter Design ID" required>
                    <button type="submit">Submit</button>
                </form>
            </div>
        </main>
        <!-- MAIN -->
    </section>
    <!-- CONTENT -->

    <!-- Profile Popup -->
    <div id="overlay"></div>
    <div id="profile-popup" class="profile-popup">
        <span class="close-btn">&times;</span>
        <form method="POST">
            <p><strong>ID:</strong> <?php echo $agent_id; ?></p>
            <p><strong>Username:</strong> <input type="text" name="agent_username" value="<?php echo $agent_username; ?>"></p>
            <p><strong>Phone Number:</strong> <input type="text" name="agent_phoneNum" value="<?php echo $agent_phoneNum; ?>"></p>
            <p><strong>Role:</strong> <?php echo $agent_role; ?></p>
            <button type="submit" name="update_profile">Save Changes</button>
        </form>
    </div>

    <!-- Logout Popup -->
    <div id="logout-overlay"></div>
    <div class="logout-popup">
        <h2>Confirm Logout</h2>
        <button class="logout-btn">Logout</button>
        <button class="cancel-btn">Cancel</button>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const agentId = <?php echo $agent_id; ?>;
            const receiverIdInput = document.getElementById('receiver-id');
            const receiverRoleInput = document.getElementById('receiver-role');
            const messageForm = document.getElementById('message-form');
            let selectedCustomerId = null;

            function fetchMessages() {
                if (!selectedCustomerId) return;
                fetch(`fetch_messages.php?receiver_id=${selectedCustomerId}&receiver_role=customer`)
                    .then(response => response.json())
                    .then(messages => {
                        console.log('Messages fetched:', messages); // Debugging output
                        const messageBox = document.getElementById('messages');
                        messageBox.innerHTML = '';
                        messages.forEach(msg => {
                            const messageElement = document.createElement('div');
                            messageElement.className = 'message';
                            messageElement.innerHTML = `<strong>${msg.sender_name}:</strong> ${msg.message}`;
                            messageBox.appendChild(messageElement);
                        });
                    })
                    .catch(error => console.error('Error fetching messages:', error));
            }

            document.querySelectorAll('.customer').forEach(customer => {
                customer.addEventListener('click', () => {
                    selectedCustomerId = customer.dataset.id;
                    receiverIdInput.value = selectedCustomerId;
                    fetchMessages();

                    // Mark messages as read
                    fetch('agent_message.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `mark_as_read=true&receiver_id=${agentId}&sender_id=${selectedCustomerId}`
                    }).then(() => {
                        const badge = customer.querySelector('.badge');
                        if (badge) {
                            badge.remove();
                        }
                    });
                });
            });

            messageForm.addEventListener('submit', (e) => {
                e.preventDefault();
                sendMessage();
            });

            function sendMessage() {
                const formData = new FormData(messageForm);
                fetch('send_messageA.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(result => {
                    console.log('Message sent result:', result); // Debugging output
                    fetchMessages();
                })
                .catch(error => console.error('Error sending message:', error));
            }

            completeCustomForm.addEventListener('submit', (e) => {
                e.preventDefault();
                const formData = new FormData(completeCustomForm);
                fetch('agent_message.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(result => {
                    console.log('Complete custom result:', result); // Debugging output
                    alert(result);
                })
                .catch(error => console.error('Error completing custom:', error));
            });

            // Initial fetch
            fetchMessages();

        });
    </script>
    <script>
        // Handle profile icon click
        document.getElementById('profile-icon').addEventListener('click', function() {
            document.getElementById('overlay').style.display = 'block';
            document.querySelector('.profile-popup').style.display = 'block';
        });

        // Handle close button click
        document.querySelector('.close-btn').addEventListener('click', function() {
            document.getElementById('overlay').style.display = 'none';
            document.querySelector('.profile-popup').style.display = 'none';
        });

        // Handle switch mode
        document.getElementById('switch-mode').addEventListener('change', function() {
            document.body.classList.toggle('dark');
        });

        // Handle logout click
        document.querySelector('.logout').addEventListener('click', function() {
            document.getElementById('logout-overlay').style.display = 'block';
            document.querySelector('.logout-popup').style.display = 'block';
        });

        // Handle logout confirmation
        document.querySelector('.logout-btn').addEventListener('click', function() {
            window.location.href = 'login.php';
        });

        // Handle logout cancellation
        document.querySelector('.cancel-btn').addEventListener('click', function() {
            document.getElementById('logout-overlay').style.display = 'none';
            document.querySelector('.logout-popup').style.display = 'none';
        });

    </script>
    <script src="script.js"></script>
</body>
</html>
