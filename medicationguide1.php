<?php
session_start();
// IMPORTANT: Paths are now relative to public_html/ (assuming files are directly in public_html/)
require_once 'db.php'; // Correct path if db.php is in the same directory
require_once 'functions.php'; // Correct path if functions.php is in the same directory

// Fetch medicine articles from database
$medicines = [];
// --- FIX IS HERE: Query from 'medicine_articles' table ---
$query = "SELECT * FROM medicine_articles WHERE category IN ('tablets', 'syrups', 'injections', 'capsules') ORDER BY created_at DESC";
$result = $medicine_db->query($query); // Using $medicine_db connection

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $medicines[] = [
            'name' => htmlspecialchars($row['title']),
            'type' => htmlspecialchars($row['category']),
            'details' => htmlspecialchars($row['content']),
            'image' => htmlspecialchars($row['image_path'] ?? ''), // Use null coalescing for image path
            'side_effects' => htmlspecialchars($row['side_effects'] ?? 'No side effects reported'),
            'generic_name' => htmlspecialchars($row['generic_name'] ?? 'N/A'),
            'brand_names' => htmlspecialchars($row['brand_names'] ?? 'N/A'),
            'prescription_required' => htmlspecialchars($row['prescription_required'] ?? 'N/A'), // Will be 1 or 0, display as Yes/No in JS
            'available_forms' => htmlspecialchars($row['available_forms'] ?? 'N/A'),
            'active_ingredients' => htmlspecialchars($row['active_ingredients'] ?? 'N/A'),
            'adult_dosage' => htmlspecialchars($row['adult_dosage'] ?? 'N/A'),
            'elderly_dosage' => htmlspecialchars($row['elderly_dosage'] ?? 'N/A'),
            'children_dosage' => htmlspecialchars($row['children_dosage'] ?? 'N/A'),
            'pregnancy_safety' => htmlspecialchars($row['pregnancy_safety'] ?? 'N/A'),
            'breastfeeding_safety' => htmlspecialchars($row['breastfeeding_safety'] ?? 'N/A'),
            'dosage_chart' => htmlspecialchars($row['dosage_chart'] ?? 'N/A'),
            'how_to_take' => htmlspecialchars($row['how_to_take'] ?? 'N/A'),
            'missed_dose' => htmlspecialchars($row['missed_dose'] ?? 'N/A'),
            'overdose_symptoms' => htmlspecialchars($row['overdose_symptoms'] ?? 'N/A')
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Medicine Guide</title>
    <style>
        /* General Resets and Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Arial", sans-serif;
        }

        body {
            background-color: #f4f4f9; /* Consistent light background */
            color: #333;
            line-height: 1.6;
            display: flex;
            flex-direction: column; /* Ensure layout pushes footer down */
            min-height: 100vh;
        }

        /* Header and Navbar (Consistent Across Site) */
        .main-header {
            background-color: #007bff;
            padding: 1rem 2rem;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: #ffffff;
            flex-wrap: wrap; /* Allows items to wrap on smaller screens */
        }

        .logo {
            font-size: 1.8rem; /* Slightly larger logo */
            font-weight: bold;
            color: #ffffff;
        }

        .nav-links {
            list-style: none;
            display: flex;
            gap: 2rem;
            margin: 0;
            padding: 0;
            transition: all 0.3s ease-in-out; /* For mobile menu animation */
        }

        .nav-links a {
            color: #ffffff;
            text-decoration: none;
            transition: color 0.3s;
            font-weight: 500;
            padding: 0.5rem 0; /* Add padding for click area */
        }

        .nav-links a:hover {
            color: #cceeff; /* Lighter blue on hover */
        }

        /* Hamburger Menu Styles */
        .hamburger {
            display: none; /* Hidden by default on desktop */
            cursor: pointer;
            flex-direction: column;
            justify-content: space-around;
            width: 30px;
            height: 25px;
            z-index: 1000; /* Ensure it's on top */
        }

        .hamburger .bar {
            width: 100%;
            height: 3px;
            background-color: white;
            transition: all 0.3s ease-in-out;
        }

        /* Hamburger animation */
        .hamburger.toggle .bar:nth-child(1) {
            transform: translateY(11px) rotate(45deg);
        }
        .hamburger.toggle .bar:nth-child(2) {
            opacity: 0;
        }
        .hamburger.toggle .bar:nth-child(3) {
            transform: translateY(-11px) rotate(-45deg);
        }

        /* Search and Filters Section */
        .medicine-search-container {
            padding: 2rem;
            text-align: center;
            background: #ffffff; /* Use white background */
            border-bottom: 1px solid #eee; /* Add a subtle border */
        }

        #medicineSearch {
            width: 70%;
            max-width: 600px; /* Limit max width */
            padding: 1rem 1.5rem; /* Increased horizontal padding */
            border: 2px solid #007bff;
            border-radius: 30px;
            font-size: 1.1rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            transition: border-color 0.3s;
        }

        #medicineSearch:focus {
            outline: none;
            border-color: #0056b3;
        }

        .search-filters {
            display: flex;
            gap: 0.8rem; /* Slightly reduced gap */
            justify-content: center;
            align-items: center;
            flex-wrap: wrap; /* Allow filters to wrap */
        }

        .filter-btn {
            padding: 0.6rem 1.4rem; /* Adjusted padding */
            border: 2px solid #007bff;
            border-radius: 20px;
            background: transparent;
            color: #007bff;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 0.95rem;
        }

        .filter-btn.active,
        .filter-btn:hover {
            background: #007bff;
            color: #ffffff;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .warning-message {
            display: flex;
            align-items: center;
            background-color: #fff8e5; /* Lighter warning background */
            color: #b35a00;
            border: 1px solid #ffc107;
            border-radius: 8px; /* Slightly more rounded */
            padding: 12px 20px; /* More padding */
            margin: 20px auto;
            font-size: 15px; /* Slightly smaller font for warning */
            max-width: 90%;
            text-align: left; /* Align text to left */
            box-sizing: border-box;
        }

        .warning-message img {
            margin-right: 10px;
            width: 24px; /* Adjust icon size */
            height: 24px;
        }

        /* Medicine Guide Grid */
        main.medicine-guide-grid { /* Target main as grid container */
            flex-grow: 1; /* Allow main to grow and push footer down */
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 2.5rem; /* Increased gap between cards */
            padding: 2.5rem; /* Increased overall padding */
        }

        .medicine-card {
            background: #ffffff;
            border-radius: 12px; /* More rounded corners */
            padding: 1.8rem; /* Increased padding */
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1); /* Stronger shadow */
            transition: transform 0.3s ease-in-out, box-shadow 0.3s ease-in-out;
            border: 1px solid #007bff; /* Use primary color border */
            display: flex; /* Use flexbox for internal layout */
            flex-direction: column;
            justify-content: space-between; /* Push button to bottom */
        }

        .medicine-card:hover {
            transform: translateY(-8px); /* More noticeable lift */
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15); /* Darker shadow on hover */
        }

        .medicine-type {
            color: #0171e9;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 0.9rem; /* Slightly larger type text */
            margin-bottom: 0.6rem;
        }

        .medicine-name {
            color: #007bff;
            margin-bottom: 1rem;
            font-size: 1.6rem; /* Larger name */
            line-height: 1.3;
        }

        .medicine-details {
            color: #555; /* Slightly darker details text */
            line-height: 1.6;
            margin-bottom: 1.5rem; /* More space before button */
            flex-grow: 1; /* Allows details to take available space */
        }

        .view-details-btn {
            background: #0171e9;
            color: #ffffff;
            padding: 0.8rem 1.8rem; /* Larger button */
            border: none;
            border-radius: 25px; /* More rounded button */
            cursor: pointer;
            transition: background 0.3s, transform 0.2s;
            font-size: 1rem;
            align-self: flex-start; /* Align button to start */
        }

        .view-details-btn:hover {
            background: #0056b3;
            transform: scale(1.03);
        }

        .no-medicines-found {
            grid-column: 1 / -1; /* Span across all columns */
            text-align: center;
            padding: 3rem;
            font-size: 1.5rem;
            color: #666;
        }

        /* Modal Styles */
        .medicine-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6); /* Darker overlay */
            z-index: 1000;
            overflow-y: auto;
            backdrop-filter: blur(5px); /* Blurred background */
            -webkit-backdrop-filter: blur(5px); /* Safari support */
        }

        .modal-content {
            background: #ffffff;
            padding: 2.5rem; /* More padding */
            border-radius: 12px; /* Rounded corners */
            width: 90%; /* Wider on desktop */
            max-width: 800px; /* Increased max width for more content */
            margin: 3rem auto; /* More margin */
            position: relative;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2); /* Stronger shadow */
            animation: fadeIn 0.3s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .modal-content h2 {
            color: #007bff;
            font-size: 2rem;
            margin-bottom: 1.2rem;
            line-height: 1.2;
        }

        .modal-content h3 {
            color: #0056b3;
            font-size: 1.4rem;
            margin-top: 1.8rem;
            margin-bottom: 0.8rem;
            border-bottom: 1px solid #eee; /* Subtle separator */
            padding-bottom: 5px;
        }

        .modal-content p {
            color: #444;
            line-height: 1.7;
            margin-bottom: 0.5rem;
            font-size: 1rem;
        }

        .modal-content strong {
            color: #222;
        }

        .close-modal {
            position: absolute;
            top: 1.5rem;
            right: 1.5rem;
            background: #dc3545; /* Red close button */
            color: #ffffff;
            padding: 0.6rem 1.2rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1.2rem; /* Larger close button */
            transition: background-color 0.3s, transform 0.2s;
        }

        .close-modal:hover {
            background-color: #c82333;
            transform: scale(1.05);
        }

        /* Footer Styles */
        footer {
            background: #007bff;
            color: white;
            text-align: center;
            padding: 20px 0;
            margin-top: 40px; /* Space above footer */
            width: 100%;
        }


        /* Responsive Adjustments */
        @media (max-width: 992px) {
            main.medicine-guide-grid {
                grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); /* Slightly smaller min-width */
                gap: 2rem;
                padding: 2rem;
            }
        }

        @media (max-width: 768px) {
            /* Navbar Specific */
            .main-header {
                padding: 1rem 1.5rem;
            }
            .nav-links {
                display: none; /* Hide nav links by default */
                flex-direction: column;
                width: 100%;
                position: absolute;
                top: 70px; /* Adjust based on navbar height */
                left: 0;
                background-color: #007bff;
                box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
                z-index: 999;
                padding: 10px 0;
                opacity: 0; /* Start hidden */
                transform: translateY(-20px); /* Start slightly up */
            }
            .nav-links.nav-active {
                display: flex; /* Show when active */
                opacity: 1; /* Fade in */
                transform: translateY(0); /* Move to position */
            }
            .nav-links li {
                width: 100%;
                text-align: center;
            }
            .nav-links a {
                padding: 15px;
                display: block;
                border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            }
            .hamburger {
                display: flex;
            }

            /* Content Specific */
            .medicine-search-container {
                padding: 1.5rem;
            }

            #medicineSearch {
                width: 90%;
                padding: 0.8rem 1.2rem;
                font-size: 1rem;
            }

            .search-filters {
                gap: 0.6rem;
            }

            .filter-btn {
                padding: 0.5rem 1rem;
                font-size: 0.9rem;
            }

            .warning-message {
                font-size: 14px;
                padding: 10px 15px;
                margin: 15px auto;
                max-width: 95%;
            }

            main.medicine-guide-grid {
                grid-template-columns: 1fr; /* Stack cards vertically */
                padding: 1.5rem;
            }

            .medicine-card {
                margin-bottom: 1rem; /* Add space between stacked cards */
                padding: 1.5rem;
            }

            .medicine-name {
                font-size: 1.4rem;
            }

            .medicine-details {
                font-size: 0.95rem;
            }

            .view-details-btn {
                padding: 0.7rem 1.5rem;
                font-size: 0.95em;
            }

            .modal-content {
                width: 95%;
                margin: 1.5rem auto;
                padding: 1.5rem;
            }

            .modal-content h2 {
                font-size: 1.8rem;
            }

            .modal-content h3 {
                font-size: 1.2rem;
            }

            .modal-content p {
                font-size: 0.95rem;
            }

            .close-modal {
                top: 0.8rem;
                right: 0.8rem;
                padding: 0.4rem 0.8rem;
                font-size: 1rem;
            }
        }

        @media (max-width: 480px) {
            .main-header {
                padding: 0.8rem 1rem;
            }

            .logo {
                font-size: 1.5rem;
            }

            .medicine-search-container {
                padding: 1rem;
            }

            #medicineSearch {
                width: 100%;
                padding: 0.7rem 1rem;
                font-size: 0.9rem;
            }

            .search-filters {
                flex-direction: column; /* Stack filter buttons */
                gap: 0.5rem;
                align-items: stretch; /* Stretch buttons to full width */
            }

            .filter-btn {
                width: 100%; /* Full width buttons */
                padding: 0.6rem;
                font-size: 0.9rem;
            }

            .warning-message {
                font-size: 13px;
                padding: 8px 10px;
                margin: 10px auto;
                flex-direction: column; /* Stack icon and text */
                text-align: center;
            }
            .warning-message img {
                margin-right: 0;
                margin-bottom: 5px;
            }

            main.medicine-guide-grid {
                padding: 1rem;
                gap: 1.2rem;
            }

            .medicine-card {
                border-radius: 8px;
                padding: 1.2rem;
            }

            .medicine-type {
                font-size: 0.8rem;
            }

            .medicine-name {
                font-size: 1.2rem;
                margin-bottom: 0.8rem;
            }

            .medicine-details {
                font-size: 0.85rem;
            }

            .view-details-btn {
                padding: 0.6rem 1.2rem;
                font-size: 0.9rem;
            }

            .modal-content {
                margin: 1rem auto;
                padding: 1rem;
                border-radius: 8px;
            }

            .modal-content h2 {
                font-size: 1.5rem;
            }

            .modal-content h3 {
                font-size: 1.1rem;
            }

            .modal-content p {
                font-size: 0.85rem;
            }

            .close-modal {
                top: 0.5rem;
                right: 0.5rem;
                padding: 0.3rem 0.6rem;
                font-size: 0.9rem;
            }

            .no-medicines-found {
                font-size: 1.2rem;
                padding: 2rem;
            }
        }
    </style>
