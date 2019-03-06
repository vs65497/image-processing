<?php
/*
QRDetector
Finds a potential QR Code from an image for processing.
 A kind of *basic* image processor. Written for practice.
Von Simmons
10.18.2018
*/

// Detects edges of potential QR Codes
class QRDetector {
    private $src;
    private $pixels;
    
    private $edge_points = array();
    private $edge_lines = array();
    private $corners = array();
    private $center = array();
    
    private $polygon = array();
    
    private $cropped = array();
    
    // Thresholds:
        // If the average color of the pixel divided by 255
        //  is greater than this percentage, then posterize it
        //  white, else black.
        private $posterize_threshold = .5;

        // Threshold for potential edge points.
        private $edgepoint_threshold = 1;
    
        // Threshold for finding pixels outside of bounding box
        private $feather = 0;

    public function __construct($s) {
        $this->setSrc($s);
        $this->read();
        $this->findEdgePoints();
        $this->findEdgeLines();
        $this->selectShape();
        $this->polygon = $this->rotate($this->polygon, $this->center, 999);
        $this->cropped = $this->crop($this->polygon);
    }

    // Processes source and sets it.
    private function setSrc($s) {
        $this->src = $s;
    }

    // Returns a posterized version of the image as an array.
    private function read() {
        $src = $this->src;
        $img = imagecreatefromjpeg($src); // need to make filetype agnostic

        $size = getimagesize($src);
        $width = $size[0];
        $height = $size[1];

        $pixels = array();

        // Parse and Posterize image.
        //for($row=0;$row<1;$row++) {
        for($row=0;$row<$height;$row++) {
            $curRow = [];

            //for($col=0;$col<1;$col++) {
            for($col=0;$col<$width;$col++) {
                $rgb = imagecolorat($img, $col, $row);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;

                $readable = $this->posterize($r, $g, $b);

                $curRow[$col] = $readable;
            }

            $pixels[$row] = $curRow;
        }

        // Setting pixels
        $this->pixels = $pixels;
    }
    
    // Normalizes and converts pixel to display black or white.
    //  Returns Black = 1 | White = 0.
    private function posterize($r, $g, $b) {
        $threshold = $this->posterize_threshold;
        
        $average = (($r + $g + $b) / 3) / 255;
        $posterize = $average > $threshold ? false : true; // white : black
        
        return $posterize;
    }
    
    // Finds all points on a potential edge.
    //  Pushes all edge point coordinates to $edge_points.
    //  Stored as ( row, col ).
    private function findEdgePoints() {
        $pixels = $this->pixels;
        
        // Add one to account for checking the *edge*, not the pixel
        $threshold = $this->$edgepoint_threshold +1;
        
        $points = array();
        
        for($row=0;$row<count($pixels);$row++) {
            for($col=0;$col<count($pixels[0]);$col++) {
                $colorsx = 0;
                $colorsy = 0;
                
                // Check nearby pixels
                for($pt=(-1 * $threshold);$pt<=$threshold;$pt++) {
                    $testx = $pt + $col;
                    $testy = $pt + $row;
                    
                    // In x bounds?
                    if($testx > 0 && $testx < count($pixels[0])) { 
                        // Different color than the current pixel?
                        if($pixels[$row][$col] != $pixels[$row][$testx]) {
                            $colorsx++;
                        }
                    }
                    
                    // In y bounds?
                    if($testy > 0 && $testy < count($pixels)) { 
                        // Different color than the current pixel?
                        if($pixels[$row][$col] != $pixels[$testy][$col]) {
                            $colorsy++;
                        }
                    }
                }
                
                if(($colorsx == $threshold && $pixels[$row][$col] != $pixels[$row][$col -1]) || 
                ($colorsy == $threshold && $pixels[$row][$col] != $pixels[$row -1][$col])) {
                        array_push($points, array($row, $col));  
                }
            }
        }
        
        $this->edge_points = $points;
    }
    
