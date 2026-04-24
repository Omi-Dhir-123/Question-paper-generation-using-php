<?php
$file = $_GET['file'] ?? '';

if (!$file || !file_exists($file)) {
    die("File not found");
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>PDF Preview</title>
    <style>
        body {
            margin: 0;
            font-family: Arial;
            background: #f4f6f9;
        }

        .top-bar {
            padding: 15px;
            background: #2563eb;
            text-align: center;
        }

        .top-bar a {
            background: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            color: #2563eb;
        }

        iframe {
            width: 100%;
            height: 90vh;
            border: none;
        }
    </style>
</head>
<body>

<div class="top-bar">
    <a href="<?php echo $file; ?>" download>Download PDF</a>
</div>

<iframe src="<?php echo $file; ?>"></iframe>

</body>
</html>