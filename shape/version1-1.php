<?php 
    require_once './shape-1.1.class.php';

    $src = '../media/testsquare3.jpg';
    $amp = 5; // display magnification

    $shapes = new ShapeDetector($src, false);
?>
<!doctype html>
<html>
    <head>
        <meta charset="utf-8" />
        <meta lang="en" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        
        <title>Shape Detector v1.1</title>
        
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
        <h1>Shape Detector version 1.1</h1>
        <hr />
        <div id="content">
            <h2>original image</h2><p>
            <img class="origin" src="<?php echo $src; ?>" /></p>
            <?php echo $shapes->display(); ?>
            <br /><br />
        </div>
        <div id="coords" class="coords">{_, _}</div>
        
        <script src="../main.js" type="module"></script>
    </body>
</html>