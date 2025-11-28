<?php
/**
 * Inventory Management Functions
 * Handles inventory CRUD operations
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Get all inventory items for a farmer
 * @param int $farmerId
 * @return array
 */
function getInventoryItems($farmerId) {
    $pdo = getDatabaseConnection();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Database connection failed', 'items' => []];
    }
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM inventory WHERE farmer_id = ? ORDER BY created_at DESC");
        $stmt->execute([$farmerId]);
        $items = $stmt->fetchAll();
        
        return ['success' => true, 'items' => $items];
        
    } catch (PDOException $e) {
        error_log("Get inventory items error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to fetch inventory items', 'items' => []];
    }
}

/**
 * Get inventory item by ID
 * @param int $itemId
 * @param int $farmerId
 * @return array|null
 */
function getInventoryItemById($itemId, $farmerId) {
    $pdo = getDatabaseConnection();
    if (!$pdo) {
        return null;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM inventory WHERE Id = ? AND farmer_id = ?");
        $stmt->execute([$itemId, $farmerId]);
        return $stmt->fetch();
        
    } catch (PDOException $e) {
        error_log("Get inventory item error: " . $e->getMessage());
        return null;
    }
}

/**
 * Add new inventory item
 * @param int $farmerId
 * @param array $data
 * @return array
 */
function addInventoryItem($farmerId, $data) {
    $pdo = getDatabaseConnection();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Database connection failed'];
    }
    
    try {
        // Determine status based on quantity
        $quantity = floatval($data['quantity'] ?? 0);
        $status = 'in_stock';
        if ($quantity == 0) {
            $status = 'out_of_stock';
        } elseif ($quantity < 10) {
            $status = 'low_stock';
        }
        
        $sql = "INSERT INTO inventory (farmer_id, product_name, category, quantity, unit, price, description, image_path, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            $farmerId,
            $data['product_name'] ?? '',
            $data['category'] ?? '',
            $quantity,
            $data['unit'] ?? '',
            $data['price'] ?? null,
            $data['description'] ?? '',
            $data['image_path'] ?? null,
            $status
        ]);
        
        if ($result) {
            return ['success' => true, 'message' => 'Inventory item added successfully', 'item_id' => $pdo->lastInsertId()];
        } else {
            return ['success' => false, 'message' => 'Failed to add inventory item'];
        }
        
    } catch (PDOException $e) {
        error_log("Add inventory item error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to add inventory item'];
    }
}

/**
 * Update inventory item
 * @param int $itemId
 * @param int $farmerId
 * @param array $data
 * @return array
 */
function updateInventoryItem($itemId, $farmerId, $data) {
    $pdo = getDatabaseConnection();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Database connection failed'];
    }
    
    try {
        // Determine status based on quantity
        $quantity = isset($data['quantity']) ? floatval($data['quantity']) : null;
        $status = null;
        if ($quantity !== null) {
            if ($quantity == 0) {
                $status = 'out_of_stock';
            } elseif ($quantity < 10) {
                $status = 'low_stock';
            } else {
                $status = 'in_stock';
            }
        }
        
        $allowedFields = ['product_name', 'category', 'quantity', 'unit', 'price', 'description', 'image_path'];
        $updateFields = [];
        $values = [];
        
        foreach ($data as $field => $value) {
            if (in_array($field, $allowedFields)) {
                $updateFields[] = "$field = ?";
                $values[] = $value;
            }
        }
        
        if ($status !== null) {
            $updateFields[] = "status = ?";
            $values[] = $status;
        }
        
        if (empty($updateFields)) {
            return ['success' => false, 'message' => 'No valid fields to update'];
        }
        
        $values[] = $itemId;
        $values[] = $farmerId;
        
        $sql = "UPDATE inventory SET " . implode(', ', $updateFields) . " WHERE Id = ? AND farmer_id = ?";
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute($values);
        
        if ($result) {
            return ['success' => true, 'message' => 'Inventory item updated successfully'];
        } else {
            return ['success' => false, 'message' => 'Failed to update inventory item'];
        }
        
    } catch (PDOException $e) {
        error_log("Update inventory item error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to update inventory item'];
    }
}

/**
 * Delete inventory item
 * @param int $itemId
 * @param int $farmerId
 * @return array
 */
function deleteInventoryItem($itemId, $farmerId) {
    $pdo = getDatabaseConnection();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Database connection failed'];
    }
    
    try {
        $stmt = $pdo->prepare("SELECT image_path FROM inventory WHERE Id = ? AND farmer_id = ?");
        $stmt->execute([$itemId, $farmerId]);
        $item = $stmt->fetch();

        $stmt = $pdo->prepare("DELETE FROM inventory WHERE Id = ? AND farmer_id = ?");
        $result = $stmt->execute([$itemId, $farmerId]);
        
        if ($result && $stmt->rowCount() > 0) {
            if ($item && !empty($item['image_path'])) {
                $imageFile = __DIR__ . '/../' . $item['image_path'];
                if (file_exists($imageFile)) {
                    @unlink($imageFile);
                }
            }
            return ['success' => true, 'message' => 'Inventory item deleted successfully'];
        } else {
            return ['success' => false, 'message' => 'Inventory item not found or already deleted'];
        }
        
    } catch (PDOException $e) {
        error_log("Delete inventory item error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to delete inventory item'];
    }
}

