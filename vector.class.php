<?php
class Vector {
    private $v;
    public $from;
    public $to;
    //private $length;
    
    public function __construct($from, $to) {
        $this->from = $from;
        $this->to = $to;
        //$this->length = $this->dot($this->from, $this->to);
        
        $this->v = $this->set_vector($this->from, $this->to);
    }
    
    private function set_vector($a, $b) {
        return array(
            'x' => $b['x'] - $a['x'],
            'y' => $b['y'] - $a['y'],
            'z' => $b['z'] - $a['z']
        );
    }
    
    public static function hamilton($a, $b) {
        return array(
            'w' => ($a['w'] * $b['w']) - ($a['x'] * $b['x']) - ($a['y'] * $b['y']) - ($a['z'] * $b['z']),
            'x' => ($a['w'] * $b['x']) + ($a['x'] * $b['w']) + ($a['y'] * $b['z']) - ($a['z'] * $b['y']),
            'y' => ($a['w'] * $b['y']) - ($a['x'] * $b['z']) + ($a['y'] * $b['w']) + ($a['z'] * $b['x']),
            'z' => ($a['w'] * $b['z']) + ($a['x'] * $b['y']) - ($a['y'] * $b['x']) + ($a['z'] * $b['w'])
        );
    }

    public static function cross($a, $b) {
        return array(
            'x' => ($a['y'] * $b['z']) - ($a['z'] * $b['y']),
            'y' => ($a['z'] * $b['x']) - ($a['x'] * $b['z']),
            'z' => ($a['x'] * $b['y']) - ($a['y'] * $b['x'])
        );
    }
    
    public static function dot($a, $b) {
        return ($a['x'] * $b['x']) + ($a['y'] * $b['y']) + ($a['z'] * $b['z']);
    }

    public static function add($a, $b) {
        return array(
            'x' => $a['x'] + $b['x'],
            'y' => $a['y'] + $b['y'],
            'z' => $a['z'] + $b['z']
        );
    }

    // subtract b from a. (a - b)
    public static function subtract($a, $b) {
        return array(
            'x' => $a['x'] - $b['x'],
            'y' => $a['y'] - $b['y'],
            'z' => $a['z'] - $b['z']
        );
    }

    // scale a by b. (b * a)
    public static function scale($a, $b) {
        return array(
            'x' => $a['x'] * $b,
            'y' => $a['y'] * $b,
            'z' => $a['z'] * $b
        );
    }
    
    public static function rotate($vector, $origin, $angle, $axis) {
        $angle = $angle / 2;

        $p = array(
            'w' => 0,
            'x' => $vector['x'] - $origin['x'],
            'y' => $vector['y'] - $origin['y'],
            'z' => $vector['z'] - $origin['z']
        );

        $r = array(
            'w' => cos($angle),
            'x' => sin($angle) * $axis['i'],
            'y' => sin($angle) * $axis['j'],
            'z' => sin($angle) * $axis['k']
        );

        $r1 = array(
            'w' => $r['w'],
            'x' => -1 * $r['x'],
            'y' => -1 * $r['y'],
            'z' => -1 * $r['z']
        );

        $p1 = $this->hamilton($this->hamilton($r, $p), $r1);

        return array(
            'x' => $p1['x'] + $origin['x'],
            'y' => $p1['y'] + $origin['y'],
            'z' => $p1['z'] + $origin['z']
        );
    }
    
    public function length() {
        $vector = $this->subtract($this->to, $this->from);
        $a = $vector['x'];
        $b = $vector['y'];
        $c = $vector['z'];
        
        /*
        print_r($vector);
        echo '<br />';
        echo pow($a,2).' + '.pow($b,2).' + '.pow($c,2);
        echo '<br />';
        */
        
        return sqrt(pow($a,2) + pow($b,2) + pow($c,2));
    }
}
?>