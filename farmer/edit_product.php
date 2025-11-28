<?php
session_start();

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: 0');

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: login.php');
    exit;
}

// Include inventory functions
require_once __DIR__ . '/../includes/inventory_functions.php';

function handleProductImageUpload($file, &$error, $currentPath = null) {
    if (!$file || empty($file['name'])) {
        return $currentPath;
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error = 'Failed to upload product image.';
        return false;
    }

    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $mimeType = mime_content_type($file['tmp_name']);
    if (!in_array($mimeType, $allowedTypes)) {
        $error = 'Please upload a valid image (JPG, PNG, GIF, or WEBP).';
        return false;
    }

    if ($file['size'] > 2 * 1024 * 1024) {
        $error = 'Image must be 2MB or smaller.';
        return false;
    }

    $uploadDir = __DIR__ . '/../uploads/products/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = uniqid('product_', true) . '.' . $extension;
    $destination = $uploadDir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        $error = 'Unable to save the uploaded image.';
        return false;
    }

    if ($currentPath && file_exists(__DIR__ . '/../' . $currentPath)) {
        @unlink(__DIR__ . '/../' . $currentPath);
    }

    return 'uploads/products/' . $filename;
}

// Get current farmer ID
$farmerId = $_SESSION['farmer_id'] ?? null;

// Get item ID from URL
$itemId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$itemId) {
    $_SESSION['error_message'] = 'Invalid product ID';
    header('Location: inventory.php');
    exit;
}

// Get existing product data
$item = $farmerId ? getInventoryItemById($itemId, $farmerId) : null;

if (!$item) {
    $_SESSION['error_message'] = 'Product not found or you do not have permission to edit it';
    header('Location: inventory.php');
    exit;
}

