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

// Field-specific error variables
$productNameError = '';
$categoryError = '';
$quantityError = '';
$unitError = '';
$priceError = '';
$imageError = '';
$customCategoryError = '';

$imagePath = $item['image_path'] ?? null;

// Field values - initialize with existing item data
$productNameValue = htmlspecialchars($item['product_name']);
$categoryValue = htmlspecialchars($item['category']);
$quantityValue = htmlspecialchars($item['quantity']);
$unitValue = htmlspecialchars($item['unit']);
$priceValue = $item['price'] ? htmlspecialchars($item['price']) : '';
$descriptionValue = htmlspecialchars($item['description'] ?? '');
$customCategoryValue = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $farmerId) {
    $productName = trim($_POST['product_name'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $customCategory = trim($_POST['custom_category'] ?? '');
    $quantity = $_POST['quantity'] ?? '';
    $unit = trim($_POST['unit'] ?? '');
    $price = $_POST['price'] ?? '';
    $description = trim($_POST['description'] ?? '');
    
    // Field-specific validation
    if (empty($productName)) {
        $productNameError = 'Product name is required';
    } else {
        // Check for duplicate product name (excluding current product)
        if (productNameExists($farmerId, $productName, $itemId)) {
            $productNameError = 'A product with this name already exists. Please use a different name.';
            $productNameValue = '';
        }
    }
    
    if (empty($category)) {
        $categoryError = 'Category is required';
    } elseif ($category === 'Other' && empty($customCategory)) {
        $customCategoryError = 'Please enter a custom category';
        $categoryError = '';
    }
    
    if (empty($quantity)) {
        $quantityError = 'Quantity is required';
    } elseif (!is_numeric($quantity) || floatval($quantity) < 0) {
        $quantityError = 'Please enter a valid quantity (0 or greater)';
        $quantityValue = '';
    }
    
    if (empty($unit)) {
        $unitError = 'Unit is required';
    }
    
    if (!empty($price) && (!is_numeric($price) || floatval($price) < 0)) {
        $priceError = 'Please enter a valid price (0 or greater)';
        $priceValue = '';
    }
    
    // Handle image upload if provided
    $imageUploadError = '';
    $uploadedPath = handleProductImageUpload($_FILES['product_image'] ?? null, $imageUploadError, $imagePath);
    if ($uploadedPath === false && !empty($imageUploadError)) {
        $imageError = $imageUploadError;
    } else if ($uploadedPath !== false) {
        $imagePath = $uploadedPath;
    }

    // If no validation errors, proceed with update
    if (empty($productNameError) && empty($categoryError) && empty($customCategoryError) && 
        empty($quantityError) && empty($unitError) && empty($priceError) && empty($imageError)) {
        
        // Use custom category if "Other" was selected
        $finalCategory = ($category === 'Other' && !empty($customCategory)) ? $customCategory : $category;
        
        $data = [
            'product_name' => $productName,
            'category' => $finalCategory,
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
            // Show error in product name field if database error
            $productNameError = $result['message'];
            $productNameValue = '';
        }
    } else {
        // Preserve valid values, clear invalid ones
        $productNameValue = empty($productNameError) ? $productName : '';
        $categoryValue = empty($categoryError) ? $category : '';
        $quantityValue = empty($quantityError) ? $quantity : '';
        $unitValue = empty($unitError) ? $unit : '';
        $priceValue = empty($priceError) ? $price : '';
        $descriptionValue = $description;
        $customCategoryValue = empty($customCategoryError) ? $customCategory : '';
    }
}

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
    <link rel="icon" type="image/png" href="../images/agritrack_logo.png?v=3">
    <style>
        /* Critical inline styles */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; overflow-x: hidden; }
        .dashboard-container { display: flex; min-height: 100vh; background: radial-gradient(1200px 400px at -10% -10%, rgba(34, 197, 94, 0.06) 0%, transparent 60%), radial-gradient(800px 300px at 110% 20%, rgba(16, 185, 129, 0.08) 0%, transparent 60%), linear-gradient(to bottom, #ffffff, #f0fdf4); width: 100%; }
        .sidebar { width: 260px; background-color: white; border-right: 1px solid #e2e8f0; display: flex; flex-direction: column; position: fixed; height: 100vh; overflow-y: auto; z-index: 100; }
        .main-content { flex: 1; margin-left: 260px; min-height: 100vh; background: radial-gradient(1200px 400px at -10% -10%, rgba(34, 197, 94, 0.06) 0%, transparent 60%), radial-gradient(800px 300px at 110% 20%, rgba(16, 185, 129, 0.08) 0%, transparent 60%), linear-gradient(to bottom, #ffffff, #f0fdf4); }
    </style>
    <link rel="stylesheet" href="<?php echo (strpos($_SERVER['REQUEST_URI'], '/AgriTrack') !== false) ? '/AgriTrack/css/home.css' : '../css/home.css'; ?>?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo (strpos($_SERVER['REQUEST_URI'], '/AgriTrack') !== false) ? '/AgriTrack/css/add_product.css' : '../css/add_product.css'; ?>?v=<?php echo time(); ?>">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-logo">
                <a href="landing.php" class="sidebar-logo-text">Agr<span class="logo-i">i</span>Track</a>
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
                    <form method="POST" enctype="multipart/form-data" class="product-form" id="edit-product-form">
                        <div class="form-section">
                            <h2 class="form-section-title">Product Information</h2>
                            
                            <div class="form-row">
                                <label for="product_name">Product Name <span class="required">*</span></label>
                                <input 
                                    type="text" 
                                    id="product_name" 
                                    name="product_name" 
                                    class="<?php echo $productNameError ? 'input-error' : ''; ?>"
                                    placeholder="<?php echo $productNameError ? htmlspecialchars($productNameError) : 'e.g., Organic Tomatoes'; ?>" 
                                    required 
                                    value="<?php echo $productNameValue; ?>"
                                />
                            </div>

                            <div class="form-row">
                                <label for="category">Category <span class="required">*</span></label>
                                <select id="category" name="category" 
                                    class="<?php echo $categoryError ? 'input-error' : ''; ?>"
                                    required>
                                    <option value=""><?php echo $categoryError ? htmlspecialchars($categoryError) : 'Select a category'; ?></option>
                                    <?php 
                                    $selectedCategory = $categoryValue;
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
                                        class="<?php echo $customCategoryError ? 'input-error' : ''; ?>"
                                        placeholder="<?php echo $customCategoryError ? htmlspecialchars($customCategoryError) : 'Enter custom category'; ?>"
                                        value="<?php echo ($isCustomCategory && $selectedCategory) ? htmlspecialchars($selectedCategory) : htmlspecialchars($customCategoryValue); ?>"
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
                                ><?php echo $descriptionValue; ?></textarea>
                            </div>

                            <div class="form-row">
                                <label for="product_image">Product Image</label>
                                <div class="image-upload">
                                    <input type="file" id="product_image" name="product_image" accept="image/*">
                                    <div class="image-dropzone <?php echo $imageError ? 'image-error' : ''; ?>" id="edit-product-image-dropzone">
                                        <div class="image-preview" id="edit-product-image-preview">
                                            <?php if (!empty($imagePath)): ?>
                                                <img src="<?php echo htmlspecialchars('../' . ltrim($imagePath, '/')); ?>" alt="Product image">
                                            <?php else: ?>
                                                <span class="image-placeholder">📷</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="image-instructions">
                                            <strong>Upload product photo</strong>
                                            <span><?php echo $imageError ? htmlspecialchars($imageError) : 'Drag & drop or click to browse'; ?></span>
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
                                        class="<?php echo $quantityError ? 'input-error' : ''; ?>"
                                        placeholder="<?php echo $quantityError ? htmlspecialchars($quantityError) : '0'; ?>" 
                                        step="0.01" 
                                        min="0" 
                                        required 
                                        value="<?php echo $quantityValue; ?>"
                                    />
                                </div>

                                <div class="form-row">
                                    <label for="unit">Unit <span class="required">*</span></label>
                                    <select id="unit" name="unit" 
                                        class="<?php echo $unitError ? 'input-error' : ''; ?>"
                                        required>
                                        <option value=""><?php echo $unitError ? htmlspecialchars($unitError) : 'Select a unit'; ?></option>
                                        <?php 
                                        $selectedUnit = $unitValue;
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
                                        class="<?php echo $priceError ? 'input-error' : ''; ?>"
                                        placeholder="<?php echo $priceError ? htmlspecialchars($priceError) : '0.00'; ?>" 
                                        step="0.01" 
                                        min="0" 
                                        value="<?php echo $priceValue; ?>"
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

            // Clear error state when user starts typing
            const productNameInput = document.getElementById('product_name');
            const quantityInput = document.getElementById('quantity');
            const unitSelect = document.getElementById('unit');
            const priceInput = document.getElementById('price');
            
            if (productNameInput) {
                productNameInput.addEventListener('input', function() {
                    if (this.classList.contains('input-error')) {
                        this.classList.remove('input-error');
                        this.placeholder = 'e.g., Organic Tomatoes';
                    }
                });
            }
            
            if (quantityInput) {
                quantityInput.addEventListener('input', function() {
                    if (this.classList.contains('input-error')) {
                        this.classList.remove('input-error');
                        this.placeholder = '0';
                    }
                });
            }
            
            if (unitSelect) {
                unitSelect.addEventListener('change', function() {
                    if (this.classList.contains('input-error')) {
                        this.classList.remove('input-error');
                        const firstOption = this.querySelector('option[value=""]');
                        if (firstOption) {
                            firstOption.textContent = 'Select a unit';
                        }
                    }
                });
            }
            
            if (priceInput) {
                priceInput.addEventListener('input', function() {
                    if (this.classList.contains('input-error')) {
                        this.classList.remove('input-error');
                        this.placeholder = '0.00';
                    }
                });
            }
            
            if (categorySelect) {
                categorySelect.addEventListener('change', function() {
                    if (this.classList.contains('input-error')) {
                        this.classList.remove('input-error');
                        const firstOption = this.querySelector('option[value=""]');
                        if (firstOption) {
                            firstOption.textContent = 'Select a category';
                        }
                    }
                });
            }
            
            if (customCategoryInput) {
                customCategoryInput.addEventListener('input', function() {
                    if (this.classList.contains('input-error')) {
                        this.classList.remove('input-error');
                        this.placeholder = 'Enter custom category';
                    }
                });
            }

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

