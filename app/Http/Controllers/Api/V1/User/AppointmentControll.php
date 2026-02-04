<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\AppointmentRequest;
use App\Http\Resources\AppointmentResource;
use App\Models\User;
use App\Models\UserAppointment;
use App\Services\UserAppointmentService;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class AppointmentControll extends Controller
{
    protected UserAppointmentService $appointment;
    use AuthorizesRequests;
    public function __construct(UserAppointmentService $appointmentService)
    {
        $this->appointment = $appointmentService;
        $this->authorizeResource(UserAppointment::class, 'appointment');
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $qc = function ($query) {
            $query->where('user_id', auth()->id());
            if (request()->has('date')) {
                $query->whereDate('appointment_at', request()->date);
            }
        };
        $appointments = $this->appointment->getAll($qc);
        if ($appointments->isEmpty()) {
            return response()->json([
                'ok' => false,
                'message' => 'No appointments found.',
            ], 404);
        }
        return AppointmentResource::collection($appointments)->additional([
            'ok' => true,
            'message' => 'Appointments retrieved successfully.',
        ]);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(AppointmentRequest $request)
    {
        $data = $request->validated();
        $data['user_id'] = auth()->id();
        $appointment = $this->appointment->create($data);
        return AppointmentResource::make($appointment)->additional([
            'ok' => true,
            'message' => 'Appointment created successfully.',
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(UserAppointment $appointment)
    {
        $appointment = $this->appointment->getById($appointment->id);
        return AppointmentResource::make($appointment)->additional([
            'ok' => true,
            'message' => 'Appointment retrieved successfully.',
        ]);
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(AppointmentRequest $request, UserAppointment $appointment)
    {
        $data = $request->validated();
        $updatedAppointment = $this->appointment->update($appointment->id, $data);
        return AppointmentResource::make($updatedAppointment)->additional([
            'ok' => true,
            'message' => 'Appointment updated successfully.',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(UserAppointment $appointment)
    {
        $this->appointment->delete($appointment->id);
        return response_success('Appointment deleted successfully.');
    }

    public function getMonthlyAppointments(Request $request)
    {
        $request->validate([
            'year' => 'required|integer',
            'month' => 'required|integer|min:1|max:12',
        ]);

        $userId = auth()->id();

        $appointmentDays = UserAppointment::where('user_id', $userId)
            ->whereYear('appointment_at', $request->year)
            ->whereMonth('appointment_at', $request->month)
            ->get(['appointment_at'])
            ->map(function ($apt) {

                return \Carbon\Carbon::parse($apt->appointment_at)->day;
            })
            ->unique()
            ->values()
            ->all();

        return response()->json([
            'ok' => true,
            'message' => "Appointments retrieved.",
            'data' => $appointmentDays
        ]);
    }

    //update appointment status
    public function updateStatus(Request $request, UserAppointment $appointment)
    {
        $request->validate([
            'status' => 'required|in:completed,cancelled',
        ]);
        $appointment->status = $request->status;
        $appointment->save();
        return response_success('Appointment status updated successfully.');
    }
}
