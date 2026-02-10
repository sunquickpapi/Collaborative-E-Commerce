<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'designer') {
    header("Location: login.php");
    exit();
}

$designer_id = htmlspecialchars($_SESSION['user_id']);

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


// Handle Add Design
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_design'])) {
    $design_title = htmlspecialchars($_POST['design_title']);
    $agent_username = htmlspecialchars($_POST['agent_username']);
    $design_description = htmlspecialchars($_POST['design_description']);

    // Handle file upload
    if (isset($_FILES['design_image']) && $_FILES['design_image']['error'] === UPLOAD_ERR_OK) {
        $target_dir = "uploads/";
        $target_file = $target_dir . basename($_FILES["design_image"]["name"]);
        move_uploaded_file($_FILES["design_image"]["tmp_name"], $target_file);
        
        $sql = "INSERT INTO designs (design_title, agent_username, design_description, design_image, created_at) VALUES (?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $design_title, $agent_username, $design_description, $target_file);
        $stmt->execute();
        $stmt->close();

        // Increment completed customs count
        $_SESSION['completed_design_count'] = isset($_SESSION['completed_design_count']) ? $_SESSION['completed_design_count'] + 1 : 1;
    }
   header("Location: designer_draft.php");
    exit();
    

    
}

// Handle Edit Design
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_design'])) {
    $design_id = htmlspecialchars($_POST['design_id']);
    $design_title = htmlspecialchars($_POST['design_title']);
    $agent_username = htmlspecialchars($_POST['agent_username']);
    $design_description = htmlspecialchars($_POST['design_description']);

    // Handle file upload if new image is provided
    if (isset($_FILES['design_image']) && $_FILES['design_image']['error'] === UPLOAD_ERR_OK) {
        $target_dir = "uploads/";
        $target_file = $target_dir . basename($_FILES["design_image"]["name"]);
        move_uploaded_file($_FILES["design_image"]["tmp_name"], $target_file);

        $sql = "UPDATE designs SET design_title = ?, agent_username = ?, design_description = ?, design_image = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssi", $design_title, $agent_username, $design_description, $target_file, $design_id);
    } else {
        $sql = "UPDATE designs SET design_title = ?, agent_username = ?, design_description = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssi", $design_title, $agent_username, $design_description, $design_id);
    }
    $stmt->execute();
    $stmt->close();

    header("Location: designer_draft.php");
    exit();
}

// Handle Delete Design
if (isset($_GET['delete_id'])) {
    $design_id = htmlspecialchars($_GET['delete_id']);
    $sql = "DELETE FROM designs WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $design_id);
    $stmt->execute();
    $stmt->close();

    $_SESSION['completed_design_count'] = isset($_SESSION['completed_design_count']) ? $_SESSION['completed_design_count'] - 1 : 1;

    header("Location: designer_draft.php");
    exit();
}

// Fetch existing designs
$sql = "SELECT * FROM designs";
$result = $conn->query($sql);
$designs = [];
while ($row = $result->fetch_assoc()) {
    $designs[] = $row;
}

// Fetch all agents
$sql = "SELECT agent_username FROM agent";
$result = $conn->query($sql);
$agents = [];
while ($row = $result->fetch_assoc()) {
    $agents[] = $row['agent_username'];
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
     <!-- FontAwesome -->
     <script src="https://kit.fontawesome.com/1b07de51b4.js"></script>
    <!-- My CSS -->
    <link rel="stylesheet" href="style.css">
    <title>Designer - Design Drafts</title>
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
            <li>
                <a href="designer_message.php">
                    <i class='bx bxs-dashboard'></i>
                    <span class="text">Agent Message</span>
                </a>
            </li>
            <li class="active">
                <a href="designer_draft.php">
                    <i class='bx bxs-group'></i>
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
                    <h1>Design Drafts</h1>
                    <ul class="breadcrumb">
                        <li>
                            <a href="#">Main</a>
                        </li>
                        <li><i class='bx bx-chevron-right'></i></li>
                        <li>
                            <a class="active" href="#">Design Drafts</a>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="designs-container">
                <!-- Existing Designs -->
                <?php foreach ($designs as $design): ?>
                    <div class="design-card" id="design-card-<?php echo $design['id']; ?>">
                        <img src="<?php echo htmlspecialchars($design['design_image']); ?>" alt="Design Image">
                        <h3><?php echo htmlspecialchars($design['design_title']); ?></h3>
                        <p>ID: <?php echo htmlspecialchars($design['id']); ?></p> <!-- Display the ID here -->
                        <p class="description"><?php echo htmlspecialchars(substr($design['design_description'], 0, 100)); ?>
                            <?php if (strlen($design['design_description']) > 100): ?>
                                <span class="read-more">...<a href="#" onclick="showFullDescription(event, <?php echo $design['id']; ?>)">Read more</a></span>
                            <?php endif; ?>
                        </p>
                        <p class="full-description" style="display: none;"><?php echo htmlspecialchars($design['design_description']); ?> <a href="#" onclick="showLessDescription(event, <?php echo $design['id']; ?>)">Show less</a></p>
                        <p>Agent: <?php echo htmlspecialchars($design['agent_username']); ?></p>
                        <button id="edit-button-<?php echo $design['id']; ?>" onclick="editDesign(<?php echo $design['id']; ?>)">Edit</button>
                        <button onclick="deleteDesign(<?php echo $design['id']; ?>)">Delete</button>

                        <!-- Edit Form -->
                        <form id="edit-form-<?php echo $design['id']; ?>" class="edit-form" style="display: none;" action="designer_draft.php" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="edit_design" value="1">
                            <input type="hidden" name="design_id" value="<?php echo $design['id']; ?>">
                            <input type="text" name="design_title" value="<?php echo htmlspecialchars($design['design_title']); ?>" required>
                            <select name="agent_username" required>
                                <?php foreach ($agents as $agent): ?>
                                    <option value="<?php echo htmlspecialchars($agent); ?>" <?php echo $design['agent_username'] == $agent ? 'selected' : ''; ?>><?php echo htmlspecialchars($agent); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <textarea name="design_description" required><?php echo htmlspecialchars($design['design_description']); ?></textarea>
                            <input type="file" name="design_image" accept="image/*">
                            <button type="submit">Save</button>
                            <button type="button" onclick="cancelEdit(<?php echo $design['id']; ?>)">Cancel</button>
                        </form>
                    </div>
                <?php endforeach; ?>

                <!-- Add New Design Card -->
                <div class="design-card add-new-design">
                    <form id="add-design-form" action="designer_draft.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="add_design" value="1">
                        <input type="text" name="design_title" placeholder="Design Title" required>
                        <select name="agent_username" required>
                            <?php foreach ($agents as $agent): ?>
                                <option value="<?php echo htmlspecialchars($agent); ?>"><?php echo htmlspecialchars($agent); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <textarea name="design_description" placeholder="Design Description" required></textarea>
                        <input type="file" name="design_image" accept="image/*" required>
                        <button type="submit">Add Design</button>
                    </form>
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


    <style>
        /* General Styling */
body {
    font-family: Arial, sans-serif;
}

/* Container for designs */
.designs-container {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
}

/* Design Card Styling */
.design-card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    width: 200px;
    text-align: center;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    position: relative;
    transition: transform 0.3s ease;
}

