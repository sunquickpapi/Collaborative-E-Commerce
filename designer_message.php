<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'designer') {
    header("Location: login.php");
    exit();
}

$designer_id = htmlspecialchars($_SESSION['user_id']);
$designer_username = htmlspecialchars($_SESSION['user_username']);

// Database connection
include("connection.php");

// Fetch designer details
$sql_designer = "SELECT designer_username, designer_phoneNum, role FROM designer WHERE designer_id=?";
$stmt_designer = $conn->prepare($sql_designer);
$stmt_designer->bind_param("i", $designer_id);
$stmt_designer->execute();
$result_designer = $stmt_designer->get_result();
$designer = $result_designer->fetch_assoc();

$designer_username = htmlspecialchars($designer['designer_username']);
$designer_phoneNum = htmlspecialchars($designer['designer_phoneNum']);
$designer_role = htmlspecialchars($designer['role']);

// Update agent data if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $designer_username = htmlspecialchars($_POST['designer_username']);
    $designer_phoneNum = htmlspecialchars($_POST['designer_phoneNum']);
    
    $sql = "UPDATE designer SET designer_username=?, designer_phoneNum=? WHERE designer_id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $designer_username, $designer_phoneNum, $designer_id);
    
    if ($stmt->execute()) {
        echo "<script>alert('Profile updated successfully');</script>";
    } else {
        echo "<script>alert('Error updating profile');</script>";
    }
}


// Fetch all agents with unread message notification
$sql = "
    SELECT agent.agent_id, agent.agent_username, 
           MAX(CASE WHEN messages.read_status = 0 THEN 1 ELSE 0 END) AS has_unread
    FROM agent
    LEFT JOIN agent_designer_messages AS messages ON agent.agent_id = messages.sender_id AND messages.receiver_id = ? AND messages.receiver_role = 'designer'
    GROUP BY agent.agent_id, agent.agent_username";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $designer_id);
$stmt->execute();
$result = $stmt->get_result();
$agents = [];
while ($row = $result->fetch_assoc()) {
    $agents[] = $row;
}
$stmt->close();
$conn->close();

// Mark messages as read when requested
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_as_read'])) {
    $receiver_id = htmlspecialchars($_POST['receiver_id']);
    $sender_id = htmlspecialchars($_POST['sender_id']);
    $receiver_role = htmlspecialchars($_POST['receiver_role']);
    $sender_role = htmlspecialchars($_POST['sender_role']);

    include("connection.php");
    $sql = "UPDATE agent_designer_messages SET read_status = 1 
            WHERE receiver_id = ? AND sender_id = ? AND receiver_role = ? AND sender_role = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiss", $receiver_id, $sender_id, $receiver_role, $sender_role);
    $stmt->execute();
    $stmt->close();
    $conn->close();
    exit();
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Boxicons -->
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
     <!-- FontAwesome -->
     <script src="https://kit.fontawesome.com/1b07de51b4.js"></script>
    <!-- My CSS -->
    <link rel="stylesheet" href="style.css">

    <title>Designer - Agent Messages</title>
