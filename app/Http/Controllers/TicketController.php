<?php

namespace App\Http\Controllers;

use App\Http\Requests\TicketFormRequest;
use App\Models\Ticket;
use App\Services\LiveTicketService;
use App\Services\TicketService;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    //
    public function __construct(private TicketService $ticketService, private LiveTicketService $liveTicketService)
    {
    }

    public function store(TicketFormRequest $request)
    {
        return $this->ticketService->store($request);
    }

    public function update(TicketFormRequest $request, Ticket $ticket)
    {
        return $this->ticketService->update($ticket, $request->all());
    }

    public function delete(Ticket $ticket)
    {
        return $this->ticketService->delete($ticket);
    }

    public function get(Ticket $ticket)
    {
        return response()->json($ticket, 200);
    }

    public function all()
    {
        return response()->json(Ticket::all(), 200);
    }

    public function paginate(Request $request)
    {
        return $this->ticketService->paginate($request);
    }

    public function getTicket(TicketFormRequest $ticketFormRequest)
    {
        return $this->ticketService->getTicket($ticketFormRequest);
    }

    public function betList(TicketFormRequest $ticketFormRequest)
    {
        return $this->ticketService->betList($ticketFormRequest);
    }

    public function betListById(Request $request)
    {
        return $this->ticketService->betListById($request);
    }

    public function betListDetails(TicketFormRequest $ticketFormRequest)
    {
        return $this->ticketService->betListDetails($ticketFormRequest);
    }

    public function checkTicketStatus(Request $request)
    {
        return $this->ticketService->checkTicketStatus($request);
    }

    public function fetchBettingReport(TicketFormRequest $ticketFormRequest)
    {
        return $this->ticketService->fetchBettingReport($ticketFormRequest);
    }

    public function fetchBettingReportByCustomerId(TicketFormRequest $ticketFormRequest)
    {
        return $this->ticketService->fetchBettingReportByCustomerId($ticketFormRequest);
    }

    public function fetchBettingReportByRefNumber(TicketFormRequest $ticketFormRequest)
    {
        return $this->ticketService->fetchBettingReportByRefNumber($ticketFormRequest);
    }

    public function fetchBettingReportByDate(TicketFormRequest $ticketFormRequest)
    {
        return $this->ticketService->fetchBettingReportByDate($ticketFormRequest);
    }

    public function fetchWinningReport(TicketFormRequest $ticketFormRequest)
    {
        return $this->ticketService->fetchWinningReport($ticketFormRequest);
    }

    public function paginateLiveTickets(Request $request)
    {
        return $this->liveTicketService->paginate($request);
    }
}
