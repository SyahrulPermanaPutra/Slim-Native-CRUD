<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PerformanceModel extends Model
{
    protected $table = 'performance_metrics';
    protected $fillable = [
        'endpoint',
        'response_time',
        'throughput',
        'error_rate',
        'cpu_usage',
        'memory_usage',
        'concurrent_users',
        'jmeter_threads',
        'test_duration',
        'timestamp'
    ];
    
    public $timestamps = false;
}