<?php
namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

class ServerMonitor {
    public static function getCpuUsage() {
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            return $load[0] * 100; // Convert to percentage
        }
        return 0;
    }
    
    public static function getMemoryUsage() {
        $memory = memory_get_usage(true);
        $memoryMax = memory_get_peak_usage(true);
        return [
            'current' => $memory / 1024 / 1024, // MB
            'peak' => $memoryMax / 1024 / 1024 // MB
        ];
    }
}