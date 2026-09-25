<?php
session_start();
require 'middleware.php';
require 'db.php';

// Admin verification
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header("Location: admin.php");
    exit();
}

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', 1);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $article_type = $_POST['article_type'];
        $category = $_POST['category'];
        $title = htmlspecialchars(trim($_POST['title']));
        $content = htmlspecialchars(trim($_POST['content']));
        
        // Medicine-specific fields
        $medicine_fields = [
            'side_effects', 'generic_name', 'brand_names', 'prescription_required',
            'available_forms', 'active_ingredients', 'adult_dosage', 'adult_warnings',
            'elderly_dosage', 'elderly_warnings', 'children_dosage', 'children_not_recommended',
            'pregnancy_safety', 'pregnancy_precautions', 'breastfeeding_safety',
            'breastfeeding_impact', 'dosage_chart', 'how_to_take', 'missed_dose', 'overdose_symptoms'
        ];
        
        foreach($medicine_fields as $field) {
            $$field = ($article_type === 'medicine') ? htmlspecialchars(trim($_POST[$field] ?? '')) : null;
        }

        // File upload
        $target_file = null;
        if ($article_type === 'health' && !empty($_FILES['image']['name'])) {
            $target_dir = "uploads/articles/";
            $target_file = $target_dir . uniqid() . '_' . basename($_FILES['image']['name']);
            if (!move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                throw new Exception("File upload failed");
            }
        }

        // Database selection
        $db = ($article_type === 'health') ? $health_db : $medicine_db;

        if ($article_type === 'health') {
            $stmt = $db->prepare("INSERT INTO articles 
                (category, title, content, image_path) 
                VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $category, $title, $content, $target_file);
        } else {
            $stmt = $db->prepare("INSERT INTO articles 
                (category, title, content, side_effects, generic_name, brand_names, 
                prescription_required, available_forms, active_ingredients, adult_dosage, 
                adult_warnings, elderly_dosage, elderly_warnings, children_dosage, 
                children_not_recommended, pregnancy_safety, pregnancy_precautions, 
                breastfeeding_safety, breastfeeding_impact, dosage_chart, how_to_take, 
                missed_dose, overdose_symptoms) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                
            $stmt->bind_param(
                "ssssssssssssssssssssss",
                $category,
                $title,
                $content,
                $side_effects,
                $generic_name,
                $brand_names,
                $prescription_required,
                $available_forms,
                $active_ingredients,
                $adult_dosage,
                $adult_warnings,
                $elderly_dosage,
                $elderly_warnings,
                $children_dosage,
                $children_not_recommended,
                $pregnancy_safety,
                $pregnancy_precautions,
                $breastfeeding_safety,
                $breastfeeding_impact,
                $dosage_chart,
                $how_to_take,
                $missed_dose,
                $overdose_symptoms
            );
        }

        if (!$stmt->execute()) {
            throw new Exception("Database error: " . $stmt->error);
        }

        $success = "Article published successfully!";
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Fetch articles
function fetchArticles($db) {
    $articles = [];
    $result = $db->query("SELECT * FROM articles ORDER BY created_at DESC");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $articles[] = $row;
        }
    }
    return $articles;
}

