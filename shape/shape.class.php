<?php
require_once '../edge/edge2.class.php';
require_once '../vector.class.php';

class ShapeDetector extends EdgeDetector {
    private $lines = array();
    private $shapes = array();
    
    public function __construct($source_file) {
        parent::__construct($source_file);
        
        $this->lines = $this->get_posterized();
        $objects = $this->find();
        $this->shapes = $this->describe($objects);
    }
    
    public function get_shapes() {
        return $this->shapes;
    }
    
    private function find() {
        $image = $this->lines; // processed image
        $objects = array();
        
        for($i=0;$i<count($image);$i++) {
            for($j=0;$j<count($image[$i]);$j++) {
                $value = $image[$i][$j];
                
                if($value == 255) {
                    $o = $this->trace($i, $j);
                    
                    if(count($o) > 0) array_push($objects, $o);
                }
            }
        }
        
        return $objects;
    }
    
    private function trace($r, $c) {
        $image = $this->lines; // processed image
        $object = array();
        
        $pixel = $image[$r][$c];
        
        $x = 0;
        $y = 0;
        
        while(
            !is_null($pixel) && 
            !in_array($pixel, $object) && 
            $pixel == 255
        ) {
            
            array_push($object, array(
                'x' => $r,
                'y' => $c,
                'z' => 0
            ));
            
            $x += $r;
            $y += $c;
            
            // overwrite image to prevent duplicate tracing
            $image[$r][$c] = 254;
            $this->lines[$r][$c] = 254;
            
            // east
            if($image[$r+1][$c] == 255) {
                $r += 1;
                //$c = ;
            
            // south
            } else if($image[$r][$c+1] == 255) {
                //$r = ;
                $c += 1;
            
            // west
            } else if($image[$r-1][$c] == 255) {
                $r -= 1;
                //$c = ;
            
            // north
            } else if($image[$r][$c-1] == 255) {
                //$r = ;
                $c -= 1;
                
            // south-east
            } else if($image[$r+1][$c+1] == 255) {
                $r += 1;
                $c += 1;
            
            // south-west
            } else if($image[$r-1][$c+1] == 255) {
                $r -= 1;
                $c += 1;
            
            // north-west
            } else if($image[$r-1][$c-1] == 255) {
                $r -= 1;
                $c -= 1;
            
            // north-east
            } else if($image[$r+1][$c-1] == 255) {
                $r += 1;
                $c -= 1;
            }
            
            $pixel = $image[$r][$c];
        }
        
        if(count($object) > 0) {
            $items = count($object);
            $centroid = array(
                'x' => round($x / $items),
                'y' => round($y / $items),
                'z' => 0
            );
            
            return array(
                'center' => $centroid,
                'points' => $object
            );
        }
    }
    
    private function describe($objects) {
        for($i=0;$i<count($objects);$i++) {
            $obj = $objects[$i];
            
            $corners = $this->find_corners($obj);
            $vertex = $this->find_vertex($corners, $obj);
            $type = count($vertex);
            
            $objects[$i]['vertex'] = $vertex;
            $objects[$i]['type'] = $type;
        }
        
        return $objects;
    }
    
    private function find_corners($obj) {
        $center = $obj['center'];
        $furthest = $center;
        $f = new Vector($center, $center);

        $threshold = 3;
        $gap = 0;
        $corners = array(array());
        // finds corners
        for($j=0;$j<count($obj['points']);$j++) {
            $p = $obj['points'][$j];

            $v0 = new Vector($center, $furthest);
            $v1 = new Vector($center, $p);

            $d0 = round($v0->length());
            $d1 = round($v1->length());

            // new furthest
            if($d1 > $d0) {
                $furthest = $p;
                $corners = array(array($p)); // reset

            // matches furthest OR
            // less than furthest, but still in threshold
            } else if(($d1 == $d0) ||
                     (($d1 < $d0) && ($d1+$threshold >= $d0))
            ) {
                if($gap > $threshold) {
                    array_push($corners, array());
                }

                $length = count($corners)-1;
                array_push($corners[$length], $p);

                $gap = 0;

            } else {
                $gap++;
            }
        }
        
        return $corners;
    }
    
    private function find_vertex($corners, $obj) {
        $center = $obj['center'];
        $vertex = array();
        for($v=0;$v<count($corners);$v++) {

            $furthest = $center;
            for($x=0;$x<count($corners[$v]);$x++) {
                $p = $corners[$v][$x];

                $v0 = new Vector($center, $furthest);
                $v1 = new Vector($center, $p);

                $d0 = ($v0->length());
                $d1 = ($v1->length());

                if($d1 > $d0) {
                    $furthest = $p;
                }
            }
            array_push($vertex, $furthest);
        }
        return $vertex;
    }
    
    public function display() {
        $html = '<div class="display">';
        
        $html .= $this->show_info();
        
        for($i=0;$i<count($this->shapes);$i++) {
        //for($i=0;$i<1;$i++) {
            $html .= '<h2>object '.($i+1).'</h2>';
            $html .= $this->show($this->shapes[$i]);
        }
        
        return $html.'</div>';
    }
    
    private function show($obj) {
        $image = $this->lines;
        $center = $obj['center'];
        $vertex = $obj['vertex'];
        $points = $obj['points'];
        
        $html = '<div class="image">';
        
        for($i=0;$i<count($image);$i++) {
            $html .= '<div class="row">';
            
            for($j=0;$j<count($image[$i]);$j++) {
                $c = $image[$i][$j];
                $pixel = array(
                    'x' => $i,
                    'y' => $j,
                    'z' => 0
                );
                
                $coords = '{'.$i.', '.$j.'}: ';
                if($pixel == $center) {
                    $style = 'background-color: lightgreen;';
                    $coords .= 'CENTER';
                    
                } else if(in_array($pixel, $vertex)) {
                    $style = 'background-color: lightgreen;';
                    $coords .= 'VERTEX';
                    
                } else if(in_array($pixel, $points)) {
                    $style = 'background-color: red;';
                    $coords .= 'border';
                    
                } else {
                    $style = 'background-color: rgba('.$c.','.$c.','.$c.',1);';
                    $coords .= (number_format($c,0));
                }
                
                $html .= '<div class="pixel" style="'.$style.'" coords="'.$coords.'"></div>';
            }
            $html .= '</div>';
        }
        
        return $html .'</div>';
    }
    
    private function show_info() {
        $html .= '<ul>';
        
        $s = $this->shapes;
        for($i=0;$i<count($s);$i++) {
            $obj = $s[$i];
            
            $points = 'points: '.count($obj['points']);
            $center = 'center: ('.$obj['center']['x'].', '.$obj['center']['y'].')';
            $type = 'type: '.$obj['type'];
            
            $html .= '<li>'.($i+1).' -- '.$center.', '.$points.', '.$type.'</li>';
        }
        
        $html .= '</ul>';
        
        return $html;
    }
}
?>