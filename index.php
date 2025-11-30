<?php
include 'db.php';

// --- 1. HANDLE LOST PET SUBMISSION ---
if (isset($_POST['submit_lost_report'])) {
    $name = $_POST['lf_name'];
    $loc = $_POST['lf_location'];
    $time = $_POST['lf_time'];
    $contact = $_POST['lf_contact'];
    $desc = $_POST['lf_desc'];
    
    $target_dir = "uploads/";
    if (!file_exists($target_dir)) { mkdir($target_dir, 0777, true); }
    
    $file_name = basename($_FILES["lf_photo"]["name"]);
    $target_file = $target_dir . time() . "_" . $file_name;
    
    if (move_uploaded_file($_FILES["lf_photo"]["tmp_name"], $target_file)) {
        // Safe Insert
        $stmt = $conn->prepare("INSERT INTO lost_pets (pet_name, location, last_seen, contact_number, description, photo_path) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $name, $loc, $time, $contact, $desc, $target_file);
        $stmt->execute();
        echo "<script>alert('Alert Posted Successfully!');</script>";
    } else {
        echo "<script>alert('Error uploading photo.');</script>";
    }
}

// --- 2. HANDLE ADOPTION ---
if (isset($_POST['submit_application'])) {
    if(!isset($_SESSION['user_id'])){
        echo "<script>alert('Please login to adopt.'); window.location.href='login.php';</script>";
    } else {
        $uid = $_SESSION['user_id'];
        $pname = $_POST['app_pet_name'];
        $fname = $_POST['app_fullname'];
        $addr = $_POST['app_address'];
        $cont = $_POST['app_contact'];
        
        $stmt = $conn->prepare("INSERT INTO applications (user_id, pet_name, fullname, address, contact) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $uid, $pname, $fname, $addr, $cont);
        $stmt->execute();
        echo "<script>alert('Application Submitted!');</script>";
    }
}

// --- 3. HANDLE DONATION ---
if (isset($_POST['submit_donation'])) {
    $dname = $_POST['donor_name'];
    $damt = $_POST['donor_amount'];
    $dmsg = $_POST['donor_message'];
    
    $stmt = $conn->prepare("INSERT INTO donations (donor_name, amount, message) VALUES (?, ?, ?)");
    $stmt->bind_param("sds", $dname, $damt, $dmsg);
    $stmt->execute();
    $showQR = true; 
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FurFinder | Baguio City</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    
    <style>
        /* CSS RESET & VARS */
        :root {
            --primary-color: #003366; 
            --accent-color: #d4af37; 
            --bg-light: #f4f7f6;
            --text-dark: #333;
            --white: #ffffff;
            --success: #28a745;
            --danger: #dc3545;
            --transition: all 0.3s ease;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Open Sans', sans-serif; }
        body { background-color: var(--bg-light); color: var(--text-dark); line-height: 1.6; padding-bottom: 50px; }

        /* NAV */
        nav { background-color: var(--primary-color); color: var(--white); padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 1000; box-shadow: 0 2px 5px rgba(0,0,0,0.2); }
        .logo { display: flex; align-items: center; font-size: 1.5rem; font-weight: 700; gap: 10px; }
        .logo i { color: var(--accent-color); }
        .nav-links { list-style: none; display: flex; gap: 20px; align-items: center; }
        .nav-links a { color: white; text-decoration: none; font-weight: 600; padding: 8px 12px; border-radius: 4px; transition: var(--transition); cursor: pointer; }
        .nav-links a:hover, .nav-links a.active { background-color: rgba(255,255,255,0.15); color: var(--accent-color); }
        .auth-btn { background: var(--accent-color); color: var(--primary-color) !important; padding: 8px 20px !important; border-radius: 20px; }

        /* LAYOUT */
        .container { max-width: 1100px; margin: 2rem auto; padding: 0 20px; display: none; }
        .container.active { display: block; animation: fadeIn 0.5s ease-out forwards; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        h1, h2, h3 { color: var(--primary-color); margin-bottom: 1rem; }
        .section-header { text-align: center; margin-bottom: 2rem; padding-bottom: 1rem; border-bottom: 2px solid #ddd; }

        /* HERO */
        .hero { background: linear-gradient(rgba(0,51,102,0.7), rgba(0,51,102,0.7)), url('https://images.unsplash.com/photo-1548199973-03cce0bbc87b?auto=format&fit=crop&w=1000&q=80'); background-size: cover; background-position: center; padding: 4rem 2rem; border-radius: 8px; text-align: center; margin-bottom: 2rem; color: white; }
        .hero h1 { color: white; font-size: 2.5rem; text-shadow: 2px 2px 4px rgba(0,0,0,0.6); }
        .hero p { color: #f1f1f1; font-size: 1.1rem; }

        /* CONTENT */
        .content-box { background: white; padding: 2rem; border-radius: 8px; margin-bottom: 2rem; box-shadow: 0 3px 6px rgba(0,0,0,0.1); }
        .ordinance-box { border-left: 5px solid var(--accent-color); }
        .req-list li { margin-bottom: 15px; list-style: none; padding-left: 1.5rem; position: relative; }
        .req-list li::before { content: "\f00c"; font-family: "Font Awesome 6 Free"; font-weight: 900; color: var(--success); position: absolute; left: 0; top: 3px; }

        /* PET GRID */
        .pet-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; }
        .pet-card { background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 3px 6px rgba(0,0,0,0.1); transition: var(--transition); }
        .pet-card:hover { transform: translateY(-5px); }
        .pet-img { height: 200px; background: #eee; display: flex; align-items: center; justify-content: center; }
        .pet-img img { width: 100%; height: 100%; object-fit: cover; }
        .pet-details { padding: 1.5rem; }

        /* LOST & FOUND */
        .lf-layout { display: grid; grid-template-columns: 1fr 2fr; gap: 2rem; }
        .report-form { background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 3px 6px rgba(0,0,0,0.1); }
        .missing-feed { display: flex; flex-direction: column; gap: 15px; }
        .missing-card { background: white; padding: 1rem; border-radius: 8px; border-left: 5px solid var(--danger); display: flex; gap: 15px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .missing-img-container { width: 100px; height: 100px; background: #eee; border-radius: 8px; flex-shrink: 0; overflow: hidden; }
        .missing-img-container img { width: 100%; height: 100%; object-fit: cover; }

        /* SHELTERS */
        .shelter-card { background: white; border-radius: 8px; padding: 2rem; margin-bottom: 2rem; display: flex; gap: 2rem; align-items: flex-start; box-shadow: 0 3px 6px rgba(0,0,0,0.1); }
        .shelter-logo img { width: 100px; height: 100px; object-fit: cover; border-radius: 50%; /* Makes the image circular */ border: 3px solid var(--primary-color); /* Adds the border */ padding: 2px; /* Small padding between image and border */ }
        .status-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-weight: bold; font-size: 0.85rem; margin-left: 10px; }
        .status-open { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .status-full { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        /* FORMS & MODALS */
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; }
        .form-group input, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; }
        .btn-primary { display: block; width: 100%; padding: 12px; background-color: var(--primary-color); color: white; border: none; border-radius: 4px; margin-top: 1rem; cursor: pointer; font-weight: 600; }
        .btn-primary:hover { background-color: var(--accent-color); color: var(--primary-color); }
        .modal { display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.6); align-items: center; justify-content: center; backdrop-filter: blur(3px); }
        .modal-content { background-color: #fefefe; padding: 2rem; border-radius: 8px; width: 90%; max-width: 500px; position: relative; animation: fadeIn 0.3s; }
        .close-modal { float: right; font-size: 28px; cursor: pointer; color: #aaa; }

        .ordinance-box {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            border-left: 5px solid var(--accent-color);
        }

        @media (max-width: 768px) {
            .lf-layout { grid-template-columns: 1fr; }
            .shelter-card { flex-direction: column; align-items: center; text-align: center; }
            .nav-links { gap: 10px; font-size: 0.9rem; }
        }
    </style>
</head>
<body>

    <nav>
        <div class="logo"><i class="fas fa-paw"></i> FurFinder</div>
        <ul class="nav-links">
            <li><a onclick="showPage('home')" id="nav-home" class="active">Home</a></li>
            <li><a onclick="showPage('adopt')" id="nav-adopt">Adopt</a></li>
            <li><a onclick="showPage('lost')" id="nav-lost">Lost & Found</a></li>
            <li><a onclick="showPage('shelters')" id="nav-shelters">Shelters</a></li>
            <li><a onclick="showPage('donate')" id="nav-donate">Donate</a></li>
            <?php if(isset($_SESSION['user_id'])): ?>
                <li style="color:var(--accent-color); margin-left:10px;">Hi, <?php echo htmlspecialchars($_SESSION['name']); ?></li>
                <li><a href="logout.php" class="auth-btn">Logout</a></li>
            <?php else: ?>
                <li><a href="login.php" class="auth-btn">Login / Signup</a></li>
            <?php endif; ?>
        </ul>
    </nav>

    <section id="home" class="container active">
        <div class="hero">
            <h1>Adopt, Don't Shop.</h1>
            <p>Help us find loving homes for the strays of Baguio City.</p>
        </div>
        <div class="content-box">
            <h2>About FurFinder</h2>
            <p>FurFinder is a dedicated initiative committed to the welfare of stray dogs and cats in Baguio City and beyond. 
                We bridge the gap between compassionate citizens and animals in need. Our platform simplifies the adoption process, 
                reunites lost pets with their owners, and supports local shelters. We believe every stray deserves a second chance 
                at a happy life, safe from the streets and in the warmth of a loving home.</p>
        </div>
        <div class="ordinance-box">
            <h2><i class="fas fa-gavel"></i> Adoption Process</h2>
            <p style="margin-bottom: 1rem; font-style: italic;">As per City Ordinance #19 s.2021</p>
            
            <h3>Requirements to Adopt:</h3>
            <ul class="req-list">
                <li><strong>Barangay Certificate:</strong> Must state that you are a resident of said barangay.</li>
                <li><strong>Valid Identification (ID):</strong> Present one of the following:
                    <ul style="list-style: circle; margin-left: 20px; margin-top: 5px; color: #555;">
                        <li>UMID</li>
                        <li>Driver's License</li>
                        <li>Professional PRC ID</li>
                        <li>Senior Citizen ID</li>
                        <li>SSS ID</li>
                        <li>Voter's ID or Comelec Registration Card</li>
                    </ul>
                </li>
                <li><strong>Dog Cage & Leash:</strong> Make sure the dog to be adopted can fit comfortably in the cage. Bring a dog leash if a cage is not applicable.</li>
            </ul>

            <div class="note">
                <i class="fas fa-info-circle"></i> <strong>Note:</strong> Following these guidelines ensures the safety of the animal and compliance with local laws.
            </div>
        </div>
    </section>

    <section id="adopt" class="container">
        <div class="section-header">
            <h2>Available for Adoption</h2>
            <p>Meet our furry friends looking for a forever home.</p>
        </div>
        <div class="pet-grid">
            <?php
            $result = $conn->query("SELECT * FROM pets WHERE status='available'");
            if($result && $result->num_rows > 0){
                while($row = $result->fetch_assoc()){
                    echo "<div class='pet-card'>
                        <div class='pet-img'><img src='{$row['image_url']}' alt='Pet'></div>
                        <div class='pet-details'>
                            <h3>{$row['name']}</h3>
                            <p><strong>Breed:</strong> {$row['breed']}</p>
                            <p><strong>Age:</strong> {$row['age']}</p>
                            <button class='btn-primary' onclick=\"openAdoptModal('{$row['name']}')\">Apply to Adopt</button>
                        </div>
                    </div>";
                }
            } else {
                echo "<p style='text-align:center; width:100%;'>No pets currently available.</p>";
            }
            ?>
        </div>
    </section>

    <section id="lost" class="container">
        <div class="section-header">
            <h2>Lost & Found</h2>
        </div>
        <div class="lf-layout">
            <div class="report-form">
                <h3>Report Missing Pet</h3>
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group"><label>Pet Name</label><input type="text" name="lf_name" required></div>
                    <div class="form-group"><label>Location</label><input type="text" name="lf_location" required></div>
                    <div class="form-group"><label>Time</label><input type="datetime-local" name="lf_time" required></div>
                    <div class="form-group"><label>Contact</label><input type="tel" name="lf_contact" required></div>
                    <div class="form-group"><label>Photo</label><input type="file" name="lf_photo" accept="image/*" required></div>
                    <div class="form-group"><label>Description</label><textarea name="lf_desc"></textarea></div>
                    <button type="submit" name="submit_lost_report" class="btn-primary">Post Alert</button>
                </form>
            </div>
            <div class="missing-feed">
                <h3>Recent Reports</h3>
                <?php
                // SAFETY CHECK HERE
                $lost = $conn->query("SELECT * FROM lost_pets ORDER BY id DESC");
                if($lost && $lost->num_rows > 0) {
                    while($row = $lost->fetch_assoc()){
                        echo "<div class='missing-card'>
                            <div class='missing-img-container'><img src='{$row['photo_path']}'></div>
                            <div>
                                <h4>MISSING: {$row['pet_name']}</h4>
                                <p><i class='fas fa-map-marker-alt'></i> {$row['location']}</p>
                                <p><i class='fas fa-phone'></i> {$row['contact_number']}</p>
                                <p style='font-size:0.9rem;'>{$row['description']}</p>
                            </div>
                        </div>";
                    }
                } else {
                    echo "<p>No lost pets reported.</p>";
                }
                ?>
            </div>
        </div>
    </section>

    <section id="shelters" class="container">
        <div class="section-header">
            <h2>Partner Shelters</h2>
        </div>
        <?php
        // Helper to safely get status
        function getStatus($conn, $name) {
            $res = $conn->query("SELECT status FROM shelters WHERE name LIKE '%$name%' LIMIT 1");
            if($res && $row = $res->fetch_assoc()) return $row['status'];
            return "Open";
        }
        $fara = getStatus($conn, 'Furvent');
        $cvao = getStatus($conn, 'Baguio');
        ?>
<div class="shelter-card">
    <div class="shelter-logo">
        <img src="https://scontent.fmnl13-4.fna.fbcdn.net/v/t39.30808-6/419834185_728763572682550_5313537103014581505_n.jpg?_nc_cat=108&ccb=1-7&_nc_sid=6ee11a&_nc_ohc=9uhRognD9hEQ7kNvwENrvHX&_nc_oc=AdkJrqZaJ5bDuD5Mp30z7BNDElHGIWzntr8jOWaZ2S3-X4quwDTwXFcNzU21t9NkC0PxAZCJUMWgIw4bhdsrE3Ew&_nc_zt=23&_nc_ht=scontent.fmnl13-4.fna&_nc_gid=U9hHORWvmlBR2uixtRQC5g&oh=00_AfjBmk6chpcb0PB-Z7u0TTqSvCJgwWwJoSAjPGJjoQ1KAA&oe=6931EF29" alt="Photo of Baguio City Vet Office">
    </div>
    <div class="shelter-info">
        <h3>Furvent Animal Rescue and Advocacy<span class="status-badge <?php echo ($cvao=='Open')?'status-open':'status-full'; ?>"><?php echo $cvao; ?></span></h3>
        <ul>
                    <li><i class="fas fa-map-marker-alt"></i> <strong>Address:</strong> Baguio City</li>
                    <li><i class="fas fa-phone"></i> <strong>Contact:</strong> (+63) 912-212-6617</li>
                    <li><i class="fas fa-envelope"></i> <strong>Email:</strong> furventrescueadvocacy@gmail.com</li>
                    <li><i class="far fa-clock"></i> <strong>Schedule:</strong> Mon - Fri</li>
            </ul>
    </div>
</div>
<div class="shelter-card">
    <div class="shelter-logo">
        <img src="https://animalcare.baguio.gov.ph/assets/CVAO-3dbfc044.jpg" alt="Photo of Baguio City Vet Office">
    </div>
    <div class="shelter-info">
        <h3>Baguio City Veterinary and Agriculture Office<span class="status-badge <?php echo ($cvao=='Open')?'status-open':'status-full'; ?>"><?php echo $cvao; ?></span></h3>
        <ul>
                    <li><i class="fas fa-map-marker-alt"></i> <strong>Address:</strong> Slaugtherhouse Cmpnd, Sto. Niño, Baguio City</li>
                    <li><i class="fas fa-phone"></i> <strong>Contact:</strong> (74) 443-5332</li>
                    <li><i class="fas fa-envelope"></i> <strong>Email:</strong> cityvet_baguio@yahoo.com</li>
                    <li><i class="far fa-clock"></i> <strong>Schedule:</strong> Mon - Fri, 9:00 AM to 4:00 PM</li>
            </ul>
    </div>
</div>
    </section>

    <section id="donate" class="container">
        <div class="section-header"><h2>Support Us</h2></div>
        <div class="content-box" style="max-width:600px; margin:0 auto; text-align:center;">
            <i class="fas fa-hand-holding-usd fa-3x" style="color:var(--accent-color);"></i>
            <h3>Monetary Donation</h3>
            <form method="POST" style="text-align:left;">
                <div class="form-group"><label>Name</label><input type="text" name="donor_name" required></div>
                <div class="form-group"><label>Amount</label><input type="number" name="donor_amount" required></div>
                <div class="form-group"><label>Message</label><textarea name="donor_message" required></textarea></div>
                <button type="submit" name="submit_donation" class="btn-primary">Donate</button>
            </form>
        <hr style="margin: 2rem 0; opacity: 0.3;">
        <div class="donate-box">
                <div class="donate-icon">
                    <i class="fas fa-gift fa-3x" style="color:var(--accent-color);"></i>
                </div>
            <h3>In-Kind Donations</h3>
                <p>We gratefully accept clothing, shelter materials, and pet food.</p>
                <div style="background:#eef; padding:15px; margin-top:10px; border-radius:4px;">
                <i class="fas fa-mobile-alt"></i> Call for pickup: <strong>0967 213 7048</strong>
            </div>
        </div>
    </section>

    <div id="adoptModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeAdoptModal()">&times;</span>
            <h3>Adopt <span id="adopt-pet-name"></span></h3>
            <form method="POST">
                <input type="hidden" id="app_pet_name" name="app_pet_name">
                <div class="form-group"><label>Name</label><input type="text" name="app_fullname" required></div>
                <div class="form-group"><label>Address</label><input type="text" name="app_address" required></div>
                <div class="form-group"><label>Contact</label><input type="text" name="app_contact" required></div>
                <button type="submit" name="submit_application" class="btn-primary">Submit</button>
            </form>
        </div>
    </div>

    <?php if(isset($showQR)): ?>
    <div id="qrModal" class="modal" style="display:flex;">
        <div class="modal-content" style="text-align:center; background:#007dfe; color:white;">
            <span class="close-modal" onclick="this.parentElement.parentElement.style.display='none'">&times;</span>
            <h3>Scan GCash QR</h3>
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=FurFinderDonation" style="margin:20px; border-radius:8px;">
        </div>
    </div>
    <?php endif; ?>

    <script>
        function showPage(pageId) {
            document.querySelectorAll('.container').forEach(s => s.classList.remove('active'));
            document.querySelectorAll('.nav-links a').forEach(l => l.classList.remove('active'));
            
            const section = document.getElementById(pageId);
            if(section) section.classList.add('active');
            
            const link = document.getElementById('nav-' + pageId);
            if(link) link.classList.add('active');
        }

        function openAdoptModal(name) {
            document.getElementById('adopt-pet-name').innerText = name;
            document.getElementById('app_pet_name').value = name;
            document.getElementById('adoptModal').style.display = 'flex';
        }
        function closeAdoptModal() {
            document.getElementById('adoptModal').style.display = 'none';
        }
    </script>
</body>
</html>