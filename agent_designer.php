<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'agent') {
    header("Location: login.php");
    exit();
}

$agent_id = htmlspecialchars($_SESSION['user_id']);
$agent_username = htmlspecialchars($_SESSION['user_username']);

// Database connection
include("connection.php");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch available designers
$sql = "SELECT designer_id, designer_username, 
        (SELECT COUNT(*) FROM agent_designer_messages 
         WHERE sender_id = designer.designer_id AND receiver_id = ? AND read_status = 0) AS has_unread 
        FROM designer
        WHERE designer_status = 'Available'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $agent_id);
$stmt->execute();
$result = $stmt->get_result();
$designers = [];
while ($row = $result->fetch_assoc()) {
    $designers[] = $row;
}
$stmt->close();


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
    <title>Agent Designer Messages</title>
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
            <li>
                <a href="agent_message.php">
                    <i class='bx bxs-dashboard'></i>
                    <span class="text">Customer Message</span>
                </a>
            </li>
            <li class="active">
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
                    <h1>Designer Messages</h1>
                    <ul class="breadcrumb">
                        <li>
                            <a href="#">Agents</a>
                        </li>
                        <li><i class='bx bx-chevron-right'></i></li>
                        <li>
                            <a class="active" href="#">Designer Messages</a>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="table-data">
                <div class="order">
                    <div class="head">
                        <h3>Available Designers</h3>
                    </div>
                    <div class="chat-container">
                        <div id="designer-list">
                            <ul>
                                <?php foreach ($designers as $designer): ?>
                                    <li data-id="<?php echo $designer['designer_id']; ?>" class="<?php echo ($designer['has_unread'] > 0) ? 'new-message' : ''; ?>">
                                        <?php echo htmlspecialchars($designer['designer_username']); ?>
                                        <?php if ($designer['has_unread'] > 0): ?>
                                            <span class="notification">New Message</span>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <div id="messages">
                            <div class="message">
                                No messages yet.
                            </div>
                        </div>
                        <form id="message-form">
                            <input type="hidden" id="receiver-id" name="receiver_id" value="">
                            <input type="hidden" id="receiver-role" name="receiver_role" value="designer">
                            <textarea id="message" name="message" placeholder="Type your message here"></textarea>
                            <button type="submit">Send</button>
                        </form>
                    </div>
                </div>
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
            const designerList = document.querySelectorAll('#designer-list li');
            let selectedDesigner = null;

            designerList.forEach(designer => {
                designer.addEventListener('click', () => {
                    if (selectedDesigner === designer) return;

                    selectedDesigner = designer;
                    designerList.forEach(d => {
                        if (d !== designer) {
                            d.classList.add('unclickable');
                        } else {
                            d.classList.remove('unclickable');
                        }
                    });

                    const receiverId = designer.getAttribute('data-id');
                    document.getElementById('receiver-id').value = receiverId;
                    fetchMessages(receiverId);
                });
            });

            document.getElementById('message-form').addEventListener('submit', (e) => {
                e.preventDefault();
                sendMessage();
            });

            function fetchMessages(receiverId) {
                fetch(`fetch_messagesAD.php?receiver_id=${receiverId}&receiver_role=designer`)
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

                        // Remove "New Message" notification
                        const designerElement = document.querySelector(`#designer-list li[data-id='${receiverId}']`);
                        if (designerElement) {
                            designerElement.classList.remove('new-message');
                            const notification = designerElement.querySelector('.notification');
                            if (notification) {
                                designerElement.removeChild(notification);
                            }
                        }
                    });
            }

            function sendMessage() {
                const form = document.getElementById('message-form');
                const formData = new FormData(form);
                fetch('send_messageAD.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(result => {
                    const receiverId = document.getElementById('receiver-id').value;
                    fetchMessages(receiverId);
                });
            }
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
            document.body.classList.toggle('dark-mode');
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

    <style>
    .chat-container {
        display: flex;
        flex-direction: row;
        height: 80vh; /* Adjust the height as needed */
        background-color: #fff;
        border-radius: 10px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    }

    #designer-list {
        width: 25%;
        border-right: 1px solid #ddd;
        padding: 20px;
        overflow-y: auto;
    }

    #designer-list ul {
        list-style: none;
        padding: 0;
    }

    #designer-list li {
        padding: 10px;
        cursor: pointer;
        background-color: #f9f9f9;
        border-radius: 5px;
        margin-bottom: 10px;
        transition: all 0.3s ease;
    }

    #designer-list li:hover {
        background-color: #e0e0e0;
    }

    #designer-list li.unclickable {
        pointer-events: none;
        opacity: 0.6;
    }

    #messages {
        flex: 1;
        overflow-y: auto;
        border: 1px solid #ddd;
        border-radius: 8px;
        padding: 20px;
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
        margin-top: 10px;
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

    .notification {
        background-color: blue;
        color: white;
        padding: 5px;
        border-radius: 5px;
        margin-left: 10px;
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
        border-radius: 8px;
        cursor: pointer;
        transition: background-color 0.3s ease;
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
    body.dark-mode {
        background-color: #1c1c1c;
        color: #e0e0e0;
    }

    body.dark-mode .chat-container {
        background-color: #333;
        box-shadow: 0 0 10px rgba(255, 255, 255, 0.1);
    }

    body.dark-mode #designer-list li {
        background-color: #444;
        color: #e0e0e0;
    }

    body.dark-mode #designer-list li:hover {
        background-color: #555;
    }

    body.dark-mode #messages {
        background-color: #222;
        color: #e0e0e0;
    }

    body.dark-mode .message {
        background-color: #444;
    }

    body.dark-mode #message-form textarea {
        background-color: #444;
        color: #e0e0e0;
    }

    body.dark-mode #message-form button {
        background-color: #4caf50;
    }

    body.dark-mode .profile-popup,
    body.dark-mode .logout-popup {
        background-color: #333;
        color: #e0e0e0;
    }

    body.dark-mode .profile-popup form input {
        background-color: #444;
        color: #e0e0e0;
    }
</style>

</body>
</html>
