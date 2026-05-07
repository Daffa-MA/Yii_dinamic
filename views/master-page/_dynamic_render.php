<?php
$this->title = isset($pageTitle) ? $pageTitle : 'Halaman';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $this->title ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen py-8">
    <div class="container mx-auto px-4 max-w-4xl">
        <div id="dynamic-content">
            <!-- Content will be rendered by JavaScript -->
        </div>
    </div>
</body>
</html>