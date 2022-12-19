<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
    <meta name="robots" content="noindex,nofollow"/>
    <style type="text/css">
        <?php include('_styles.css'); ?>
    </style>
</head>
<body>
<div id="sf_resetcontent" class="sf-reset">
    <h1><?php echo !empty($message) ? htmlspecialchars($message) : 'Whoops, looks like something went wrong.'; ?></h1>
    <?php if (!empty($exception)): ?>
        <pre class="block_exception"><?php echo htmlspecialchars($exception); ?></pre>
    <?php endif; ?>
</div>
</body>
</html>
