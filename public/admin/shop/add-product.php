<?php
require_once __DIR__ . '/../../../includes/bootstrap.php';
Auth::requireLogin();

$categories = Database::fetchAll("SELECT * FROM shop_categories ORDER BY type, name");
$isEdit = !empty($_GET['id']);
$productId = $isEdit ? (int)($_GET['id'] ?? 0) : 0;
$product = null;

if ($isEdit) {
    $product = Database::fetchOne("SELECT * FROM products WHERE id = ?", [$productId]);
    if (!$product) {
        flash('error', 'Product not found.');
        redirect(SITE_URL . '/admin/shop/index.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $type = $_POST['type'] ?? ($product['type'] ?? 'pot');
        $data = [
            'category_id' => (int)($_POST['category_id'] ?? 0) ?: null,
            'name'         => trim($_POST['name'] ?? ''),
            'description'  => trim($_POST['description'] ?? ''),
            'price'        => is_numeric($_POST['price'] ?? '') ? (float)$_POST['price'] : null,
            'type'         => $type,
            'status'       => $_POST['status'] ?? 'available',
            'dimensions'   => trim($_POST['dimensions'] ?? ''),
            'technique'    => trim($_POST['technique'] ?? ''),
            'quantity'     => (int)($_POST['quantity'] ?? 1),
            'pod_provider' => $type === 'merch' ? ($_POST['pod_provider'] ?? null) : null,
            'pod_product_url'=> trim($_POST['pod_product_url'] ?? '') ?: null,
            'pod_product_id' => trim($_POST['pod_product_id'] ?? '') ?: null,
            'external_url'  => trim($_POST['external_url'] ?? '') ?: null,
            'sort_order'   => (int)($_POST['sort_order'] ?? 0),
        ];

        if (empty($data['name'])) throw new RuntimeException('Name is required.');

        if (!empty($_FILES['image']['name'])) {
            $upload = ImageUpload::upload($_FILES['image'], 'products');
            $data['image_path'] = $upload['path'];
        } elseif ($isEdit && !empty($product['image_path'])) {
            $data['image_path'] = $product['image_path'];
        }

        if ($isEdit) {
            Database::update('products', $data, 'id = :id', ['id' => $productId]);
            flash('success', 'Product updated!');
            redirect(SITE_URL . '/admin/shop/edit-product.php?id=' . $productId);
        }

        Database::insert('products', $data);
        flash('success', 'Product added!');
        redirect(SITE_URL . '/admin/shop/index.php');
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$formData = $_POST + ($product ?? []);
$selectedType = $formData['type'] ?? 'pot';
$currentImagePath = $product['image_path'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $isEdit ? 'Edit Product' : 'Add Product' ?> — Admin</title>
    <link rel="stylesheet" href="/admin/css/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400;1,700&family=Caveat:wght@400;600&family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body>
<?php include __DIR__ . '/../partials/sidebar.php'; ?>
<main class="admin-main">
    <?php include __DIR__ . '/../partials/topbar.php'; ?>
    <div class="admin-content">
        <div class="admin-page-header">
            <h1><?= $isEdit ? 'Edit Shop Product' : 'Add Shop Product' ?></h1>
            <a href="/admin/shop/index.php" class="admin-btn">← Back</a>
        </div>

        <?php if (!empty($error)): ?>
        <div class="alert alert--error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="admin-form">
            <!-- Product Type Tabs -->
            <div class="type-tabs">
                <label class="type-tab <?= $selectedType === 'pot' ? 'active' : '' ?>">
                    <input type="radio" name="type" value="pot" <?= $selectedType === 'pot' ? 'checked' : '' ?>>
                    🏺 Original Pot
                </label>
                <label class="type-tab <?= $selectedType === 'merch' ? 'active' : '' ?>">
                    <input type="radio" name="type" value="merch" <?= $selectedType === 'merch' ? 'checked' : '' ?>>
                    👕 Merch (Print-on-Demand)
                </label>
            </div>

            <div class="form-grid">
                <div class="form-group form-group--full">
                    <label>Product Name *</label>
                    <input type="text" name="name" required value="<?= e($formData['name'] ?? '') ?>"
                           placeholder="e.g. Hand-thrown Celadon Bowl">
                </div>
                <div class="form-group form-group--full">
                    <label>Description</label>
                    <textarea name="description" rows="3"><?= e($formData['description'] ?? '') ?></textarea>
                </div>
                <div class="form-group">
                    <label>Category</label>
                    <select name="category_id">
                        <option value="">— None —</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= (string)($formData['category_id'] ?? '') === (string)$cat['id'] ? 'selected' : '' ?>>
                            <?= e($cat['name']) ?> (<?= e($cat['type']) ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Price ($)</label>
                    <input type="number" name="price" step="0.01" min="0"
                           value="<?= e($formData['price'] ?? '') ?>" placeholder="0.00">
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status">
                        <option value="available" <?= ($formData['status'] ?? 'available') === 'available' ? 'selected' : '' ?>>Available</option>
                        <option value="sold" <?= ($formData['status'] ?? '') === 'sold' ? 'selected' : '' ?>>Sold</option>
                        <option value="coming_soon" <?= ($formData['status'] ?? '') === 'coming_soon' ? 'selected' : '' ?>>Coming Soon</option>
                    </select>
                </div>

                <!-- Pot-only fields -->
                <div class="form-group pot-only">
                    <label>Dimensions</label>
                    <input type="text" name="dimensions" value="<?= e($formData['dimensions'] ?? '') ?>" placeholder="e.g. 12cm H">
                </div>
                <div class="form-group pot-only">
                    <label>Technique</label>
                    <input type="text" name="technique" value="<?= e($formData['technique'] ?? '') ?>">
                </div>
                <div class="form-group pot-only">
                    <label>Quantity Available</label>
                    <input type="number" name="quantity" value="<?= e($formData['quantity'] ?? '1') ?>" min="0">
                </div>

                <!-- Merch-only fields -->
                <div class="form-group merch-only">
                    <label>Print-on-Demand Provider</label>
                    <select name="pod_provider">
                        <option value="">— Select —</option>
                        <option value="printful" <?= ($formData['pod_provider'] ?? '') === 'printful' ? 'selected' : '' ?>>Printful ✓ (your provider)</option>
                        <option value="printify" <?= ($formData['pod_provider'] ?? '') === 'printify' ? 'selected' : '' ?>>Printify</option>
                        <option value="other" <?= ($formData['pod_provider'] ?? '') === 'other' ? 'selected' : '' ?>>Other</option>
                    </select>
                </div>
                <div class="form-group merch-only form-group--full">
                    <div class="tip-box">
                        <strong>Printful tip:</strong> In your Printful dashboard, go to <em>Stores → My Products</em>, open a product, and copy the product's URL or your store's checkout URL. Paste it below so customers are sent directly to that product on Printful/your storefront.
                    </div>
                </div>
                <div class="form-group merch-only form-group--full">
                    <label>Printful Product URL *</label>
                    <input type="url" name="pod_product_url" value="<?= e($formData['pod_product_url'] ?? '') ?>"
                           placeholder="https://your-store.printful.me/products/...">
                </div>
                <div class="form-group merch-only">
                    <label>Product ID (optional)</label>
                    <input type="text" name="pod_product_id" value="<?= e($formData['pod_product_id'] ?? '') ?>">
                </div>

                <!-- Both -->
                <div class="form-group form-group--full">
                    <label>External Link (override buy button URL)</label>
                    <input type="url" name="external_url" value="<?= e($formData['external_url'] ?? '') ?>"
                           placeholder="Alternative URL if not using POD provider link">
                </div>

                <div class="form-group form-group--full">
                    <label>Product Image<?= $isEdit ? ' (leave empty to keep current image)' : '' ?></label>
                    <input type="file" name="image" accept="image/*">
                    <?php if (!empty($currentImagePath)): ?>
                    <div style="margin-top:.75rem;">
                        <img src="/uploads/<?= e($currentImagePath) ?>" alt="Current product image" class="admin-table__thumb">
                    </div>
                    <?php endif; ?>
                </div>
                <div class="form-group">
                    <label>Sort Order</label>
                    <input type="number" name="sort_order" value="<?= e($formData['sort_order'] ?? '0') ?>">
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="admin-btn admin-btn--primary"><?= $isEdit ? 'Save Changes' : 'Add Product' ?></button>
                <a href="/admin/shop/index.php" class="admin-btn">Cancel</a>
            </div>
        </form>
    </div>
</main>
<script src="/admin/js/admin.js"></script>
<script>
// Show/hide pot vs merch fields based on type
function updateTypeFields() {
    const type = document.querySelector('input[name="type"]:checked')?.value;
    document.querySelectorAll('.pot-only').forEach(el => el.style.display = type === 'pot' ? '' : 'none');
    document.querySelectorAll('.merch-only').forEach(el => el.style.display = type === 'merch' ? '' : 'none');
    document.querySelectorAll('.type-tab').forEach(el => el.classList.remove('active'));
    if (type) {
        const tab = document.querySelector(`input[value="${type}"]`)?.closest('.type-tab');
        if (tab) tab.classList.add('active');
    }
}
document.querySelectorAll('input[name="type"]').forEach(r => r.addEventListener('change', updateTypeFields));
updateTypeFields();
</script>
</body>
</html>
