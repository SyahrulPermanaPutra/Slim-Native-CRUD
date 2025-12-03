<?php
namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

class MetricsMiddleware
{
    // Update MetricsMiddleware
    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        $startTime = microtime(true);

        $response = $handler->handle($request);

        $endTime = microtime(true);
        $responseTime = ($endTime - $startTime) * 1000;

        // Get server metrics (fallback using built-in functions)
        $cpuUsage = $this->getCpuUsage();
        $memoryUsage = [
            'current' => memory_get_usage(true) / 1024 / 1024,
            'peak' => memory_get_peak_usage(true) / 1024 / 1024,
        ];

        // Store metrics in database or persistent storage (placeholder)
        $this->storeMetrics($request, $responseTime, $cpuUsage, $memoryUsage);

        return $response
            ->withHeader('X-Response-Time', round($responseTime, 2) . 'ms')
            ->withHeader('X-CPU-Usage', round($cpuUsage, 2) . '%')
            ->withHeader('X-Memory-Current', round($memoryUsage['current'], 2) . 'MB')
            ->withHeader('X-Memory-Peak', round($memoryUsage['peak'], 2) . 'MB');
    }

    private function getCpuUsage(): float
    {
        $load = sys_getloadavg();
        return isset($load[0]) ? (float) $load[0] : 0.0;
    }

    private function storeMetrics(Request $request, float $responseTime, float $cpuUsage, array $memoryUsage): void
    {
        // TODO: persist/store metrics (DB, logger, etc.). Left empty as a safe default.
    }
}