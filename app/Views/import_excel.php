<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Excel File</title>
</head>
<body>
    <h1>Import Excel File</h1>
    
    <?php if (session()->getFlashdata('error')) : ?>
        <div style="color: red;">
            <?= session()->getFlashdata('error') ?>
        </div>
    <?php endif; ?>

    <form action="<?= base_url('excel/upload') ?>" method="post" enctype="multipart/form-data">
        <input type="file" name="excel_file" required>
        <button type="submit">Upload</button>
    </form>
</body>
</html>
