<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\PerformanceModel;

class PerformanceController
{
    private $performanceModel;

    public function __construct($database) {
        $this->performanceModel = new PerformanceModel($database);
    }

    public function index(Request $request, Response $response): Response
    {
        try {
            $page = $request->getQueryParams()['page'] ?? 1;
            $limit = 50;
            $offset = ($page - 1) * $limit;
            
            $metrics = $this->performanceModel->getAll($limit, $offset);
            $total = $this->performanceModel->count();
            $totalPages = ceil($total / $limit);
            
            $view = \Slim\Views\Twig::fromRequest($request);
            return $view->render($response, 'performance.twig', [
                'metrics' => $metrics,
                'currentPage' => $page,
                'totalPages' => $totalPages,
                'total' => $total
            ]);
        } catch (\Exception $e) {
            $response->getBody()->write("Error: " . $e->getMessage());
            return $response->withStatus(500);
        }
    }

    public function show(Request $request, Response $response, array $args): Response
    {
        $id = $args['id'];
        $metric = $this->performanceModel->find($id);
        
        if (!$metric) {
            $response->getBody()->write(json_encode(['error' => 'Metric not found']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
        
        $response->getBody()->write(json_encode($metric));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function store(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        
        $result = $this->performanceModel->create([
            'endpoint' => $data['endpoint'] ?? '/test',
            'response_time' => $data['response_time'] ?? 0,
            'throughput' => $data['throughput'] ?? 0,
            'error_rate' => $data['error_rate'] ?? 0,
            'cpu_usage' => $data['cpu_usage'] ?? 0,
            'memory_usage' => $data['memory_usage'] ?? 0,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        
        if ($result) {
            $response->getBody()->write(json_encode(['message' => 'Metric created']));
            return $response->withStatus(201)->withHeader('Content-Type', 'application/json');
        } else {
            $response->getBody()->write(json_encode(['error' => 'Failed to create metric']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    public function delete(Request $request, Response $response, array $args): Response
    {
        $id = $args['id'];
        $result = $this->performanceModel->delete($id);
        
        if ($result) {
            $response->getBody()->write(json_encode(['message' => 'Metric deleted']));
            return $response->withHeader('Content-Type', 'application/json');
        } else {
            $response->getBody()->write(json_encode(['error' => 'Metric not found']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
    }

    public function stats(Request $request, Response $response): Response
    {
        $stats = $this->performanceModel->getStats();
        $response->getBody()->write(json_encode($stats));
        return $response->withHeader('Content-Type', 'application/json');
    }
}