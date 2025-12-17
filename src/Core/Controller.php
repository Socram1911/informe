<?php

namespace App\Core;

class Controller
{
    public function render($view, $params = [])
    {
        // Simple view engine
        foreach ($params as $key => $value) {
            $$key = $value;
        }
        ob_start();
        include __DIR__ . "/../View/$view.php";
        return ob_get_clean();
    }
}
