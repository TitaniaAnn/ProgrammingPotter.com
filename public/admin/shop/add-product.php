<?php
require_once __DIR__ . '/../../../includes/bootstrap.php';
Auth::requireLogin();

$hasVisibilityColumn = false;
try {
    $visibilityColumn = Database::fetchOne(
        "SELECT COLUMN_NAME
         FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = 'products'
           AND COLUMN_NAME = 'is_visible'
         LIMIT 1"
    );
    $hasVisibilityColumn = !empty($visibilityColumn);

    if (!$hasVisibilityColumn) {
        try {
            Database::query("ALTER TABLE products ADD COLUMN is_visible TINYINT(1) NOT NULL DEFAULT 1 AFTER status");
            $hasVisibilityColumn = true;
        } catch (Exception $e) {
            // Keep the page usable if DB user cannot alter schema.
        }
    }
} catch (Exception $e) {
    // Keep the page usable; visibility controls are hidden if schema can't be checked.
}

Database::query(
    "CREATE TABLE IF NOT EXISTS product_images (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        image_path TEXT NOT NULL,
        image_thumb TEXT NULL,
        sort_order INT DEFAULT 0,
        is_primary TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY idx_product_sort (product_id, sort_order),
        CONSTRAINT fk_product_images_product
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
);

function productImageThumbPath(?string $path): ?string {
    if (empty($path)) {
        return null;
    }

    $dir = dirname($path);
    $file = basename($path);

    return ($dir === '.' ? '' : $dir . '/') . 'thumb_' . $file;
}

function syncProductPrimaryImage(int $productId): void {
    $primaryImage = Database::fetchOne(
        "SELECT * FROM product_images
         WHERE product_id = ?
         ORDER BY is_primary DESC, sort_order ASC, id ASC
         LIMIT 1",
        [$productId]
    );

    Database::query("UPDATE product_images SET is_primary = 0 WHERE product_id = ?", [$productId]);

    if ($primaryImage) {
        Database::query("UPDATE product_images SET is_primary = 1 WHERE id = ?", [$primaryImage['id']]);
        Database::query("UPDATE products SET image_path = ? WHERE id = ?", [$primaryImage['image_path'], $productId]);
        return;
    }

    Database::query("UPDATE products SET image_path = NULL WHERE id = ?", [$productId]);
}

function backfillLegacyProductImage(array $product): void {
    if (empty($product['id']) || empty($product['image_path'])) {
        return;
    }

    $existingCount = Database::fetchOne(
        "SELECT COUNT(*) AS cnt FROM product_images WHERE product_id = ?",
        [$product['id']]
    );

    if (($existingCount['cnt'] ?? 0) > 0) {
        return;
    }

    Database::insert('product_images', [
        'product_id' => $product['id'],
        'image_path' => $product['image_path'],
        'image_thumb' => productImageThumbPath($product['image_path']),
        'sort_order' => 0,
        'is_primary' => 1,
    ]);
}

$categories = Database::fetchAll("SELECT * FROM shop_categories ORDER BY type, name");
$isEdit = !empty($_GET['id']);
$productId = $isEdit ? (int)($_GET['id'] ?? 0) : 0;
$product = null;
$existingImages = [];