$health_articles = fetchArticles($health_db);
$medicine_articles = fetchArticles($medicine_db);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Keep existing styles -->
</head>
<body>
    <?php include 'admin-nav.php'; ?>
    
    <div class="admin-container">
        <!-- Form remains the same -->

        <div class="article-preview">
            <h2>Medicine Articles</h2>
            <?php if ($medicine_articles as $article): ?>
                <div class="article-item">
                    <h3><?= htmlspecialchars($article['title']) ?></h3>
                    <p>Category: <?= htmlspecialchars($article['category']) ?></p>
                    
                    <?php if(!empty($article['side_effects'])): ?>
                        <div class="section">
                            <strong>Side Effects:</strong>
                            <p><?= nl2br(htmlspecialchars($article['side_effects'])) ?></p>
                        </div>
                    <?php endif; ?>

                    <div class="detailed-sections">
                        <?php if(!empty($article['generic_name'])): ?>
                            <div class="section">
                                <strong>Generic Name:</strong>
                                <p><?= htmlspecialchars($article['generic_name']) ?></p>
                            </div>
                        <?php endif; ?>

                        <?php if(!empty($article['adult_dosage'])): ?>
                            <div class="section">
                                <strong>Adult Dosage:</strong>
                                <p><?= nl2br(htmlspecialchars($article['adult_dosage'])) ?></p>
                            </div>
                        <?php endif; ?>

                        <?php if(!empty($article['adult_warnings'])): ?>
                            <div class="section">
                                <strong>Adult_Warnings:</strong>
                                <p><?= nl2br(htmlspecialchars($article['adult_warnings'])) ?></p>
                            </div>
                        <?php endif; ?>
                         <?php if(!empty($article['elderly_dosage'])): ?>
                            <div class="section">
                                <strong>Elderly_Dosage:</strong>
                                <p><?= nl2br(htmlspecialchars($article['elderly_dosage'])) ?></p>
                            </div>
                            <?php if(!empty($article['elderly_warnings'])): ?>
                            <div class="section">
                                <strong>Elderly_Warnings:</strong>
                                <p><?= nl2br(htmlspecialchars($article['elderly_warnings'])) ?></p>
                            </div>
                        <?php endif; ?>
                         <?php if(!empty($article['children_dosage'])): ?>
                            <div class="section">
                                <strong>Children_Dosage:</strong>
                                <p><?= nl2br(htmlspecialchars($article['children_dosage'])) ?></p>
                            </div>
                        <?php endif; ?> <?php if(!empty($article['children_not_recommended'])): ?>
                            <div class="section">
                                <strong>Children_Not_Recommended:</strong>
                                <p><?= nl2br(htmlspecialchars($article['children_not_recommended'])) ?></p>
                            </div>
                        <?php endif; ?>
                         <?php if(!empty($article['pregnancy_safety'])): ?>
                            <div class="section">
                                <strong>Pregnancy_Safety:</strong>
                                <p><?= nl2br(htmlspecialchars($article['pregnancy_safety'])) ?></p>
                            </div>
                        <?php endif; ?>
                         <?php if(!empty($article['pregnancy_precautions'])): ?>
                            <div class="section">
                                <strong>Pregnancy_Precautions:</strong>
                                <p><?= nl2br(htmlspecialchars($article['pregnancy_precautions'])) ?></p>
                            </div>
                        <?php endif; ?>
                         <?php if(!empty($article['breastfeeding_safety'])): ?>
                            <div class="section">
                                <strong>Breastfeeding_Safety:</strong>
                                <p><?= nl2br(htmlspecialchars($article['breastfeeding_safety'])) ?></p>
                            </div>
                        <?php endif; ?>
                         <?php if(!empty($article['breastfeeding_impact'])): ?>
                            <div class="section">
                                <strong>Breastfeeding_Impact:</strong>
                                <p><?= nl2br(htmlspecialchars($article['breastfeeding_impact'])) ?></p>
                            </div>
                        <?php endif; ?>
                         <?php if(!empty($article['dosage_chart'])): ?>
                            <div class="section">
                                <strong>Dosage_Chart:</strong>
                                <p><?= nl2br(htmlspecialchars($article['dosage_chart'])) ?></p>
                            </div>
                        <?php endif; ?>
                         <?php if(!empty($article['how_to_take'])): ?>
                            <div class="section">
                                <strong>How_To_Take:</strong>
                                <p><?= nl2br(htmlspecialchars($article['how_to_take'])) ?></p>
                            </div>
                        <?php endif; ?>
                         <?php if(!empty($article['missed_dose'])): ?>
                            <div class="section">
                                <strong>Missed_Dose:</strong>
                                <p><?= nl2br(htmlspecialchars($article['missed_dose'])) ?></p>
                            </div>
                        <?php endif; ?>
                        <?php if(!empty($article['overdose_symptoms'])): ?>
                            <div class="section">
                                <strong>Overdose_Symptoms:</strong>
                                <p><?= nl2br(htmlspecialchars($article['overdose_symptoms'])) ?></p>
                            </div>
                        <?php endif; ?>



                        <!-- Add similar blocks for other fields -->
                        <?php foreach([
                            'brand_names', 'prescription_required', 'available_forms',
                            'active_ingredients', 'adult_warnings', 'elderly_warnings',
                            'children_dosage', 'children_not_recommended', 'pregnancy_safety',
                            'pregnancy_precautions', 'breastfeeding_safety', 'breastfeeding_impact',
                            'dosage_chart', 'how_to_take', 'missed_dose', 'overdose_symptoms'
                        ] as $field): ?>
                            <?php if(!empty($article[$field])): ?>
                                <div class="section">
                                    <strong><?= ucwords(str_replace('_', ' ', $field)) ?>:</strong>
                                    <p><?= nl2br(htmlspecialchars($article[$field])) ?></p>
                                </div>
                            <?php endif; ?>
                        <?php endwhile; else; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

        // Keep existing JavaScript
         <script>
        const categories = {
            health: ['nutrition', 'fitness', 'mental_health', 'diseases'],
            medicine: ['tablets', 'syrups', 'injections', 'capsules']
        };

        const articleType = document.getElementById('articleType');
        const categorySelect = document.getElementById('categorySelect');
        const sideEffectsField = document.getElementById('sideEffectsField');
        const imageField = document.getElementById('imageField');
        const basicInfoFields = document.getElementById('basicInfoFields');
        const ageGroupFields = document.getElementById('ageGroupFields');
        const dosageFields = document.getElementById('dosageFields');

        function updateFormFields() {
            const type = articleType.value;

            // Toggle fields
            sideEffectsField.classList.toggle('active', type === 'medicine');
            imageField.classList.toggle('active', type === 'health');
            basicInfoFields.classList.toggle('active', type === 'medicine');
            ageGroupFields.classList.toggle('active', type === 'medicine');
            dosageFields.classList.toggle('active', type === 'medicine');

            // Update required attributes
            imageField.querySelector('input').required = type === 'health';
            sideEffectsField.querySelector('textarea').required = type === 'medicine';
            basicInfoFields.querySelectorAll('input, textarea, select').forEach(field => {
                field.required = type === 'medicine';
            });
            ageGroupFields.querySelectorAll('input, textarea, select').forEach(field => {
                field.required = type === 'medicine';
            });
            dosageFields.querySelectorAll('input, textarea, select').forEach(field => {
                field.required = type === 'medicine';
            });
        }

        articleType.addEventListener('change', function () {
            // Update categories
            categorySelect.innerHTML = '<option value="">Select Category</option>';

            if (categories[this.value]) {
                categories[this.value].forEach(cat => {
                    const option = document.createElement('option');
                    option.value = cat;
                    option.textContent = cat.replace(/_/g, ' ').toUpperCase();
                    categorySelect.appendChild(option);
                });
            }

            // Update form fields
            updateFormFields();
        });

        // Initial field update
        updateFormFields();
    </script>
    
</body>
</html>