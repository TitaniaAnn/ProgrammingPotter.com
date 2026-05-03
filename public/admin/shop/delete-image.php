<?php
require_once __DIR__ . '/../../../includes/bootstrap.php';
require_once __DIR__ . '/../../../includes/ImageDeleteHandler.php';
Auth::requireLogin();
csrf_verify();

header('Content-Type: application/json');

$result = ImageDeleteHandler::delete([
    'imageId'           => (int) ($_GET['img_id'] ?? 0),
    'parentId'          => (int) ($_GET['product_id'] ?? 0),
    'imagesTable'       => 'product_images',
    'parentIdColumn'    => 'product_id',
    'parentTable'       => 'products',
    'parentThumbColumn' => null,
    'blockLastImage'    => false,
]);

echo json_encode($result);