</head>
<body>

    <!-- SIDEBAR -->
    <section id="sidebar">
        <a href="#" class="brand">
            <i class='bx bxs-circle'></i>
            <span class="text">LVNADESIGNER</span>
        </a>
        <ul class="side-menu top">
            <li>
                <a href="designer.php">
                    <i class='bx bxs-shopping-bag'></i>
                    <span class="text">Dashboard</span>
                </a>
            </li>
            <li class="active">
                <a href="designer_message.php">
                    <i class='bx bxs-dashboard'></i>
                    <span class="text">Agent Message</span>
                </a>
            </li>
            <li>
                <a href="designer_draft.php">
                    <i class='bx bxs-group' ></i>
                    <span class="text">Design Draft</span>
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
            <a href="#" class="nav-link">DESIGNER</a>
            <form action="#">
                <div class="form-input">
                    <button type="submit" class="search-btn" hidden><i class='bx bxs-t-shirt'></i></button>
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
                    <h1>Agents Messages</h1>
                    <ul class="breadcrumb">
                        <li>
                            <a href="#">Designer</a>
                        </li>
                        <li><i class='bx bx-chevron-right'></i></li>
                        <li>
                            <a class="active" href="#">Agents Messages</a>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="chat-container">
                <div id="agent-list">
                    <ul>
                        <?php foreach ($agents as $agent): ?>
                            <li data-id="<?php echo $agent['agent_id']; ?>" class="<?php echo ($agent['has_unread'] > 0) ? 'new-message' : ''; ?>">
                                <?php echo htmlspecialchars($agent['agent_username']); ?>
                                <?php if ($agent['has_unread'] > 0): ?>
                                    <span class="notification">New Message</span>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div id="messages-container">
                    <div id="messages">
                        <!-- Messages will be loaded here dynamically -->
                    </div>
                    <form id="message-form">
                        <input type="hidden" id="receiver-id" name="receiver_id" value="">
                        <input type="hidden" id="receiver-role" name="receiver_role" value="agent">
                        <textarea id="message" name="message" placeholder="Type your message here"></textarea>
                        <button type="submit">Send</button>
                    </form>
                </div>
            </div>

            <div class="custom-info">
                <form id="custom-id-form">
                    <label for="custom_id">Enter Custom ID:</label>
                    <input type="text" id="custom_id" name="custom_id">
                    <button type="submit">Enter</button>
                </form>
                <table id="custom-info-table">
                    <tr>
                        <th>Jersey Fabric</th>
                        <td id="jersey_fabric"></td>
                    </tr>
                    <tr>
                        <th>Jersey Color</th>
                        <td id="jersey_color"></td>
                    </tr>
                    <tr>
                        <th>Front Logo Position</th>
                        <td id="front_logo_position"></td>
                    </tr>
                    <tr>
                        <th>Back Logo Position</th>
                        <td id="back_logo_position"></td>
                    </tr>
                    <tr>
                        <th>Design Description</th>
                        <td id="design_description" style="height: 100px;"></td>
                    </tr>
                    <tr>
                        <th>Quantity S</th>
                        <td id="quantity_s"></td>
                    </tr>
                    <tr>
                        <th>Quantity M</th>
                        <td id="quantity_m"></td>
                    </tr>
                    <tr>
                        <th>Quantity L</th>
                        <td id="quantity_l"></td>
                    </tr>
                    <tr>
                        <th>Quantity XL</th>
                        <td id="quantity_xl"></td>
                    </tr>
                    <tr>
                        <th>Quantity 2XL</th>
                        <td id="quantity_2xl"></td>
                    </tr>
                    <tr>
                        <th>Quantity 3XL</th>
                        <td id="quantity_3xl"></td>
                    </tr>
                    <tr>
                        <th>Quantity 4XL</th>
                        <td id="quantity_4xl"></td>
                    </tr>
                    <tr>
                        <th>Total Price</th>
                        <td id="total_price"></td>
                    </tr>
                    <tr>
                        <th>Front Logo</th>
                        <td>
                            <img id="front_logo_img" src="" alt="Front Logo" style="max-width: 100px; max-height: 100px;">
                            <a id="front_logo" href="" download>Download</a>
                        </td>
                    </tr>
                    <tr>
                        <th>Back Logo</th>
                        <td>
                            <img id="back_logo_img" src="" alt="Back Logo" style="max-width: 100px; max-height: 100px;">
                            <a id="back_logo" href="" download>Download</a>
                        </td>
                    </tr>
                </table>
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
            <p><strong>ID:</strong> <?php echo $designer_id; ?></p>
            <p><strong>Username:</strong> <input type="text" name="designer_username" value="<?php echo $designer_username; ?>"></p>
            <p><strong>Phone Number:</strong> <input type="text" name="designer_phoneNum" value="<?php echo $designer_phoneNum; ?>"></p>
            <p><strong>Role:</strong> <?php echo $designer_role; ?></p>
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
            const agentList = document.querySelectorAll('#agent-list li');
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
                    fetchMessages(receiverId);

                    // Mark messages as read
                    const formData = new URLSearchParams();
                    formData.append('mark_as_read', 'true');
                    formData.append('receiver_id', <?php echo $designer_id; ?>);
                    formData.append('sender_id', receiverId);
                    formData.append('receiver_role', 'designer');
                    formData.append('sender_role', 'agent');

                    fetch('designer_message.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: formData.toString()
                    }).then(() => {
                        const badge = agent.querySelector('.notification');
                        if (badge) {
                            badge.remove();
                        }
                    });
                });
            });

            document.getElementById('message-form').addEventListener('submit', (e) => {
                e.preventDefault();
                sendMessage();
            });

            function fetchMessages(receiverId) {
                fetch(`fetch_messagesAD.php?receiver_id=${receiverId}&receiver_role=agent`)
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

            document.getElementById('custom-id-form').addEventListener('submit', (e) => {
                e.preventDefault();
                const customId = document.getElementById('custom_id').value;
                fetchCustomInfo(customId);
            });

            function fetchCustomInfo(customId) {
                fetch(`fetch_custom_info.php?custom_id=${customId}`)
                    .then(response => response.json())
                    .then(data => {
                        document.getElementById('jersey_fabric').textContent = data.jersey_fabric;
                        document.getElementById('jersey_color').textContent = data.jersey_color;
                        document.getElementById('front_logo_position').textContent = data.front_logo_position;
                        document.getElementById('back_logo_position').textContent = data.back_logo_position;
                        document.getElementById('design_description').textContent = data.design_description;
                        document.getElementById('quantity_s').textContent = data.quantity_s;
                        document.getElementById('quantity_m').textContent = data.quantity_m;
                        document.getElementById('quantity_l').textContent = data.quantity_l;
                        document.getElementById('quantity_xl').textContent = data.quantity_xl;
                        document.getElementById('quantity_2xl').textContent = data.quantity_2xl;
                        document.getElementById('quantity_3xl').textContent = data.quantity_3xl;
                        document.getElementById('quantity_4xl').textContent = data.quantity_4xl;
                        document.getElementById('total_price').textContent = data.total_price;

                        const frontLogo = document.getElementById('front_logo');
                        frontLogo.href = data.front_logo;
                        frontLogo.style.display = data.front_logo ? 'block' : 'none';

                        const frontLogoImg = document.getElementById('front_logo_img');
                        frontLogoImg.src = data.front_logo;
                        frontLogoImg.style.display = data.front_logo ? 'block' : 'none';

                        const backLogo = document.getElementById('back_logo');
                        backLogo.href = data.back_logo;
                        backLogo.style.display = data.back_logo ? 'block' : 'none';

                        const backLogoImg = document.getElementById('back_logo_img');
                        backLogoImg.src = data.back_logo;
                        backLogoImg.style.display = data.back_logo ? 'block' : 'none';
                    });
            }
        });
    </script>
    <script>
       // Profile popup scripts