$error = '';
$success = false;
$imagePath = $item['image_path'] ?? null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $farmerId) {
    $productName = trim($_POST['product_name'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $quantity = $_POST['quantity'] ?? '';
    $unit = trim($_POST['unit'] ?? '');
    $price = $_POST['price'] ?? '';
    $description = trim($_POST['description'] ?? '');
    
    // Validation
    if (empty($productName)) {
        $error = 'Product name is required';
    } elseif (empty($category)) {
        $error = 'Category is required';
    } elseif (empty($quantity) || $quantity < 0) {
        $error = 'Please enter a valid quantity (0 or greater)';
    } elseif (empty($unit)) {
        $error = 'Unit is required';
    } else {
        // Prepare data
        // Handle image upload if provided
        $imageUploadError = '';
        $uploadedPath = handleProductImageUpload($_FILES['product_image'] ?? null, $imageUploadError, $imagePath);
        if ($uploadedPath === false) {
            $error = $imageUploadError;
        } else {
            $imagePath = $uploadedPath;
        }
    }

    if (empty($error)) {
        $data = [
            'product_name' => $productName,
            'category' => $category,
            'quantity' => floatval($quantity),
            'unit' => $unit,
            'description' => $description,
            'image_path' => $imagePath
        ];
        
        // Add price if provided
        if (!empty($price) && is_numeric($price)) {
            $data['price'] = floatval($price);
        } else {
            $data['price'] = null; // Clear price if empty
        }
        
        // Update inventory item
        $result = updateInventoryItem($itemId, $farmerId, $data);
        
        if ($result['success']) {
            $_SESSION['success_message'] = $result['message'];
            header('Location: inventory.php');
            exit;
        } else {
            $error = $result['message'];
        }
    }
}

// Use posted values if form was submitted with errors, otherwise use existing item data
$productName = isset($_POST['product_name']) ? htmlspecialchars($_POST['product_name']) : htmlspecialchars($item['product_name']);
$category = isset($_POST['category']) ? htmlspecialchars($_POST['category']) : htmlspecialchars($item['category']);
$quantity = isset($_POST['quantity']) ? htmlspecialchars($_POST['quantity']) : htmlspecialchars($item['quantity']);
$unit = isset($_POST['unit']) ? htmlspecialchars($_POST['unit']) : htmlspecialchars($item['unit']);
$price = isset($_POST['price']) ? htmlspecialchars($_POST['price']) : ($item['price'] ? htmlspecialchars($item['price']) : '');
$description = isset($_POST['description']) ? htmlspecialchars($_POST['description']) : htmlspecialchars($item['description'] ?? '');

// Common categories for agriculture
$commonCategories = [
    'Crops' => ['Rice', 'Wheat', 'Corn', 'Soybeans', 'Cotton', 'Sugarcane', 'Coffee', 'Tea'],
    'Vegetables' => ['Tomatoes', 'Potatoes', 'Onions', 'Carrots', 'Lettuce', 'Peppers', 'Cucumbers', 'Cabbage'],
    'Fruits' => ['Apples', 'Bananas', 'Oranges', 'Mangoes', 'Grapes', 'Strawberries', 'Watermelon', 'Papaya'],
    'Livestock' => ['Cattle', 'Poultry', 'Sheep', 'Goats', 'Pigs', 'Fish'],
    'Equipment' => ['Tractors', 'Tools', 'Irrigation', 'Fertilizer', 'Seeds'],
    'Other' => []
];

// Common units
$commonUnits = ['kg', 'g', 'lbs', 'tons', 'pieces', 'units', 'liters', 'gallons', 'bags', 'boxes', 'crates'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product - AgriTrack</title>
    <link rel="icon" type="image/svg+xml" href="../favicon.svg?v=2">
    <style>
        /* Critical inline styles */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; overflow-x: hidden; }
        .dashboard-container { display: flex; min-height: 100vh; background-color: #f8fafc; width: 100%; }
        .sidebar { width: 260px; background-color: white; border-right: 1px solid #e2e8f0; display: flex; flex-direction: column; position: fixed; height: 100vh; overflow-y: auto; z-index: 100; }
        .main-content { flex: 1; margin-left: 260px; min-height: 100vh; }
    </style>
    <link rel="stylesheet" href="<?php echo (strpos($_SERVER['REQUEST_URI'], '/AgriTrack') !== false) ? '/AgriTrack/css/home.css' : '../css/home.css'; ?>?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo (strpos($_SERVER['REQUEST_URI'], '/AgriTrack') !== false) ? '/AgriTrack/css/add_product.css' : '../css/add_product.css'; ?>?v=<?php echo time(); ?>">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-logo">
                <a href="landing.php" class="sidebar-logo-text">AgriTrack</a>
            </div>
            
            <nav class="sidebar-nav">
                <a href="home.php" class="nav-item">
                    <span class="nav-icon">🏠</span>
                    <span>Home</span>
                </a>
                <a href="inventory.php" class="nav-item active">
                    <span class="nav-icon">📦</span>
                    <span>Inventory</span>
                </a>
                <a href="add_product.php" class="nav-item">
                    <span class="nav-icon">➕</span>
                    <span>Add Products</span>
                </a>
                <a href="reports.php" class="nav-item">
                    <span class="nav-icon">📈</span>
                    <span>Reports</span>
                </a>
                <a href="settings.php" class="nav-item">
                    <span class="nav-icon">⚙️</span>
                    <span>Settings</span>
                </a>
            </nav>
            
            <div class="sidebar-footer">
                <a href="logout.php" class="logout-link">
                    <span class="nav-icon">🚪</span>
                    <span>Logout</span>
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="content-header">
                <div>
                    <h1 class="content-title">Edit Product</h1>
                    <p style="color: #64748b; font-size: 0.875rem; margin-top: 0.25rem;">Update product information</p>
                </div>
                <div class="header-actions">
                    <a href="inventory.php" class="btn-secondary">Cancel</a>
                </div>
            </header>

            <div class="content-body">
                <div class="form-container">
                    <?php if ($error): ?>
                        <div class="alert alert-error">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                        <form method="POST" enctype="multipart/form-data" class="product-form" id="edit-product-form">
                        <div class="form-section">
                            <h2 class="form-section-title">Product Information</h2>
                            
                            <div class="form-row">
                                <label for="product_name">Product Name <span class="required">*</span></label>
                                <input 
                                    type="text" 
                                    id="product_name" 
                                    name="product_name" 
                                    placeholder="e.g., Organic Tomatoes" 
                                    required 
                                    value="<?php echo $productName; ?>"
                                />
                            </div>

                            <div class="form-row">
                                <label for="category">Category <span class="required">*</span></label>
                                <select id="category" name="category" required>
                                    <option value="">Select a category</option>
                                    <?php 
                                    $selectedCategory = isset($_POST['category']) ? $_POST['category'] : $category;
                                    $isCustomCategory = true;
                                    
                                    // Check if selected category is in the predefined list
                                    foreach ($commonCategories as $mainCategory => $subCategories): 
                                        if ($mainCategory === 'Other') continue; // Skip "Other" from check
                                        
                                        if (!empty($subCategories)): 
                                            foreach ($subCategories as $subCategory):
                                                if ($selectedCategory === $subCategory) {
                                                    $isCustomCategory = false;
                                                    break 2;
                                                }
                                            endforeach;
                                        else:
                                            if ($selectedCategory === $mainCategory) {
                                                $isCustomCategory = false;
                                                break;
                                            }
                                        endif;
                                    endforeach;
                                    
                                    // Generate options
                                    foreach ($commonCategories as $mainCategory => $subCategories): 
                                        // Skip "Other" from the loop since we add it manually at the end
                                        if ($mainCategory === 'Other') continue;
                                        
                                        if (!empty($subCategories)): ?>
                                            <optgroup label="<?php echo htmlspecialchars($mainCategory); ?>">
                                                <?php foreach ($subCategories as $subCategory): ?>
                                                    <option value="<?php echo htmlspecialchars($subCategory); ?>" 
                                                        <?php echo ($selectedCategory === $subCategory) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($subCategory); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </optgroup>
                                        <?php else: ?>
                                            <option value="<?php echo htmlspecialchars($mainCategory); ?>"
                                                <?php echo ($selectedCategory === $mainCategory) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($mainCategory); ?>
                                            </option>
                                        <?php endif; 
                                    endforeach; ?>
                                    <option value="Other" <?php echo ($isCustomCategory && $selectedCategory) ? 'selected' : ''; ?>>Other</option>
                                </select>
                                <div class="custom-category-wrapper" style="margin-top: 0.5rem; <?php echo ($isCustomCategory && $selectedCategory) ? '' : 'display: none;'; ?>">
                                    <input 
                                        type="text" 
                                        id="custom-category" 
                                        name="custom_category" 
                                        placeholder="Enter custom category"
                                        value="<?php echo ($isCustomCategory && $selectedCategory) ? htmlspecialchars($selectedCategory) : ''; ?>"
                                        style="padding: 0.875rem 1rem; border: 1px solid #e2e8f0; border-radius: 0.5rem; font-size: 1rem; color: #0f172a; background-color: white; transition: all 0.2s; width: 100%; font-family: inherit;"
                                    />
                                </div>
                                <small class="form-hint">Select from list or choose "Other" to enter a custom category</small>
                            </div>

                            <div class="form-row">
                                <label for="description">Description</label>
                                <textarea 
                                    id="description" 
                                    name="description" 
                                    rows="3" 
                                    placeholder="Additional details about the product (optional)"
                                ><?php echo $description; ?></textarea>
                            </div>

                            <div class="form-row">
                                <label for="product_image">Product Image</label>
                                <div class="image-upload">
                                    <input type="file" id="product_image" name="product_image" accept="image/*">
                                    <div class="image-dropzone" id="edit-product-image-dropzone">
                                        <div class="image-preview" id="edit-product-image-preview">
                                            <?php if (!empty($imagePath)): ?>
                                                <img src="<?php echo htmlspecialchars('../' . ltrim($imagePath, '/')); ?>" alt="Product image">
                                            <?php else: ?>
                                                <span class="image-placeholder">📷</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="image-instructions">
                                            <strong>Upload product photo</strong>
                                            <span>Drag & drop or click to browse</span>
                                        </div>
                                    </div>
                                </div>
                                <small class="form-hint">PNG, JPG, GIF or WEBP up to 2MB</small>
                            </div>
                        </div>

                        <div class="form-section">
                            <h2 class="form-section-title">Quantity & Pricing</h2>
                            
                            <div class="form-row-group">
                                <div class="form-row">
                                    <label for="quantity">Quantity <span class="required">*</span></label>
                                    <input 
                                        type="number" 
                                        id="quantity" 
                                        name="quantity" 
                                        placeholder="0" 
                                        step="0.01" 
                                        min="0" 
                                        required 
                                        value="<?php echo $quantity; ?>"
                                    />
                                </div>

                                <div class="form-row">
                                    <label for="unit">Unit <span class="required">*</span></label>
                                    <select id="unit" name="unit" required>
                                        <option value="">Select a unit</option>
                                        <?php 
                                        $selectedUnit = isset($_POST['unit']) ? $_POST['unit'] : $unit;
                                        foreach ($commonUnits as $unitOption): ?>
                                            <option value="<?php echo htmlspecialchars($unitOption); ?>" 
                                                <?php echo ($selectedUnit === $unitOption) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($unitOption); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="form-hint">Select a unit from the list</small>
                                </div>
                            </div>

                            <div class="form-row">
                                <label for="price">Price per Unit (Optional)</label>
                                <div class="input-group">
                                    <span class="input-prefix">₱</span>
                                    <input 
                                        type="number" 
                                        id="price" 
                                        name="price" 
                                        placeholder="0.00" 
                                        step="0.01" 
                                        min="0" 
                                        value="<?php echo $price; ?>"
                                    />
                                </div>
                                <small class="form-hint">Leave empty if not applicable</small>
                            </div>
                        </div>

                        <div class="form-actions">
                            <a href="inventory.php" class="btn-secondary">Cancel</a>
                            <button type="submit" class="btn-primary">
                                <span class="btn-text">Update Product</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('edit-product-form');
            const categorySelect = document.getElementById('category');
            const customCategoryWrapper = document.querySelector('.custom-category-wrapper');
            const customCategoryInput = document.getElementById('custom-category');

            // Show/hide custom category input
            categorySelect.addEventListener('change', function() {
                if (this.value === 'Other') {
                    customCategoryWrapper.style.display = 'block';
                    customCategoryInput.required = true;
                    customCategoryInput.focus();
                } else {
                    customCategoryWrapper.style.display = 'none';
                    customCategoryInput.required = false;
                    customCategoryInput.value = '';
                }
            });

            // Add submitted class on form submit attempt
            form.addEventListener('submit', function(e) {
                form.classList.add('submitted');
            });

            // Form validation
            form.addEventListener('submit', function(e) {
                const quantity = parseFloat(document.getElementById('quantity').value);
                if (isNaN(quantity) || quantity < 0) {
                    e.preventDefault();
                    alert('Please enter a valid quantity (0 or greater)');
                    return false;
                }
                
                const price = document.getElementById('price').value;
                if (price && (isNaN(price) || parseFloat(price) < 0)) {
                    e.preventDefault();
                    alert('Please enter a valid price (0 or greater)');
                    return false;
                }

                // If "Other" is selected, use custom category value
                if (categorySelect.value === 'Other') {
                    const customCategory = customCategoryInput.value.trim();
                    if (!customCategory) {
                        e.preventDefault();
                        alert('Please enter a custom category');
                        customCategoryInput.focus();
                        return false;
                    }
                    // Create hidden input with custom category and disable select
                    const hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = 'category';
                    hiddenInput.value = customCategory;
                    form.appendChild(hiddenInput);
                    categorySelect.disabled = true;
                }
            });
            const imageInput = document.getElementById('product_image');
            const imagePreview = document.getElementById('edit-product-image-preview');
            const dropzone = document.getElementById('edit-product-image-dropzone');
            if (imageInput && dropzone) {
                dropzone.addEventListener('click', () => imageInput.click());

                imageInput.addEventListener('change', function() {
                    const file = this.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            imagePreview.innerHTML = '<img src="' + e.target.result + '" alt="Preview">';
                        };
                        reader.readAsDataURL(file);
                    } else {
                        imagePreview.innerHTML = '<span class="image-placeholder">📷</span>';
                    }
                });
            }
        });
    </script>
</body>
</html>

