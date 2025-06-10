<?php
if (!isset($pageTitle)) {
    $pageTitle = "TaskLance";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { margin: 0; font-family: 'Segoe UI', Arial, sans-serif; background: #f4f4f4;}
        header {
            background: #007bff;
            color: #fff;
            padding: 16px 36px 14px 36px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .main-title {
            font-size: 2em;
            margin: 0;
            font-family: 'Segoe UI', Arial, sans-serif;
            letter-spacing: 1.5px;
        }
        nav a {
            color: #fff;
            text-decoration: none;
            margin-left: 28px;
            font-size: 1.08em;
            font-weight: bold;
            transition: text-decoration 0.2s, color 0.2s;
        }
        nav a:hover {
            color: #ffe066;
            text-decoration: underline;
        }
        @media (max-width: 650px) {
            header { flex-direction: column; align-items: flex-start; }
            nav { margin-top: 10px; }
        }
    </style>
</head>
<body>
<header>
    <span class="main-title">TaskLance</span>
</header>