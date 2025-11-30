<?php
include 'db.php';
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
// Check if user is logged in and is admin
if(!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// --- PHP HANDLERS ---

// 1. Handle New Pet (MODIFIED FOR FILE UPLOAD)
if (isset($_POST['add_pet'])) {
    $name = $_POST['name'];
    $breed = $_POST['breed'];
    $age = $_POST['age'];
    $type = $_POST['type'];

    // --- FILE UPLOAD LOGIC ---
    // The 'uploads' folder must exist in your FurFinder root directory
    $target_dir = "uploads/";
    if (!file_exists($target_dir)) { mkdir($target_dir, 0777, true); }
    
    // Check if a file was uploaded
    if (isset($_FILES["pet_photo"]) && $_FILES["pet_photo"]["error"] == 0) {
        
        $file_info = pathinfo($_FILES["pet_photo"]["name"]);
        $file_extension = $file_info['extension'];
        
        // Create a unique filename (e.g., timestamp_originalfilename.ext)
        $unique_filename = time() . "_" . $file_info['filename'] . "." . $file_extension;
        $target_file = $target_dir . $unique_filename;
        $image_url = $target_file; // This path will be stored in the DB

        // Move the uploaded file from the temporary location to the target folder
        if (move_uploaded_file($_FILES["pet_photo"]["tmp_name"], $target_file)) {
            // File moved successfully, proceed with DB insertion
            $stmt = $conn->prepare("INSERT INTO pets (name, breed, age, image_url, type) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $name, $breed, $age, $image_url, $type);
            $stmt->execute();
            echo "<script>alert('New pet added and photo uploaded!'); window.location.href='admin.php';</script>";
        } else {
            echo "<script>alert('Error uploading pet photo: Could not move file. Check file permissions on the uploads folder.'); window.location.href='admin.php';</script>";
        }
    } else {
         echo "<script>alert('Error: Please select a valid image file for the pet.'); window.location.href='admin.php';</script>";
    }
}


// 2. Handle Pet Deletion
if (isset($_POST['delete_pet'])) {
    $id = $_POST['pet_id'];
    $stmt = $conn->prepare("DELETE FROM pets WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    echo "<script>alert('Pet deleted.'); window.location.href='admin.php';</script>";
}

// 3. Handle Shelter Update
if (isset($_POST['update_shelter'])) {
    $id = $_POST['shelter_id'];
    $status = $_POST['status'];
    $email = $_POST['email'];
    $schedule = $_POST['schedule'];
    
    $stmt = $conn->prepare("UPDATE shelters SET status = ?, email = ?, schedule = ? WHERE id = ?");
    $stmt->bind_param("sssi", $status, $email, $schedule, $id);
    $stmt->execute();
    echo "<script>alert('Shelter details updated!'); window.location.href='admin.php';</script>";
}

// 4. Handle Application Status Update
if (isset($_POST['update_application'])) {
    $id = $_POST['app_id'];
    $status = $_POST['status'];
    
    $stmt = $conn->prepare("UPDATE applications SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $id);
    $stmt->execute();
    echo "<script>window.location.href='admin.php';</script>";
}

// 5. Handle Lost Pet Status Update (Mark Found)
if (isset($_POST['mark_found'])) {
    $id = $_POST['lost_pet_id'];
    // Update the status in the lost_pets table to 'Found'
    $stmt = $conn->prepare("UPDATE lost_pets SET status = 'Found' WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    echo "<script>alert('Lost pet marked as Found!'); window.location.href='admin.php';</script>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel | FurFinder</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #003366; 
            --accent-color: #d4af37; 
            --bg-light: #f4f7f6;
            --text-dark: #333;
            --white: #ffffff;
            --danger: #dc3545;
            --success: #28a745; 
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Open Sans', sans-serif; }
        body { display: flex; min-height: 100vh; background-color: var(--bg-light); color: var(--text-dark); }
        
        /* SIDEBAR */
        .sidebar {
            width: 250px;
            background-color: var(--primary-color);
            color: var(--white);
            padding: 20px 0;
            box-shadow: 2px 0 5px rgba(0,0,0,0.3);
            flex-shrink: 0;
        }
        .sidebar h2 { text-align: center; margin-bottom: 30px; color: var(--accent-color); font-size: 1.6rem; }
        .sidebar ul { list-style: none; }
        .sidebar ul li a {
            display: block;
            padding: 15px 20px;
            color: var(--white);
            text-decoration: none;
            transition: background-color 0.3s, color 0.3s;
            border-left: 5px solid transparent;
        }
        .sidebar ul li a:hover, .sidebar ul li a.active {
            background-color: rgba(255,255,255,0.1);
            border-left-color: var(--accent-color);
            color: var(--accent-color);
        }
        .sidebar .logout a { color: var(--danger); }
        .sidebar .logout a:hover { background-color: rgba(220, 53, 69, 0.2); border-left-color: var(--danger); }

        /* CONTENT AREA */
        .content {
            flex-grow: 1;
            padding: 40px;
            overflow-y: auto;
        }
        .section {
            background: var(--white);
            padding: 30px;
            border-radius: 8px;
            margin-bottom: 30px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1); 
            display: none; 
        }
        .section.active { display: block; }
        .section h3 { margin-bottom: 20px; color: var(--primary-color); border-bottom: 2px solid #eee; padding-bottom: 10px; }
        
        /* FORMS */
        .form-row { 
            display: flex; 
            gap: 15px; 
            margin-bottom: 15px; 
            align-items: center; 
        }
        .form-row input, .form-row select { padding: 10px; border: 1px solid #ccc; border-radius: 4px; flex-grow: 1; }
        .form-row input[type="file"] { 
            padding: 8px; 
            flex-grow: 1.5; 
        }
        .btn-add { background: var(--success); color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; }
        .btn-delete { background: var(--danger); color: white; border: none; padding: 8px 12px; border-radius: 4px; cursor: pointer; font-size: 0.9rem; }
        .btn-save { background: var(--primary-color); color: white; border: none; padding: 8px 12px; border-radius: 4px; cursor: pointer; font-size: 0.9rem; }
        
        /* TABLES */
        .data-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .data-table th, .data-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .data-table th {
            background-color: var(--primary-color);
            color: var(--white);
            font-weight: 600;
            text-transform: uppercase;
        }
        .data-table tr:nth-child(even) { background-color: #f9f9f9; }
        .data-table tr:hover { background-color: #f1f1f1; }

        .app-status select, .shelter-update select, .shelter-update input { padding: 5px; border-radius: 4px; }
        .app-status { display: flex; gap: 5px; }

        /* Specific Shelter Table Inputs */
        .shelter-update input { width: 100px; }
    </style>
</head>
<body>

    <div class="sidebar">
        <h2>Admin Panel</h2>
        <ul>
            <li><a href="#" onclick="showSection('manage-pets', this)" class="active"><i class="fas fa-dog"></i> Manage Pets</a></li>
            <li><a href="#" onclick="showSection('applications', this)"><i class="fas fa-file-alt"></i> Applications</a></li>
            <li><a href="#" onclick="showSection('lost-found', this)"><i class="fas fa-search-location"></i> Lost & Found (User Posts)</a></li>
            <li><a href="#" onclick="showSection('shelter-status', this)"><i class="fas fa-home"></i> Shelter Status</a></li>
            <li><a href="#" onclick="showSection('donations', this)"><i class="fas fa-hand-holding-usd"></i> Donations</a></li>
            <li class="logout"><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <div class="content">
        <h1>Welcome, Admin!</h1>

        <div id="manage-pets" class="section active">
            <h3>Add New Adoptable Pet</h3>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-row">
                    <input type="text" name="name" placeholder="Name" required>
                    <input type="text" name="breed" placeholder="Breed" required>
                    <input type="text" name="age" placeholder="Age (e.g. 1 year)" required>
                    <input type="file" name="pet_photo" accept="image/*" required>
                    <select name="type">
                        <option value="dog">Dog</option>
                        <option value="cat">Cat</option>
                    </select>
                    <button type="submit" name="add_pet" class="btn-add">Add</button>
                </div>
            </form>

            <h3 style="margin-top:40px;">Current Adoptable Pets</h3>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Photo</th> 
                        <th>Name</th>
                        <th>Breed</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $pets = $conn->query("SELECT * FROM pets ORDER BY id DESC");
                    while($row = $pets->fetch_assoc()):
                    ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td><img src="<?php echo $row['image_url']; ?>" alt="Pet Photo" style="width: 50px; height: 50px; object-fit: cover;"></td>
                            <td><?php echo $row['name']; ?></td>
                            <td><?php echo $row['breed']; ?></td>
                            <td><?php echo ucfirst($row['type']); ?></td>
                            <td><?php echo $row['status']; ?></td>
                            <td>
                                <form method="POST" onsubmit="return confirm('Are you sure you want to delete this pet?');" style="display:inline;">
                                    <input type="hidden" name="pet_id" value="<?php echo $row['id']; ?>">
                                    <button type="submit" name="delete_pet" class="btn-delete">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <div id="applications" class="section">
            <h3>Adoption Applications</h3>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Pet</th>
                        <th>Applicant</th>
                        <th>Contact</th>
                        <th>Address</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $apps = $conn->query("SELECT * FROM applications ORDER BY id DESC");
                    while($row = $apps->fetch_assoc()):
                    ?>
                        <tr>
                            <td><?php echo $row['pet_name']; ?></td>
                            <td><?php echo $row['fullname']; ?></td>
                            <td><?php echo $row['contact']; ?></td>
                            <td><?php echo $row['address']; ?></td>
                            <td>
                                <form method="POST" class="app-status">
                                    <input type="hidden" name="app_id" value="<?php echo $row['id']; ?>">
                                    <select name="status">
                                        <option value="Pending" <?php if($row['status'] == 'Pending') echo 'selected'; ?>>Pending</option>
                                        <option value="Approved" <?php if($row['status'] == 'Approved') echo 'selected'; ?>>Approved</option>
                                        <option value="Rejected" <?php if($row['status'] == 'Rejected') echo 'selected'; ?>>Rejected</option>
                                    </select>
                                    <button type="submit" name="update_application" class="btn-save">Save</button>
                                </form>
                            </td>
                            <td>
                                <button class="btn-delete" disabled>Archive</button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <div id="lost-found" class="section">
            <h3>User Submitted Lost Pets</h3>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Pet Name</th>
                        <th>Location</th>
                        <th>Contact</th>
                        <th>Details</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $lost = $conn->query("SELECT * FROM lost_pets ORDER BY id DESC");
                    while($row = $lost->fetch_assoc()):
                    ?>
                        <tr>
                            <td><?php echo $row['pet_name']; ?></td>
                            <td><?php echo $row['location']; ?></td>
                            <td><?php echo $row['contact_number']; ?></td>
                            <td><a href="<?php echo $row['photo_path']; ?>" target="_blank">View Photo</a> | <?php echo substr($row['description'], 0, 30); ?>...</td>
                            <td><?php echo $row['status']; ?></td>
                            <td><form method="POST"><input type="hidden" name="lost_pet_id" value="<?php echo $row['id']; ?>"><button type="submit" name="mark_found" class="btn-delete" style="background-color: var(--danger);">Mark Found</button></form> </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <div id="shelter-status" class="section">
            <h3>Update Shelter Details</h3>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Shelter</th>
                        <th>Email</th>
                        <th>Schedule</th>
                        <th>Current Status</th>
                        <th>Update</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $shelters = $conn->query("SELECT * FROM shelters");
                    while($row = $shelters->fetch_assoc()):
                    ?>
                        <tr>
                            <td><?php echo $row['name']; ?></td>
                            <form method="POST" class="shelter-update">
                                <input type="hidden" name="shelter_id" value="<?php echo $row['id']; ?>">
                                
                                <td><input type="email" name="email" value="<?php echo $row['email']; ?>" required></td>
                                <td><input type="text" name="schedule" value="<?php echo $row['schedule']; ?>" required></td>
                                
                                <td><?php echo $row['status']; ?></td>
                                <td>
                                    <select name="status">
                                        <option value="Open" <?php if($row['status'] == 'Open') echo 'selected'; ?>>Open</option>
                                        <option value="Full" <?php if($row['status'] == 'Full') echo 'selected'; ?>>Full</option>
                                    </select>
                                    <button type="submit" name="update_shelter" class="btn-save">Save</button>
                                </td>
                            </form>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        
        <div id="donations" class="section">
            <h3>Recent Donations</h3>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Donor Name</th>
                        <th>Amount (PHP)</th>
                        <th>Message</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $donations = $conn->query("SELECT * FROM donations ORDER BY date_created DESC");
                    while($row = $donations->fetch_assoc()):
                    ?>
                        <tr>
                            <td><?php echo date('M d, Y', strtotime($row['date_created'])); ?></td>
                            <td><?php echo $row['donor_name']; ?></td>
                            <td style="font-weight:bold; color:var(--success);"><?php echo number_format($row['amount'], 2); ?></td>
                            <td><?php echo htmlspecialchars($row['message']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

    </div>

    <script>
        function showSection(sectionId, element) {
            // Hide all sections
            document.querySelectorAll('.section').forEach(section => {
                section.classList.remove('active');
            });
            // Deactivate all sidebar links
            document.querySelectorAll('.sidebar ul li a').forEach(link => {
                link.classList.remove('active');
            });

            // Show selected section
            document.getElementById(sectionId).classList.add('active');
            // Activate selected link
            element.classList.add('active');
        }

        // Initialize to ensure the correct section is active on load
        document.addEventListener('DOMContentLoaded', () => {
             // Default to 'manage-pets' on page load
             document.getElementById('manage-pets').classList.add('active');
             document.querySelector('.sidebar ul li a').classList.add('active');
        });
    </script>
</body>
</html>