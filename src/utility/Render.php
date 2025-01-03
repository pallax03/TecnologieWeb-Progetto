<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <meta name="format-detection" content="telephone=no">
    <title><?php echo $title ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" type="text/css" href="/resources/css/style.css? <?php echo date('l jS \of F Y h:i:s A'); ?>">
    <?php 
        if(isset($style) && !empty($style)) {
            foreach ($style as $s) {
                echo '<link rel="stylesheet" type="text/css" href="/resources/css/' . $s . '.css?'. date('l jS \of F Y h:i:s A') . '">';
            }
        }
         
    ?>
    <script src="/resources/js/init.js"></script>

    <!-- <script   src="https://code.jquery.com/jquery-3.7.1.slim.min.js"   integrity="sha256-kmHvs0B+OpCW5GVHUNjv9rOmY0IvSIRcf7zGUDTDQM8="   crossorigin="anonymous"></script> -->
</head>
<body>
    <!-- <script src="/resources/js/main.js"></script> -->
    <?php include COMPONENTS . '/header.php' ?>
    
    <?php include COMPONENTS . '/nav.php' ?>

    <main id="main-content" aria-hidden="false">
        <?php 
            if (isset($page) && file_exists($page)) {
                include $page;
            } else {
                include PAGES . 'error.php';
            }
        ?>
    </main>

    <section id="sec-search_content" class="search-content" aria-hidden="true">
    </section>
    
    <?php include COMPONENTS . '/footer.php' ?>

    <script src="/resources/js/main.js"></script>
    <? if(!Session::isSuperUser()):?>
        <script src="/resources/js/search.js"></script>
        <script src="/resources/js/cards.js"></script>
    <? endif;?>
</body>
</html>