    // Finds trends in edge points to suggest vectors
    private function findEdgeLines() {
        $pixels = $this->pixels;
        $edge = $this->edge_points;
        
        $left = array();
        $right = array();
        
        for($row=0;$row<count($pixels);$row++) {
            $first = null;
            $last = null;
            
            for($col=0;$col<count($pixels[0]);$col++) {
                $point = array($row, $col);
                
                if(!isset($first) && in_array($point, $edge)) {
                    $first = $point;
                }
                
                if(in_array($point, $edge)) {
                    $last = $point;
                } 
            }
            
            if(in_array(array($row -1, $first[1]), $edge) || 
               in_array(array($row +1, $first[1]), $edge)) {
                array_push($left, $first);   
            }
            
            if(in_array(array($row -1, $last[1]), $edge) || 
               in_array(array($row +1, $last[1]), $edge)) {
                array_push($right, $last);   
            }
            
        }
        
        // Needs some error checking
        
        // point values
        //    row                                 column
        $y1 = $left[0][0];                  $x1 = $left[0][1];                  // top left
        $y2 = $right[0][0];                 $x2 = $right[0][1];                 // top right
        $y3 = $right[count($right) -1][0];  $x3 = $right[count($right) -1][1];  // bottom right
        $y4 = $left[count($left) -1][0];    $x4 = $left[count($left) -1][1];    // bottom left
        
        // (row , col)
        $corners = array(
            array($y1, $x1), // top left
            array($y2, $x2), // top right
            array($y3, $x3), // bottom right
            array($y4, $x4) // bottom left
        );
        
        // Find center ($c_ = "center")
        $center = $this->intersectLines(
            $this->calculateLine($corners[3],$corners[1]),
            $this->calculateLine($corners[2],$corners[0])
        );
        
        // Top, Right, Bottom, Left
        $lines = array(
            "top"=>$this->calculateLine($corners[0], $corners[1]), 
            "right"=>$this->calculateLine($corners[1], $corners[2]), 
            "bottom"=>$this->calculateLine($corners[2], $corners[3]), 
            "left"=>$this->calculateLine($corners[3], $corners[0])
        );
        
        $this->center = $center;
        $this->corners = $corners;
        $this->edge_lines = $lines;
    }
    
    // Calculates the slop and y-intercept of a line given two points.
    //  Returns a line.
    private function calculateLine($p1, $p2) {
        // y = mx + b
        // m = (y1 - y0) / (x1 - x0)
        // b = x0
        // y = row, x = col
        
        $y1 = $p1[0];
        $x1 = $p1[1];
        
        $y2 = $p2[0];
        $x2 = $p2[1];
        
        $m = ($y2 - $y1) / ($x2 - $x1);
        $b = $y1 + (-1 * $m * $x1);
        
        return array(
            "m" => $m,
            "b" => $b
        );
    }
    
    // Intersects two lines.
    //  Returns a point.
    private function intersectLines($fx1, $fx2) {
        $m1 = $fx1["m"];
        $b1 = $fx1["b"];
        
        $m2 = $fx2["m"];
        $b2 = $fx2["b"];
        
        $x = ($b1 - $b2) / ($m2 - $m1);
        $y = ($m1 * $x) + $b1;
        
        return array($y, $x);
    }
    
    private function selectShape() {
        $pixels = $this->pixels;
        $lines = $this->edge_lines;
        $corners = $this->corners;
        $center = $this->center;
        $shape = array();
        
        for($row=0;$row<count($pixels);$row++) {
            for($col=0;$col<count($pixels[0]);$col++) {
                
                // Bounding box lines
                // y = mx + b
                $top = floor(($lines["top"]["m"] * $col) + $lines["top"]["b"]);
                $right = floor(($row - $lines["right"]["b"]) / $lines["right"]["m"]);
                $bottom = floor(($lines["bottom"]["m"] * $col) + $lines["bottom"]["b"]);
                $left = floor(($row - $lines["left"]["b"]) / $lines["left"]["m"]);
                
                $color = $pixels[$row][$col];
                
                if(
                    $row >= $top &&
                    $row <= $bottom &&
                    $col >= $left &&
                    $col <= $right
                ) {
                    $point = array($row, $col, $color);
                    array_push($shape, $point);
                }
                
            }
        }
        
        // Package the polygon with its defining characteristics
        $polygon = array(
            "shape"=>$shape,
            "bounds"=>$lines,
            "corners"=>$corners,
            "center"=>$center
        );
        
        $this->polygon = $polygon;
    }
    
