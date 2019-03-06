<?php
class EdgeDetector {
    private $src;
    private $memory = array();
    private $output = array();
    
    private $kernel_types = array(
            'box_blur' => array(
                array(1,1,1),
                array(1,1,1),
                array(1,1,1),
            ),
            'gaussian_blur_3x3' => array(
                array(1,2,1),
                array(2,4,2),
                array(1,2,1),
            ),
            'sharpen' => array(
                array(0,-1,0),
                array(-1,5,-1),
                array(0,-1,0),
            ),
            'edge_detection_1' => array(
                array(1,0,-1),
                array(0,0,0),
                array(-1,0,1)
            ),
            'edge_detection_2' => array(
                array(0,1,0),
                array(1,-4,1),
                array(0,1,0)
            ),
            'edge_detection_3' => array(
                array(-1,-1,-1),
                array(-1,8,-1),
                array(-1,-1,-1)
            )
        );
    private $kernel;
    
    private $display_set = array();
    private $default_filter_stack = array(
        'darken' => .5,
        'contrast' => 200,
        'gaussian' => false,
        'edge2' => false,
        'posterize' => 60
    );
    
    public function __construct($source_file, $filter_stack) {
        $this->src = $source_file;
        if(!$filter_stack) {
            $filter_stack = $this->default_filter_stack;
        }
        
        $m = $this->read();
        $this->memory = $m;
        
        $this->output = $this->process($filter_stack);
    }
    
    public function get_edges() {
        return $this->output;
    }
    
    public function clear_display() {
        $this->display_set = array();
    }
    
    public function process($stack) {
        $m = $this->memory;
        
        $order = 1;
        $display = array();
        foreach($stack as $filter => $option) {
            
            switch($filter) {
                case 'darken':
                    $option = (!is_null($option))? $option:.5;
                    $m = $this->adjust($m, 'darken', $option);
                    $message = 'darkened by '.$option;
                    break;
                    
                case 'lighten':
                    $option = (!is_null($option))? $option:.5;
                    $m = $this->adjust($m, 'lighten', $option);
                    $message = 'lightened by '.$option;
                    break;
                    
                case 'contrast':
                    $option = (!is_null($option))? $option:200;
                    $m = $this->adjust($m, 'contrast', $option);
                    $message = 'contrasted by '.$option;
                    break;
                    
                case 'posterize':
                    $option = (!is_null($option))? $option:60;
                    $m = $this->posterize($m, $option);
                    $message = 'posterized by '.$option;
                    break;
                    
                case 'gaussian':
                    $m = $this->filter($m, $this->kernel_types['gaussian_blur_3x3']);
                    $message = 'gaussian blur 3x3';
                    break;
                    
                case 'edge1':
                    $m = $this->filter($m, $this->kernel_types['edge_detection_1']);
                    $message = 'edge detection kernel 1';
                    break;
                    
                case 'edge2':
                    $m = $this->filter($m, $this->kernel_types['edge_detection_2']);
                    $message = 'edge detection kernel 2';
                    break;
                    
                case 'edge3':
                    $m = $this->filter($m, $this->kernel_types['edge_detection_3']);
                    $message = 'edge detection kernel 3';
                    break;
                    
                case 'box':
                    $m = $this->filter($m, $this->kernel_types['box_blur']);
                    $message = 'box blur';
                    break;
                    
                case 'sharpen':
                    $m = $this->filter($m, $this->kernel_types['sharpen']);
                    $message = 'sharpened';
                    break;
            }
            
            if(!is_null($message)) {
                $message = $order.') '.$message;
                $order++;
                array_push($display, array($m, $message));
            }
        }
        
        $this->display_set = $display;
        return $m;
    }
    
    private function read() {
        $src = $this->src;
        $img = imagecreatefromjpeg($src); // need to make filetype agnostic

        $size = getimagesize($src);
        $width = $size[0];
        $height = $size[1];

        $pixels = array();

        for($row=0;$row<$height;$row++) {
            $curRow = [];

            for($col=0;$col<$width;$col++) {
                $rgb = imagecolorat($img, $col, $row);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;

                $curRow[$col] = $this->grayscale($r, $g, $b);
            }

            $pixels[$row] = $curRow;
        }

        return $pixels;
    }
    
    private function grayscale($r, $g, $b) {
        $i = 0.2126;
        $j = 0.7152;
        $k = 0.0722;
        
        $r = number_format($r,4);
        $g = number_format($g,4);
        $b = number_format($b,4);
        
        return ($i * $r) + ($j * $g) + ($k * $b);
    }
    
    private function posterize($m, $threshold) {
        $poster = array();
        
        for($i=0;$i<count($m);$i++) {
            
            $p = array();
            for($j=0;$j<count($m[$i]);$j++) {
                $value = (number_format($m[$i][$j],0) > $threshold)? 255:0;
                array_push($p, $value);
            }
            array_push($poster, $p);
        }
        
        return $poster;
    }
    
    private function adjust($m, $operation, $option) {
        $adjusted = array();
        for($i=0;$i<count($m);$i++) {
            
            $a = array();
            for($j=0;$j<count($m[$i]);$j++) {
                
                $value = $m[$i][$j];
                switch($operation) {
                    case 'lighten':
                        $value = 255 - ($option * (255 - $value));
                        break;
                    case 'darken':
                        $value *= $option;
                        break;
                    case 'contrast':
                        $factor = (259 * ($option + 255)) / (255 * (259 - $option));
                        $value *= $factor;
                }
                
                array_push($a, $value);
            }
            array_push($adjusted, $a);
        }
        
        return $adjusted;
    }
    
    private function filter($m, $kernel) {
        $size = count($kernel);
        $filtered = array();
        
        // iterate over memory
        for($i=0;$i<count($m);$i++) {
            
            $f = array();
            for($j=0;$j<count($m[$i]);$j++) {
                
                $value = 0;
                $average = 0;
                
                // iterate over kernel
                for($k=0;$k<count($kernel);$k++) {
                    for($n=0;$n<count($kernel[$k]);$n++) {
                        
                        $row = $i + $k;
                        $col = $j + $n;
                        
                        // image edge behaviour
                        if(is_null($m[$row][$col])) {
                            //continue;
                            $value += 0;
                            $average += 1;
                            
                        } else {
                            // matrix convolution
                            $value += ($m[$row][$col] * $kernel[$k][$n]);
                            $average += $kernel[$k][$n];   
                        }
                    }
                }
                
                $average = ($average == 0)? 1:$average;
                $final = ($value / $average);
                $final = ($final < 0)? 0:$final;
                $final = ($final > 255)? 255:$final;
                array_push($f, $final);
            }
            if(count($f) > 0) array_push($filtered, $f);
        }
        
        return $filtered;
    }
    
    public function display() {
        $html = '<div class="display">';
        
        $set = $this->display_set;
        for($i=0;$i<count($set);$i++) {
            $html .= '<h2>'.$set[$i][1].'</h2>';
            $html .= $this->show($set[$i][0]);
        }
        
        return $html .'</div>';
    }
    
    private function show($m) {
        $html = '<div class="image">';
        
        for($i=0;$i<count($m);$i++) {
            $html .= '<div class="row">';
            
            for($j=0;$j<count($m[$i]);$j++) {
                $c = $m[$i][$j];
                
                $coords = '{'.$i.', '.$j.'}: '.(number_format($c,0));
                $style = 'background-color: rgba('.$c.','.$c.','.$c.',1);';
                $html .= '<div class="pixel" style="'.$style.'" coords="'.$coords.'"></div>';
            }
            $html .= '</div>';
        }
        
        return $html .'</div>';
    }
}
?>