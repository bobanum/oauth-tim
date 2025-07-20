<?php

namespace Auth\Trait;

trait Debug {
    static public $fileName = 'debug.txt';
    static public function vd() {
        echo "<pre>\n";
        echo str_repeat("\u{2501}", 80) . "\n";
        echo sprintf("%s::%s:<b>%s</b>\n", debug_backtrace()[1]['class'] ?? debug_backtrace()[0]['file'], debug_backtrace()[1]['function'], debug_backtrace()[0]['line']);
        foreach (func_get_args() as $arg) {
            echo str_repeat("\u{2500}", 80) . "\n";
            var_export($arg);
            echo "\n";
        }
        echo str_repeat("\u{2501}", 80) . "\n";
        echo "</pre>";
    }
    static public function vdd() {
        echo "<pre>\n";
        echo str_repeat("\u{2501}", 80) . "\n";
        echo sprintf("%s::%s:<b>%s</b>\n", debug_backtrace()[1]['class'] ?? debug_backtrace()[0]['file'], debug_backtrace()[1]['function'], debug_backtrace()[0]['line']);
        foreach (func_get_args() as $arg) {
            echo str_repeat("\u{2500}", 80) . "\n";
            var_export($arg);
            echo "\n";
        }
        echo str_repeat("\u{2501}", 80) . "\n";
        echo "</pre>";
        die;
    }
    static public function vdj() {
        header('Content-Type: application/json');
        [$caller, $callee] = debug_backtrace();
        $result = [];
        $result['file'] = $caller['file'];
        $result['line'] = $caller['line'];
        $result['function'] = $callee['function'];
        if (!empty($callee['class'])) {
            $result['function'] = $callee['class'] . "::" . $result['function'];
        }
        $dump = func_get_args();
        $dump = array_combine(array_map(fn($i) => "var " . $i, array_keys($dump)), $dump);
        $result += $dump;
        // array_push($result, ...$dump);
        // $result['dump'] = func_get_args();
        echo json_encode($result, JSON_PRETTY_PRINT);
        die;
    }
    static public function vdf() {
        // var_dump($_SERVER);die;
        $file = $_SERVER['DOCUMENT_ROOT'] . '/' . self::$fileName;
        header('Content-Type: application/json');
        $bt = debug_backtrace();
        $caller = $bt[0];
        $callee = $bt[1] ?? null;
        $result = [];
        $caller['file'] = str_replace([$_SERVER['DOCUMENT_ROOT'], dirname($_SERVER['DOCUMENT_ROOT'])], '', $caller['file']);
        $caller['file'] = str_replace('\\', '/', $caller['file']);
        $caller['file'] = trim($caller['file'], '/');
        $result['file'] = sprintf("%s (line %d)", $caller['file'], $caller['line']);
        $dump = func_get_args();
        if ($callee !== null) {
            $fn = '';
            if (!empty($callee['class'])) {
                $fn .= $callee['class'] . "::";
            }
            if (!empty($callee['function'])) {
                $fn .= $callee['function'];
            }
            if ($fn) {
                $result['function'] = $fn;
            }
        }
        $dump = array_combine(array_map(fn($i) => "var " . $i, array_keys($dump)), $dump);
        $result += $dump;
        // array_push($result, ...$dump);
        // $result['dump'] = func_get_args();
        $result = json_encode($result, JSON_PRETTY_PRINT);
        $result .= "\n" . str_repeat("\u{2501}", 80) . "\n";
        file_put_contents($file, $result, FILE_APPEND);
        // die("999");
    }
    static public function vdf_reset() {
        $file = $_SERVER['DOCUMENT_ROOT'] . '/' . self::$fileName;
        if (file_exists($file)) {
            unlink($file);
        }
    }
}
