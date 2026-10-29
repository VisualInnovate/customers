<?php

namespace OrganizationModules\Slack\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use OrganizationModules\Slack\Services\DataService;

class SlackController extends Controller
{
    protected $slackyService;
    
    public function __construct(DataService $slackyService)
    {
        $this->slackyService = $slackyService;
    }

    public function store(Request $request)
    {
        $data = $this->slackyService->storeSlackyData(session()->get('current_organization'), $request);
        return response()->json($data);
    }

    public function handle(Request $request, $id = null)
    {
        
    }
}