document.getElementById('profile-icon').addEventListener('click', function () {
    document.getElementById('overlay').style.display = 'block';
    document.getElementById('profile-popup').style.display = 'block';
});

document.querySelector('.close-btn').addEventListener('click', function () {
    document.getElementById('overlay').style.display = 'none';
    document.getElementById('profile-popup').style.display = 'none';
});

document.getElementById('overlay').addEventListener('click', function () {
    document.getElementById('overlay').style.display = 'none';
    document.getElementById('profile-popup').style.display = 'none';
});

// Handle switch mode
document.addEventListener('DOMContentLoaded', () => {
    const switchMode = document.getElementById('switch-mode');

    if (localStorage.getItem('dark-mode') === 'true') {
        document.body.classList.add('dark-mode');
        switchMode.checked = true;
    }

    switchMode.addEventListener('change', function () {
        document.body.classList.toggle('dark-mode');
        localStorage.setItem('dark-mode', document.body.classList.contains('dark-mode'));
    });

});


// Handle logout click
document.querySelector('.logout').addEventListener('click', function () {
    document.getElementById('logout-overlay').style.display = 'block';
    document.querySelector('.logout-popup').style.display = 'block';
});

// Handle logout confirmation
document.querySelector('.logout-btn').addEventListener('click', function () {
    window.location.href = 'login.php';
});

// Handle logout cancellation
document.querySelector('.cancel-btn').addEventListener('click', function () {
    document.getElementById('logout-overlay').style.display = 'none';
    document.querySelector('.logout-popup').style.display = 'none';
});

    </script>
    <script src="script.js"></script>


    <style>
        /* Common Styles */
        body {
    font-family: 'Poppins', sans-serif;
    background-color: #f8f9fa;
    color: #212529;
}

body.dark-mode {
    background-color: #343a40;
    color: #f8f9fa;
}

.sidebar, .sidebar a {
    color: #f8f9fa;
    background-color: #343a40;
}

.sidebar a.active {
    background-color: #495057;
}

.sidebar a:hover {
    background-color: #495057;
}

.navbar {
    background-color: #ffffff;
    border-bottom: 1px solid #dee2e6;
}

.navbar .profile img {
    border-radius: 50%;
}

.navbar .profile i {
    font-size: 24px;
}

.navbar .search-btn {
    background-color: transparent;
    border: none;
    color: #495057;
}

.navbar .switch-mode {
    margin-left: auto;
    margin-right: 15px;
}

.dark-mode .navbar, .dark-mode .sidebar, .dark-mode .sidebar a {
    background-color: #343a40;
    color: #f8f9fa;
}

.dark-mode .navbar .search-btn {
    color: #f8f9fa;
}

