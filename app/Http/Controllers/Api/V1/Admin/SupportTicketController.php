<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SupportTicketRequest;
use App\Models\SupportTicket;
use App\Services\SupportTicketService;
use Illuminate\Http\Request;

class SupportTicketController extends Controller
{
    protected SupportTicketService $service;
    public function __construct(SupportTicketService $service)
    {
        $this->service = $service;
    } 
    // Submit support ticket from mobile app or frontend
    public function store(SupportTicketRequest $request)
    {
        $data = $request->validated();
        
        $responce = $this->service->create($data);
        
        return response_success('Support ticket submitted successfully.', $responce);
    }

    // Admin: List all support tickets with pagination
    public function index()
    {
        $tickets = $this->service->getAll();
        return response_success('Support tickets fetched successfully.', $tickets);
    }

    // Admin: Update ticket status (e.g., open -> resolved)
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:open,pending,resolved'
        ]);

        $ticket = SupportTicket::findOrFail($id);
        $ticket->update(['status' => $request->status]);

        return response_success("Ticket status updated to {$request->status} successfully.", $ticket);
    }
}