if ($isEdit) {
    $product = Database::fetchOne("SELECT * FROM products WHERE id = ?", [$productId]);
    if (!$product) {
        flash('error', 'Product not found.');
        redirect(SITE_URL . '/admin/shop/index.php');
    }

    backfillLegacyProductImage($product);
    $existingImages = Database::fetchAll(
        "SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order ASC, id ASC",
        [$productId]
    );
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

        if ($hasVisibilityColumn) {
            $data['is_visible'] = isset($_POST['is_visible']) ? 1 : 0;
        }

        if (empty($data['name'])) throw new RuntimeException('Name is required.');

        $newUploads = [];
        if (!empty($_FILES['images']['name'][0])) {
            $files = $_FILES['images'];
            $count = count($files['name']);
            for ($index = 0; $index < $count; $index++) {
                if ($files['error'][$index] !== UPLOAD_ERR_OK || empty($files['name'][$index])) {
                    continue;
                }

                $file = [
                    'name' => $files['name'][$index],
                    'type' => $files['type'][$index],
                    'tmp_name' => $files['tmp_name'][$index],
                    'error' => $files['error'][$index],
                    'size' => $files['size'][$index],
                ];

                $newUploads[] = ImageUpload::upload($file, 'products');
            }
        }

        if ($isEdit) {
            Database::update('products', $data, 'id = :id', ['id' => $productId]);
            $finalId = $productId;
        } else {
            $finalId = Database::insert('products', $data);
        }

        if (!empty($newUploads)) {
            $sortSeedRow = Database::fetchOne(
                "SELECT COALESCE(MAX(sort_order), -1) AS max_sort FROM product_images WHERE product_id = ?",
                [$finalId]
            );
            $sortOrder = (int)($sortSeedRow['max_sort'] ?? -1) + 1;

            $imageCountRow = Database::fetchOne(
                "SELECT COUNT(*) AS cnt FROM product_images WHERE product_id = ?",
                [$finalId]
            );
            $isFirstImage = ((int)($imageCountRow['cnt'] ?? 0) === 0);

            foreach ($newUploads as $upload) {
                Database::insert('product_images', [
                    'product_id' => $finalId,
                    'image_path' => $upload['path'],
                    'image_thumb' => $upload['thumb'],
                    'sort_order' => $sortOrder++,
                    'is_primary' => $isFirstImage ? 1 : 0,
                ]);
                $isFirstImage = false;
            }
        }

        $primaryImageId = (int)($_POST['primary_image_id'] ?? 0);
        if ($primaryImageId > 0) {
            Database::query("UPDATE product_images SET is_primary = 0 WHERE product_id = ?", [$finalId]);
            Database::query(
                "UPDATE product_images SET is_primary = 1 WHERE id = ? AND product_id = ?",
                [$primaryImageId, $finalId]
            );
        }

        syncProductPrimaryImage($finalId);

        if ($isEdit) {
            flash('success', 'Product updated!');
            redirect(SITE_URL . '/admin/shop/edit-product.php?id=' . $productId);
        }

        flash('success', 'Product added!');
        redirect(SITE_URL . '/admin/shop/edit-product.php?id=' . $finalId);
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$formData = $_POST + ($product ?? []);
$selectedType = $formData['type'] ?? 'pot';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $isEdit ? 'Edit Product' : 'Add Product' ?> — Admin</title>
    <link rel="stylesheet" href="/admin/css/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400;1,700&family=Caveat:wght@400;600&family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        .img-gallery { display: flex; flex-wrap: wrap; gap: .75rem; margin-top: 1rem; }
        .img-gallery-item { position: relative; width: 130px; border-radius: 8px; overflow: hidden; border: 2px solid var(--cream-dk); }
        .img-gallery-item.is-primary { border-color: var(--clay); }
        .img-gallery-item img { width: 100%; height: 110px; object-fit: cover; display: block; }
        .img-labels { padding: .3rem .4rem; background: var(--cream); display: flex; align-items: center; justify-content: space-between; gap: .3rem; }
        .primary-indicator { font-size: .6rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: var(--clay); }
        .set-primary-btn { font-size: .65rem; color: var(--ash); background: none; border: 1px solid var(--cream-dk); border-radius: 4px; padding: .1rem .35rem; cursor: pointer; }
        .set-primary-btn:hover { border-color: var(--clay); color: var(--clay); }
        .delete-img-btn { position: absolute; top: 4px; right: 4px; background: rgba(0,0,0,.65); color: #fff; border: none; width: 22px; height: 22px; border-radius: 50%; font-size: .85rem; cursor: pointer; display: flex; align-items: center; justify-content: center; }
        .delete-img-btn:hover { background: #c0392b; }
        .new-preview-item { width: 130px; border-radius: 8px; overflow: hidden; border: 2px dashed var(--clay); }
        .new-preview-item img { width: 100%; height: 110px; object-fit: cover; display: block; }
        .new-badge { background: rgba(212,168,32,.9); color: #fff; font-size: .6rem; font-weight: 700; text-align: center; padding: .25rem; letter-spacing: .06em; text-transform: uppercase; }
        .upload-note { margin-top: .5rem; color: var(--ash); display: block; }
    </style>
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

        <form method="POST" enctype="multipart/form-data" class="admin-form" id="productForm">
            <input type="hidden" name="primary_image_id" id="primaryImageId" value="">

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
                <?php if ($hasVisibilityColumn): ?>
                <div class="form-group">
                    <label>Storefront Visibility</label>
                    <label style="display: inline-flex; align-items: center; gap: .5rem; margin-top: .25rem;">
                        <input type="checkbox" name="is_visible" value="1"
                               <?= (string)($formData['is_visible'] ?? '1') === '1' ? 'checked' : '' ?>>
                        Show this product in the public shop
                    </label>
                </div>
                <?php endif; ?>

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
                    <label>Product Images</label>
                    <input type="file" name="images[]" id="imageInput" accept="image/*" multiple>
                    <small class="upload-note">Upload one or more images. The first image becomes the cover until you change it.</small>

                    <?php if (!empty($existingImages)): ?>
                    <div class="img-gallery" id="existingGallery">
                        <?php foreach ($existingImages as $image): ?>
                        <div class="img-gallery-item <?= $image['is_primary'] ? 'is-primary' : '' ?>" data-img-id="<?= $image['id'] ?>">
                            <img src="/uploads/<?= e($image['image_thumb'] ?? $image['image_path']) ?>" alt="">
                            <button type="button" class="delete-img-btn" onclick="deleteImage(<?= $image['id'] ?>, <?= $productId ?>)" title="Delete image">×</button>
                            <div class="img-labels">
                                <?php if ($image['is_primary']): ?>
                                <span class="primary-indicator">★ Cover</span>
                                <?php else: ?>
                                <button type="button" class="set-primary-btn" onclick="setPrimary(<?= $image['id'] ?>)">Set cover</button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php elseif ($isEdit): ?>
                    <p class="upload-note">No product images yet.</p>
                    <?php endif; ?>

                    <div class="img-gallery" id="newPreviews"></div>
                    <?php if (!$isEdit): ?>
                    <p class="upload-note">After saving, you can set the cover image or remove individual images.</p>
                    <?php else: ?>
                    <p class="upload-note">Cover selection and individual delete actions are available for saved images.</p>
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
const primaryImageInput = document.getElementById('primaryImageId');
const imageInput = document.getElementById('imageInput');
const previewContainer = document.getElementById('newPreviews');

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

function setPrimary(imageId) {
    primaryImageInput.value = imageId;
    document.querySelectorAll('.img-gallery-item').forEach((item) => {
        item.classList.remove('is-primary');

        const label = item.querySelector('.img-labels');
        if (!label) {
            return;
        }

        if (parseInt(item.dataset.imgId, 10) === imageId) {
            item.classList.add('is-primary');
            label.innerHTML = '<span class="primary-indicator">★ Cover</span>';
        } else {
            label.innerHTML = `<button type="button" class="set-primary-btn" onclick="setPrimary(${item.dataset.imgId})">Set cover</button>`;
        }
    });
}

async function deleteImage(imageId, productId) {
    if (!confirm('Delete this image?')) {
        return;
    }

    const response = await fetch(`/admin/shop/delete_image.php?img_id=${imageId}&product_id=${productId}`);
    const result = await response.json();

    if (!result.success) {
        alert(result.error || 'Unable to delete image.');
        return;
    }

    const imageCard = document.querySelector(`.img-gallery-item[data-img-id="${imageId}"]`);
    if (imageCard) {
        imageCard.remove();
    }

    if (primaryImageInput.value === String(imageId)) {
        primaryImageInput.value = '';
    }
}

function updateNewPreviews() {
    previewContainer.innerHTML = '';

    Array.from(imageInput.files || []).forEach((file) => {
        const reader = new FileReader();
        reader.onload = (event) => {
            const card = document.createElement('div');
            card.className = 'new-preview-item';
            card.innerHTML = `<img src="${event.target.result}" alt="New image preview"><div class="new-badge">New upload</div>`;
            previewContainer.appendChild(card);
        };
        reader.readAsDataURL(file);
    });
}

document.querySelectorAll('input[name="type"]').forEach(r => r.addEventListener('change', updateTypeFields));
imageInput.addEventListener('change', updateNewPreviews);
updateTypeFields();
</script>
</body>
</html>