.main-content {
    padding: 20px;
}

.head-title {
    margin-bottom: 20px;
}

.breadcrumb {
    display: flex;
    align-items: center;
    list-style: none;
    padding: 0;
    margin: 0;
}

.breadcrumb li {
    margin-right: 5px;
    font-size: 14px;
}

.breadcrumb li a {
    color: #007bff;
    text-decoration: none;
}

.breadcrumb li a:hover {
    text-decoration: underline;
}

.breadcrumb li i {
    font-size: 12px;
    margin-right: 5px;
}

.chat-container {
    display: flex;
    flex-direction: row;
    height: 400px;
}

#agent-list, #designer-list {
    width: 25%;
    border-right: 1px solid #ddd;
    padding: 20px;
    overflow-y: auto;
}

#agent-list ul, #designer-list ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

#agent-list li, #designer-list li {
    padding: 10px;
    cursor: pointer;
    position: relative;
    background-color: #f8f9fa;
    margin-bottom: 10px;
    border-radius: 5px;
    transition: background-color 0.3s ease;
}

#agent-list li:hover, #designer-list li:hover {
    background-color: #e9ecef;
}

#agent-list li.unclickable, #designer-list li.unclickable {
    pointer-events: none;
    opacity: 0.6;
}

#messages-container {
    flex: 1;
    display: flex;
    flex-direction: column;
    padding: 20px;
}

#messages {
    flex: 1;
    overflow-y: auto;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 10px;
    background-color: #ffffff;
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
    background-color: #28a745;
    color: white;
    border-radius: 8px;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

#message-form button:hover {
    background-color: #218838;
}

.notification {
    background-color: #dc3545;
    color: white;
    padding: 5px;
    border-radius: 5px;
    margin-left: 10px;
    font-size: 12px;
    font-weight: bold;
}

.custom-info {
    margin-top: 20px;
}

#custom-id-form {
    display: flex;
    align-items: center;
    margin-bottom: 10px;
}

#custom-id-form input[type="text"] {
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 8px;
    margin-right: 10px;
}

#custom-id-form button {
    padding: 10px 20px;
    border: none;
    background-color: #28a745;
    color: white;
    border-radius: 8px;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

#custom-id-form button:hover {
    background-color: #218838;
}

#custom-info-table {
    width: 100%;
    border-collapse: collapse;
}

#custom-info-table th, #custom-info-table td {
    border: 1px solid #ddd;
    padding: 10px;
    text-align: left;
}

#custom-info-table th {
    background-color: #f8f9fa;
}

#custom-info-table td img {
    display: none;
    max-width: 100px;
    max-height: 100px;
}

#custom-info-table a {
    display: none;
    color: #007bff;
}

#custom-info-table a:hover {
    text-decoration: underline;
}

body.dark-mode #agent-list li, body.dark-mode #designer-list li {
    background-color: #495057;
    color: #f8f9fa;
}

body.dark-mode #agent-list li:hover, body.dark-mode #designer-list li:hover {
    background-color: #6c757d;
}

body.dark-mode #messages {
    background-color: #343a40;
}

body.dark-mode .message {
    background-color: #495057;
    color: #f8f9fa;
}

body.dark-mode #custom-info-table th {
    background-color: #495057;
    color: #f8f9fa;
}

body.dark-mode #custom-info-table td {
    background-color: #343a40;
    color: #f8f9fa;
}

body.dark-mode #message-form button {
    background-color: #17a2b8;
}

body.dark-mode #message-form button:hover {
    background-color: #138496;
}

body.dark-mode #custom-id-form button {
    background-color: #17a2b8;
}

body.dark-mode #custom-id-form button:hover {
    background-color: #138496;
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

.profile-popup form input {
    width: 100%;
    padding: 10px;
    margin: 10px 0;
    border: 1px solid #ccc;
    border-radius: 5px;
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

#profile-popup input[type="submit"] {
    padding: 10px 20px;
    border: none;
    background-color: #28a745;
    color: white;
    border-radius: 8px;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

#profile-popup input[type="submit"]:hover {
    background-color: #218838;
}

body.dark-mode .profile-popup, body.dark-mode .logout-popup {
    background-color: #495057;
    color: #f8f9fa;
}

body.dark-mode .profile-popup form input {
    background-color: #343a40;
    color: #f8f9fa;
    border: 1px solid #ccc;
}

body.dark-mode .profile-popup input[type="submit"] {
    background-color: #17a2b8;
}

body.dark-mode .profile-popup input[type="submit"]:hover {
    background-color: #138496;
}


    </style>
</body>
</html>