    // Takes a shape and rotates it theta degrees around a given ref point.
    //  If theta is 999, the top line of the bounding box will be set to 
    //  m=0. The rotation is then based on this delta. Theta is in radians.
    //  I can see this being reused for multiple shapes and/or centers so
    //  this will have arguments sent to it.
    //  Returns a rotated shape.
    private function rotate($polygon, $ref, $theta) {
        if(abs($theta) > (2*pi()) && $theta != 999) return $polygon;
        
        $shape = $polygon["shape"];
        $bounds = $polygon["bounds"];
        $corners = $polygon["corners"];
        $center = $polygon["center"];
        
        $zero_y = floor($ref[0]); // row of the reference point
        $zero_x = floor($ref[1]); // col of the reference point
        
        // Flatten top
        if($theta == 999) {
            // Corners
            $high = ($corners[0][0] >= $corners[1][0])? 0:1;
            $low = ($corners[0][0] >= $corners[1][0])? 1:0;
            
            // Opposite (of theta). New: 2, Old: 1.
            $opposite2 = $zero_y - $corners[$high][0];
            $opposite1 = $zero_y - $corners[$low][0];
            
            // Adjecent (of theta)
            $adjecent = abs($zero_x - $corners[$c][1]);
            
            // Direction (clockwise / anticlockwise)
            //  If top left is higher then rotate the shape clockwise
            $direction = ($high == 0)? 1:-1;
            
            $delta = abs(atan($opposite2 / $adjecent) - atan($opposite1 / $adjecent)) * $direction;
            
            $theta = $delta;
        }
        
        // Rotate about reference point
        $new_shape = array();
        $new_corners = array();
        for($p=0;$p<count($shape);$p++) {
            $point = $shape[$p];
            
            $row = $point[0];
            $col = $point[1];
            $color = $point[2];
            
            // c^2 = a^2 + b^2
            $opposite = $zero_y - $row;
            $adjecent = $zero_x - $col;
            $radius = sqrt( pow($opposite, 2) + pow($adjecent, 2) );
            
            $angle = atan($opposite / $adjecent);
            $debug = '';
            
            if($opposite == 0 && $adjecent == 0) { // Origin
                $angle = 0; $debug = '(origin)';
                
            } else if($opposite > 0 && $adjecent == 0) { // North pole
                $angle -= pi(); $debug = '(north)';
            } else if($adjecent < 0 && $opposite == 0) { // East pole
                $angle -= 0; $debug = '(east)';
            } else if($opposite < 0 && $adjecent == 0) { // South pole
                $angle -= pi(); $debug = '(south)';
            } else if($adjecent > 0 && $opposite == 0) { // West pole
                $angle -= pi(); $debug = '(west)';
                
            } else if($adjecent < 0 && $opposite < 0) { // Quadrant IV
                $debug = '(IV)';
            } else if($adjecent > 0 && $opposite < 0) { // Quadrant III
                $angle -= pi(); $debug = '(III)';
            } else if($adjecent > 0 && $opposite > 0) { // Quadrant II
                $angle -= pi(); $debug = '(II)';
            } else if($adjecent < 0 && $opposite > 0) { // Quadrant I
                $debug = '(I)';
            }

            // Rotate pixel
            $sin = round($zero_y + (sin($angle + $theta) * $radius));
            $cos = round($zero_x + (cos($angle + $theta) * $radius));

            // If this pixel is in corners, rewrite
            if(in_array(array($row, $col), $corners)) {
                array_push($new_corners, array($sin, $cos));
            }
            
            $new_point = array(
                $sin,
                $cos,
                $color
            );
            
            //echo $debug.'{('.$row.','.$col.')->('.$sin.', '.$cos.'), rotate:'.($angle + $theta).', angle:'.$angle.', radius:'.$radius.', color: '.$color.'}<br />';
            
            array_push($new_shape, $new_point);
        }
        
        // Rewrite bounds using new corners
        $new_bounds = array(
            "top"=>$this->calculateLine($new_corners[0], $new_corners[1]), 
            "right"=>$this->calculateLine($new_corners[1], $new_corners[2]), 
            "bottom"=>$this->calculateLine($new_corners[2], $new_corners[3]), 
            "left"=>$this->calculateLine($new_corners[3], $new_corners[0])
        );
        
        $rotated = array(
            "shape"=>$new_shape,
            "bounds"=>$new_bounds,
            "corners"=>$new_corners,
            "center"=>$center
        );
        
        return $rotated;
    }
    
    // Creates a cropped image using the dimensions of a shape.
    //  Returns an array.
    private function crop($polygon) {
        $shape = $polygon["shape"];
        $image = array();
        
        // Find the width of the image after cropping out the shape
        $first_width = null;
        $last_width = 0;
        
        $first_height = null;
        $last_height = 0;
        
        for($p=0;$p<count($shape);$p++) {
            $point = $shape[$p];
            
            $row = $point[0];
            $col = $point[1];
            $color = $point[2];
            
            $first_width = (!isset($first_width) || $col < $first_width)? $col : $first_width;
            $last_width = ($col > $last_width)? $col : $last_width;
            
            $first_height = (!isset($first_height) || $row < $first_height)? $row : $first_height;
            $last_height = ($row > $last_height)? $row : $last_height;
            
            //echo '{'.$row.', '.$col.', '.$color.'}<br />';
        }
        
        $width = $last_width - $first_width;
        $height = $last_height - $first_height;
        
        //echo 'width: '.$width.', height: '.$height;
        
        for($r=0;$r<$height;$r++) {
            $row = array();
            
            for($c=0;$c<$width;$c++) {
                $col = 0;
                
                $black_pixel = array($r + $first_height, $c + $first_width, 1);
                $white_pixel = array($r + $first_height, $c + $first_width, 0);
                
                if(in_array($black_pixel, $shape)) {
                    $col = 1;
                } else if(in_array($white_pixel, $shape)) {
                    $col = 0;
                }
                
                array_push($row, $col);
            }
            
            array_push($image, $row);
        }
        
        return $image;
    }

