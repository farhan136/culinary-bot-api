<?php

namespace App\Http\Controllers;

use App\Models\ApiLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse; // Import JsonResponse

class ApiLogController extends Controller
{
    /**
     * Display a listing of the API logs.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        // You can add pagination, filtering, or sorting based on request parameters
        $perPage = $request->input('per_page', 20); // Default 20 items per page
        $logs = ApiLog::orderBy('requested_at', 'desc')->paginate($perPage);

        // Return the paginated logs as a JSON response
        return response()->json($logs);
    }
}