/**
 * Get inventory statistics for a farmer
 * @param int $farmerId
 * @return array
 */
function getInventoryStats($farmerId) {
    $pdo = getDatabaseConnection();
    if (!$pdo) {
        return ['success' => false, 'stats' => []];
    }
    
    try {
        $stats = [];
        
        // Total items
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM inventory WHERE farmer_id = ?");
        $stmt->execute([$farmerId]);
        $stats['total_items'] = $stmt->fetch()['total'] ?? 0;
        
        // Items in stock
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM inventory WHERE farmer_id = ? AND status = 'in_stock'");
        $stmt->execute([$farmerId]);
        $stats['in_stock'] = $stmt->fetch()['total'] ?? 0;
        
        // Low stock items
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM inventory WHERE farmer_id = ? AND status = 'low_stock'");
        $stmt->execute([$farmerId]);
        $stats['low_stock'] = $stmt->fetch()['total'] ?? 0;
        
        // Out of stock items
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM inventory WHERE farmer_id = ? AND status = 'out_of_stock'");
        $stmt->execute([$farmerId]);
        $stats['out_of_stock'] = $stmt->fetch()['total'] ?? 0;
        
        // Categories count
        $stmt = $pdo->prepare("SELECT category, COUNT(*) as count FROM inventory WHERE farmer_id = ? GROUP BY category");
        $stmt->execute([$farmerId]);
        $stats['categories'] = $stmt->fetchAll();
        
        return ['success' => true, 'stats' => $stats];
        
    } catch (PDOException $e) {
        error_log("Get inventory stats error: " . $e->getMessage());
        return ['success' => false, 'stats' => []];
    }
}

/**
 * Get detailed inventory reports
 * @param int $farmerId
 * @return array
 */
function getInventoryReports($farmerId) {
    $pdo = getDatabaseConnection();
    if (!$pdo) {
        return ['success' => false, 'reports' => []];
    }
    
    try {
        $reports = [];
        
        // Get all items for calculations
        $stmt = $pdo->prepare("SELECT * FROM inventory WHERE farmer_id = ?");
        $stmt->execute([$farmerId]);
        $allItems = $stmt->fetchAll();
        
        // Total inventory value (if prices are set)
        $totalValue = 0;
        $itemsWithPrice = 0;
        foreach ($allItems as $item) {
            if ($item['price'] && $item['quantity'] > 0) {
                $totalValue += floatval($item['price']) * floatval($item['quantity']);
                $itemsWithPrice++;
            }
        }
        $reports['total_value'] = $totalValue;
        $reports['items_with_price'] = $itemsWithPrice;
        
        // Category breakdown with quantities
        $stmt = $pdo->prepare("SELECT category, COUNT(*) as count, SUM(quantity) as total_quantity FROM inventory WHERE farmer_id = ? GROUP BY category ORDER BY count DESC");
        $stmt->execute([$farmerId]);
        $reports['category_breakdown'] = $stmt->fetchAll();
        
        // Status breakdown
        $stmt = $pdo->prepare("SELECT status, COUNT(*) as count FROM inventory WHERE farmer_id = ? GROUP BY status");
        $stmt->execute([$farmerId]);
        $reports['status_breakdown'] = $stmt->fetchAll();
        
        // Low stock items (need attention)
        $stmt = $pdo->prepare("SELECT * FROM inventory WHERE farmer_id = ? AND status = 'low_stock' ORDER BY quantity ASC LIMIT 10");
        $stmt->execute([$farmerId]);
        $reports['low_stock_items'] = $stmt->fetchAll();
        
        // Out of stock items
        $stmt = $pdo->prepare("SELECT * FROM inventory WHERE farmer_id = ? AND status = 'out_of_stock' ORDER BY updated_at DESC LIMIT 10");
        $stmt->execute([$farmerId]);
        $reports['out_of_stock_items'] = $stmt->fetchAll();
        
        // Recent items (last 10 added)
        $stmt = $pdo->prepare("SELECT * FROM inventory WHERE farmer_id = ? ORDER BY created_at DESC LIMIT 10");
        $stmt->execute([$farmerId]);
        $reports['recent_items'] = $stmt->fetchAll();
        
        // Total quantity by status
        $stmt = $pdo->prepare("SELECT status, SUM(quantity) as total_quantity FROM inventory WHERE farmer_id = ? GROUP BY status");
        $stmt->execute([$farmerId]);
        $reports['quantity_by_status'] = $stmt->fetchAll();
        
        return ['success' => true, 'reports' => $reports];
        
    } catch (PDOException $e) {
        error_log("Get inventory reports error: " . $e->getMessage());
        return ['success' => false, 'reports' => []];
    }
}
?>