    // Returns HTML version of the input.
    public function display() {
        $pixels = $this->pixels;
        $perimeter = $this->edge_points;
        $corners = $this->corners;
        $center = $this->center;
            $center[0] = floor($center[0]);
            $center[1] = floor($center[1]);
        $lines = $this->edge_lines;
        
        $html = '<div class="display">';
        
        // General data:
        $html .= '<p>';
        
            // Print center point
            $html .= 'center: ('.
                                //number_format($center[0], 3, '.', '').
                                $center[0].
                                ', '.
                                //number_format($center[1], 3, '.', '').
                                $center[1].
                            ')<br />';

            // Print formulas for lines
            $html .= 'boundaries:<br />';
            //for($l=0;$l<count($lines);$l++) {
            foreach($lines as $l => $fx) {
                $m = number_format($lines[$l]["m"], 3, '.', '');
                $b = number_format($lines[$l]["b"], 3, '.', '');
                $html .= 'y = '.$m.'x + '.$b.'<br />';
            }
        
        $html .= '</p>'; // end general data
        
        $display_point = '<span class="coords">{_, _}</span>';
        $html .= '<p><b>edge detection</b> | coordinates: '.$display_point.'</p>';

        // Print image with calculations
        for($row=0;$row<count($pixels);$row++) {
            $html .= '<div class="row">';

            for($col=0;$col<count($pixels[0]);$col++) {
                
                $pt = array($row, $col);
                $coords = '{'.$row.', '.$col.'}';
                $is_edge = in_array($pt, $perimeter)? ' edge':'';
                $is_corner = in_array($pt, $corners)? ' corner':'';
                $color = $pixels[$row][$col] == 1? 'black':'white';
                $bounds = '';
                $is_center = '';
                $info = '';
                
                // Set border class
                    $border1 = floor(($col * $lines["top"]["m"]) + $lines["top"]["b"]);
                    $border2 = floor(($row - $lines["right"]["b"]) / $lines["right"]["m"]);
                    $border3 = floor(($col * $lines["bottom"]["m"]) + $lines["bottom"]["b"]);
                    $border4 = floor(($row - $lines["left"]["b"]) / $lines["left"]["m"]);

                    if(
                        $row == $border1 ||
                        $col == $border2 ||
                        $row == $border3 ||
                        $col == $border4
                    ) {
                        $bounds = ' bounds'; 
                    }
                
                // Set center point
                if($row == $center[0] && $col == $center[1]) {
                    $is_center = ' center';
                }
                
                /*// For debugging
                $info = $info . '{row = '.$row.' | col = '.$col.'} ';
                $info .= $border1 . ', ';
                $info .= $border2 . ', ';
                $info .= $border3 . ', ';
                $info .= $border4 . ', ';
                */
                
                $classes = $color.$is_edge.$is_corner.$bounds.$is_center;
                $html .= '<div class="pixel '.$classes.'" coords="'.$coords.'">'.$info.'</div>';
            }
            
            $html .= '</div>'; // end of row
        }
        
        $html .= '</div>'; // end of display
        
        // Display shape on its own
        $html .= '<h2>cropped and rotated shape</h2>';
        
        //$s = $this->polygon["shape"];
        
        $shape = $this->cropped;
        
        $shape_html = '<div class="display shape">';
        for($row=0;$row<count($shape);$row++) {
            $shape_html .= '<div class="row">';
            
            for($col=0;$col<count($shape[$row]);$col++) {
                
                $coords = '{'.$row.', '.$col.'}';
                $color = $shape[$row][$col] == 1? 'black':'white';
                $info = '';
                
                $shape_html .= '<div class="pixel '.$color.'" coords="'.$coords.'">'.$info.'</div>';
                
            }
            $shape_html .= '</div>'; // end of row
        }
        $shape_html .= '</div>'; // end of display
        
        $html .= $shape_html; // append shape html
        
        
        return $html;
    }
    
} // end of class
?>