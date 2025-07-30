<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\ApiLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ApiRequestLogger
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        $startTime = microtime(true);
        $response = $next($request);
        $endTime = microtime(true);

        try {
            ApiLog::create([
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'headers' => json_encode($request->headers->all()),
                'body' => json_encode($request->all()),
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'status_code' => $response->getStatusCode(),
                'response_body' => json_encode(json_decode($response->getContent(), true)),
                'requested_at' => Carbon::createFromTimestamp($startTime),
                'responded_at' => Carbon::createFromTimestamp($endTime),
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to log API request: ' . $e->getMessage(), ['request_url' => $request->fullUrl()]);
        }

        return $response;
    }
}
