<?php 
    include('./qrdetector.class.php');

    // Global variables
        $src = '../media/testsquare2.jpg';
        $amp = 5; // display multiplier

        $qrcode = new QRDetector($src);
?>
<!doctype html>
<html>
    <head>
        <meta charset="utf-8" />
        <meta lang="en" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        
        <title>Edge Detector v1.0</title>
        
        <link href="../styles.css" type="text/css" rel="stylesheet" />
        <style type="text/css">
            .row { height:<?php echo $amp; ?>px; }
            .pixel {
                width:<?php echo $amp; ?>px;
                height:<?php echo $amp; ?>px;
            }
        </style>
    </head>
    <body>
        <h1>Edge Detector version 1.0</h1>
        <hr />
        <div id="content">
            <h2>original image</h2><p>
            <img class="origin" src="<?php echo $src; ?>" /></p>
            <h2>calculations</h2>
            <?php echo $qrcode->display(); ?>
            <br /><br />
        </div>
        
        <script src="../main.js" type="module"></script>
    </body>
</html>