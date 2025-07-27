<?php

namespace Auth\Trait;

trait Debug {
    static public $width = 80;
    static public $fileName = 'debug.txt';
    static public function format($caller, $callee, ...$args) {
        $seps = ["―", "═", "■", ];
        $seps = array_map(fn($s) => str_repeat($s, self::$width), $seps);
        $result[] = $seps[2];
        $result[] = self::formatHeader($caller, $callee);
        $result[] = $seps[1];
        foreach ($args as $arg) {
            $result[] = var_export($arg, true);
            $result[] = $seps[0];
        }
        // $result[] = $seps[2];
        return implode("\n", $result);
    }
    static public function vd() {
        $result = ['<pre>', self::format(debug_backtrace()[0], debug_backtrace()[1], ...func_get_args()), '</pre>'];
        echo implode("\n", $result);
    }
    static public function vdd() {
        $result = ['<pre>', self::format(debug_backtrace()[0], debug_backtrace()[1], ...func_get_args()), '</pre>'];
        echo implode("\n", $result);
        die;
    }
    static public function vdj() {
        header('Content-Type: application/json');
        $result = [];
        $result['file'] = self::formatHeader(...debug_backtrace());

        $dump = func_get_args();
        $dump = array_combine(array_map(fn($i) => "var " . $i, array_keys($dump)), $dump);
        $result += $dump;
        echo json_encode($result, JSON_PRETTY_PRINT);
        die;
    }
    static function trimLeft($string1, $string2) {
        while ($string1 && $string2 && $string1[0] === $string2[0]) {
            $string1 = substr($string1, 1);
            $string2 = substr($string2, 1);
        }
        return $string1;
    }
    static function formatHeader($caller, $callee) {
        $file = trim(self::trimLeft($caller['file'], $_SERVER['DOCUMENT_ROOT']), '\\/');
        // $result .= ' ' . basename($file) . ' (' . dirname($file) . ')';
        $result = sprintf("%s:%d (%s)", basename($file), $caller['line'], $file);
        if ($callee !== null) {
            $fn = '';
            if (!empty($callee['class'])) {
                $fn .= $callee['class'] . "::";
            }
            if (!empty($callee['function']) && $callee['function'] !== 'include') {
                $fn .= $callee['function'];
            }
            if ($fn) {
                $result .= ' (' . $fn . ')';
            }
        }
        $date = date('m-d H:i:s');
        $date = "[$date]";
        $result = str_pad($result, self::$width - strlen($date), ' ', STR_PAD_RIGHT) . $date;
        return $result;
    }
    static public function vdf(...$args) {
        $output = $_SERVER['DOCUMENT_ROOT'] . '/' . self::$fileName;
        $bt = debug_backtrace();
        $result = self::format(...array_slice($bt, 0, 2), ...$args) . "\n";
        file_put_contents($output, $result, FILE_APPEND);
    }
    static public function vdf_reset() {
        $file = $_SERVER['DOCUMENT_ROOT'] . '/' . self::$fileName;
        if (file_exists($file)) {
            unlink($file);
        }
    }
}