</head>
<body>
    <header class="main-header">
        <nav class="navbar">
            <div class="logo">MedicineGuide</div>
            <ul class="nav-links">
                <li><a href="index">Home</a></li>
                <li><a href="healtharticles">Articles</a></li>
                <li><a href="about.html">About</a></li>
                <?php if (isLoggedIn()): ?>
                    <li><a href="logout">Log Out</a></li>
                    <?php if (isAdmin()): ?>
                        <li><a href="backend/adminpage">Admin Panel</a></li>
                    <?php endif; ?>
                <?php else: ?>
                    <li><a href="login">Log In</a></li>
                <?php endif; ?>
            </ul>
            <div class="hamburger">
                <span class="bar"></span>
                <span class="bar"></span>
                <span class="bar"></span>
            </div>
        </nav>
    </header>

    <div class="medicine-search-container">
        <input type="text" placeholder="Search medicines..." id="medicineSearch">
        <div class="search-filters">
            <button class="filter-btn active" data-filter="all">All</button>
            <button class="filter-btn" data-filter="tablets">Tablets</button>
            <button class="filter-btn" data-filter="syrups">Syrups</button>
            <button class="filter-btn" data-filter="injections">Injections</button>
            <button class="filter-btn" data-filter="capsules">Capsule</button>
        </div>
        <div class="warning-message">
            <img src="image.png\warning 32.png" alt="Warning" />
            <span>--Please consult your doctor before using any medication listed here.</span>
        </div>
    </div>

    <main class="medicine-guide-grid" id="medicineGrid">
        <?php if(empty($medicines)): ?>
            <p class="no-medicines-found">No medicine articles found</p>
        <?php else: ?>
            <?php foreach ($medicines as $medicine): ?>
                <div class="medicine-card" data-type="<?= $medicine['type'] ?>">
                    <div class="medicine-type"><?= ucfirst($medicine['type']) ?></div>
                    <h2 class="medicine-name"><?= $medicine['name'] ?></h2>
                    <p class="medicine-details"><?= substr($medicine['details'], 0, 100) ?>...</p>
                    <button class="view-details-btn" data-medicine='<?= htmlspecialchars(json_encode($medicine)) ?>'>
                        View Details
                    </button>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>

    <div id="medicineModal" class="medicine-modal"></div>

    <footer>
        <p>&copy; Health Care & Medicine Guide</p>
    </footer>

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            // Hamburger menu functionality (consistent)
            const hamburger = document.querySelector('.hamburger');
            const navLinks = document.querySelector('.nav-links');

            if (hamburger && navLinks) {
                hamburger.addEventListener('click', () => {
                    navLinks.classList.toggle('nav-active');
                    hamburger.classList.toggle('toggle');
                });
                document.querySelectorAll('.nav-links li a').forEach(link => {
                    link.addEventListener('click', () => {
                        navLinks.classList.remove('nav-active');
                        hamburger.classList.remove('toggle');
                    });
                });
            }

            const medicines = <?= json_encode($medicines) ?>;
            const medicineGrid = document.getElementById("medicineGrid");
            const searchInput = document.getElementById("medicineSearch");
            const filterBtns = document.querySelectorAll(".filter-btn");
            const modal = document.getElementById("medicineModal");

            function showMedicineDetails(medicine) {
                // Determine display value for prescription_required
                const prescriptionStatus = medicine.prescription_required === '1' || medicine.prescription_required.toLowerCase() === 'yes' ? 'Yes' : 'No';

                modal.innerHTML = `
                    <div class="modal-content">
                        <button class="close-modal">&times;</button>
                        <h2>${medicine.name}</h2>
                        <p><strong>Type:</strong> ${medicine.type}</p>
                        <p><strong>Details:</strong> ${medicine.details}</p>
                        <p><strong>Side Effects:</strong> ${medicine.side_effects}</p>
                        <h3>Basic Information 🏥</h3>
                        <p><strong>Generic Name:</strong> ${medicine.generic_name}</p>
                        <p><strong>Brand Names:</strong> ${medicine.brand_names}</p>
                        <p><strong>Prescription Required?:</strong> ${prescriptionStatus}</p>
                        <p><strong>Available Forms:</strong> ${medicine.available_forms}</p>
                        <p><strong>Active Ingredients:</strong> ${medicine.active_ingredients}</p>
                        <h3>Suitable for Different Age Groups & Genders 👨‍👩‍👧‍👦</h3>
                        <p><strong>👨‍⚕️ Adult (18-60 years):</strong> ${medicine.adult_dosage}</p>
                        <p><strong>👴 Elderly (60+ years):</strong> ${medicine.elderly_dosage}</p>
                        <p><strong>👶 Children (0-12 years):</strong> ${medicine.children_dosage}</p>
                        <p><strong>👩‍🍼 Pregnant Women:</strong> ${medicine.pregnancy_safety}</p>
                        <p><strong>🤱 Breastfeeding Women:</strong> ${medicine.breastfeeding_safety}</p>
                        <h3>Dosage & Administration 📋</h3>
                        <p><strong>Standard Dosage Chart:</strong> ${medicine.dosage_chart}</p>
                        <p><strong>How to Take:</strong> ${medicine.how_to_take}</p>
                        <p><strong>Missed Dose:</strong> ${medicine.missed_dose}</p>
                        <p><strong>Overdose Symptoms:</strong> ${medicine.overdose_symptoms}</p>
                    </div>
                `;
                modal.style.display = "block";

                modal.querySelector(".close-modal").addEventListener("click", () => {
                    modal.style.display = "none";
                });
                // Close modal when clicking outside of it
                modal.addEventListener('click', (e) => {
                    if (e.target === modal) {
                        modal.style.display = 'none';
                    }
                });
            }

            function renderMedicines(filteredMedicines) {
                if (filteredMedicines.length === 0) {
                    medicineGrid.innerHTML = '<p class="no-medicines-found">No medicines found matching your criteria.</p>';
                    return;
                }

                medicineGrid.innerHTML = filteredMedicines
                    .map(medicine => `
                        <div class="medicine-card" data-type="${medicine.type}">
                            <div class="medicine-type">${medicine.type}</div>
                            <h2 class="medicine-name">${medicine.name}</h2>
                            <p class="medicine-details">${medicine.details.substring(0, 100)}...</p>
                            <button class="view-details-btn"
                                data-medicine='${JSON.stringify(medicine)}'>
                                View Details
                            </button>
                        </div>
                    `).join("");

                // Re-attach event listeners after rendering new content
                document.querySelectorAll(".view-details-btn").forEach(btn => {
                    btn.addEventListener("click", () => {
                        const medicineData = JSON.parse(btn.dataset.medicine);
                        showMedicineDetails(medicineData);
                    });
                });
            }

            function filterMedicines() {
                const searchTerm = searchInput.value.toLowerCase();
                const activeFilter = document.querySelector(".filter-btn.active").dataset.filter;

                return medicines.filter(medicine => {
                    const matchesSearch =
                        medicine.name.toLowerCase().includes(searchTerm) ||
                        medicine.details.toLowerCase().includes(searchTerm) ||
                        medicine.generic_name.toLowerCase().includes(searchTerm) ||
                        medicine.brand_names.toLowerCase().includes(searchTerm); // Search in new fields

                    const matchesFilter =
                        activeFilter === "all" || medicine.type.toLowerCase() === activeFilter.toLowerCase();
                    return matchesSearch && matchesFilter;
                });
            }

            function handleSearch() {
                const filtered = filterMedicines();
                renderMedicines(filtered);
            }

            searchInput.addEventListener("input", handleSearch);

            filterBtns.forEach(btn => {
                btn.addEventListener("click", () => {
                    filterBtns.forEach(b => b.classList.remove("active"));
                    btn.classList.add("active");
                    handleSearch();
                });
            });

            // Initial render
            handleSearch(); // Call handleSearch on load to render all medicines initially
        });
    </script>
</body>
</html>