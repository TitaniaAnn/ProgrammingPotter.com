<?php
require_once __DIR__ . '/../../../includes/bootstrap.php';
require_once __DIR__ . '/../../../includes/ImageDeleteHandler.php';
Auth::requireLogin();
csrf_verify();

header('Content-Type: application/json');

$result = ImageDeleteHandler::delete([
    'imageId'           => (int) ($_GET['img_id'] ?? 0),
    'parentId'          => (int) ($_GET['piece_id'] ?? 0),
    'imagesTable'       => 'pottery_images',
    'parentIdColumn'    => 'pottery_id',
    'parentTable'       => 'pottery',
    'parentThumbColumn' => 'image_thumb',
    'blockLastImage'    => true,
]);

echo json_encode($result);