.design-card:hover {
    transform: translateY(-5px);
}

.design-card img {
    max-width: 100%;
    border-radius: 8px;
    margin-bottom: 10px;
}

.design-card h3 {
    margin: 10px 0;
    font-size: 18px;
}

.design-card p {
    font-size: 14px;
    color: #555;
}

.design-card .description {
    height: 60px;
    overflow: hidden;
}

.design-card .full-description {
    display: none;
    font-size: 14px;
    color: #555;
}

.design-card button {
    margin-top: 10px;
    padding: 10px;
    border: none;
    border-radius: 8px;
    background-color: #4caf50;
    color: white;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.design-card button:hover {
    background-color: #45a049;
}

/* Edit Form Styling */
.edit-form {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-top: 10px;
}

.edit-form input, .edit-form select, .edit-form textarea, .edit-form button {
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 8px;
    width: 100%;
}

.edit-form button {
    background-color: #4caf50;
    color: white;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.edit-form button:hover {
    background-color: #45a049;
}

/* Add New Design Form Styling */
.add-new-design form {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.add-new-design input, .add-new-design select, .add-new-design textarea, .add-new-design button {
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 8px;
    width: 100%;
}

.add-new-design button {
    background-color: #4caf50;
    color: white;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.add-new-design button:hover {
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

/* Dark Mode Styling */
body.dark-mode {
    background-color: #121212;
    color: #ffffff;
}

body.dark-mode .design-card {
    background: #1e1e1e;
    border-color: #333;
    color: #ddd;
}

body.dark-mode .design-card p {
    color: #bbb;
}

body.dark-mode .design-card button {
    background-color: #555;
}

body.dark-mode .design-card button:hover {
    background-color: #444;
}

body.dark-mode .add-new-design button {
    background-color: #555;
}

body.dark-mode .add-new-design button:hover {
    background-color: #444;
}

body.dark-mode .edit-form button {
    background-color: #555;
}

body.dark-mode .edit-form button:hover {
    background-color: #444;
}

body.dark-mode #profile-popup {
    background-color: #1e1e1e;
    color: #ddd;
}

body.dark-mode #logout-popup {
    background-color: #1e1e1e;
    color: #ddd;
}

body.dark-mode #overlay, body.dark-mode #logout-overlay {
    background-color: rgba(0, 0, 0, 0.7);
}

    </style>

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

        function showFullDescription(event, id) {
            event.preventDefault();
            const card = document.getElementById(`design-card-${id}`);
            card.querySelector('.description').style.display = 'none';
            card.querySelector('.full-description').style.display = 'block';
            card.style.width = '100%';
        }

        function showLessDescription(event, id) {
            event.preventDefault();
            const card = document.getElementById(`design-card-${id}`);
            card.querySelector('.full-description').style.display = 'none';
            card.querySelector('.description').style.display = 'block';
            card.style.width = '200px';
        }

        function editDesign(id) {
            const card = document.getElementById(`design-card-${id}`);
            card.querySelector('.description').style.display = 'none';
            card.querySelector('.full-description').style.display = 'none';
            card.querySelector('.edit-form').style.display = 'block';
            card.querySelector('img').style.display = 'none';
            card.querySelector('h3').style.display = 'none';
            card.querySelectorAll('button')[0].style.display = 'none'; // Hide edit button
            card.querySelectorAll('button')[1].style.display = 'none'; // Hide delete button
        }

        function cancelEdit(id) {
            const card = document.getElementById(`design-card-${id}`);
            card.querySelector('.edit-form').style.display = 'none';
            card.querySelector('img').style.display = 'block';
            card.querySelector('h3').style.display = 'block';
            card.querySelectorAll('button')[0].style.display = 'inline-block'; // Show edit button
            card.querySelectorAll('button')[1].style.display = 'inline-block'; // Show delete button
            card.style.width = '200px'; // Reset card width
        }

        function deleteDesign(id) {
            if (confirm('Are you sure you want to delete this design?')) {
                window.location.href = `designer_draft.php?delete_id=${id}`;
            }
        }
    </script>
    <script src="script.js"></script>
</body>
</html>
