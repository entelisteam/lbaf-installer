<?php
/**
 * Минимальный каркас страницы.
 * @var string $title
 * @var string $content уже отрендеренный html
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?></title>
</head>
<body>
<?= $content ?>
</body>
</html>
