<?php

namespace App\Livewire\Teacher;

use App\Models\Attendance;
use App\Models\Learner;
use App\Models\SchoolClass;
use App\Models\StaffAttendance;
use App\Services\SchoolAttendanceLocationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class AttendanceRegister extends Component
{
    public ?int $classId = null;
    public string $attendanceDate = '';
    public array $register = [];
    public ?float $latitude = null;
    public ?float $longitude = null;
    public ?StaffAttendance $staffAttendance = null;
    public bool $autoClockedIn = false;

    public function mount(Request $request, SchoolAttendanceLocationService $location): void
    {
        $this->attendanceDate = today()->toDateString();
        $this->staffAttendance = $location->automaticClockIn($request->user(), $request);
        $this->autoClockedIn = (bool) $this->staffAttendance?->clock_in_at && $this->staffAttendance->clock_in_method === 'approved_ip';

        if (! $this->staffAttendance) {
            $staff = $request->user()->resolvedStaffMember();
            $this->staffAttendance = $staff
                ? StaffAttendance::where('staff_id', $staff->id)->whereDate('attendance_date', today())->first()
                : null;
        }
        $this->classId = $this->classes()->first()?->id;
        if ($this->classId) $this->loadRegister();
    }

    public function updatedClassId(): void { $this->loadRegister(); }
    public function updatedAttendanceDate(): void { $this->loadRegister(); }

    public function loadRegister(): void
    {
        $this->register = [];
        if (! $this->classId || ! $this->mayMarkSelectedClass()) return;

        $saved = Attendance::where('class_id', $this->classId)->whereDate('date', $this->attendanceDate)->where('session', 'full_day')->get()->keyBy('learner_id');
        $this->register = Learner::active()->where('class_id', $this->classId)->orderBy('last_name')->orderBy('first_name')->get()
            ->mapWithKeys(fn (Learner $learner) => [$learner->id => [
                'name' => $learner->full_name,
                'admission_number' => $learner->admission_number,
                'status' => $saved->get($learner->id)?->status ?? 'present',
                'remarks' => $saved->get($learner->id)?->remarks ?? '',
            ]])->all();
    }

    public function markAllPresent(): void
    {
        foreach ($this->register as $id => $entry) $this->register[$id]['status'] = 'present';
    }

    public function saveRegister(): void
    {
        $this->validate([
            'classId' => ['required', 'integer'],
            'attendanceDate' => ['required', 'date', 'before_or_equal:today'],
            'register' => ['required', 'array', 'min:1'],
            'register.*.status' => ['required', 'in:present,absent,late,excused'],
            'register.*.remarks' => ['nullable', 'string', 'max:500'],
        ]);
        abort_unless($this->mayMarkSelectedClass(), 403, 'Only the assigned class teacher can submit this class attendance register.');

        // Do not trust the Livewire array keys: a crafted request must never
        // let a class teacher record attendance for a learner in another class.
        $allowedLearnerIds = Learner::active()->where('class_id', $this->classId)->pluck('id')->map(fn ($id) => (int) $id)->all();
        $submittedLearnerIds = array_map('intval', array_keys($this->register));
        abort_unless(empty(array_diff($submittedLearnerIds, $allowedLearnerIds)), 403, 'The submitted register contains a learner outside your assigned class.');

        $staff = auth()->user()->resolvedStaffMember();
        DB::transaction(function () use ($staff): void {
            foreach ($this->register as $learnerId => $entry) {
                Attendance::updateOrCreate([
                    'learner_id' => $learnerId,
                    'date' => $this->attendanceDate,
                    'session' => 'full_day',
                ], [
                    'class_id' => $this->classId,
                    'status' => $entry['status'],
                    'remarks' => filled($entry['remarks']) ? $entry['remarks'] : null,
                    'recorded_by' => $staff->id,
                ]);
            }
        });

        session()->flash('success', count($this->register) . ' learner attendance records saved and confirmed.');
    }

    public function clockIn(SchoolAttendanceLocationService $location): void
    {
        $this->staffAttendance = $location->clockIn(auth()->user(), 'manual', request()->ip(), $this->latitude, $this->longitude);
        $this->autoClockedIn = false;
        session()->flash('clock_success', 'Clock-in recorded at ' . $this->staffAttendance->clock_in_at->format('H:i') . '.');
    }

    public function clockOut(SchoolAttendanceLocationService $location): void
    {
        $this->staffAttendance = $location->clockOut(auth()->user(), 'manual', request()->ip(), $this->latitude, $this->longitude);
        session()->flash('clock_success', 'Clock-out recorded at ' . $this->staffAttendance->clock_out_at->format('H:i') . '.');
    }

    public function render()
    {
        return view('livewire.teacher.attendance-register', [
            'classes' => $this->classes(),
            'canMarkAttendance' => $this->classId && $this->mayMarkSelectedClass(),
            'locationSettings' => app(SchoolAttendanceLocationService::class)->settings(),
        ]);
    }

    private function classes()
    {
        $staff = auth()->user()->resolvedStaffMember();
        return $staff ? SchoolClass::active()->where('class_teacher_id', $staff->id)->orderBy('grade_level')->orderBy('name')->get() : collect();
    }

    private function mayMarkSelectedClass(): bool
    {
        $staff = auth()->user()->resolvedStaffMember();
        return (bool) ($staff && $this->classId && SchoolClass::whereKey($this->classId)->where('class_teacher_id', $staff->id)->exists());
    }
}
