<?php

namespace App\Livewire\Notifications;

use App\Jobs\SendSmsJob;
use App\Models\Guardian;
use App\Models\School;
use App\Models\SchoolNotification;
use App\Models\SchoolClass;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class SendNotification extends Component
{
    /**
     * Livewire actions are posted to the internal livewire.update endpoint.
     * Preserve the page context captured on mount so a school administrator
     * remains an administrator while selecting recipients or sending.
     */
    public bool $isAdminPortal = false;
    public string $title        = '';
    public string $message      = '';
    public string $type         = 'general';
    public string $channel      = 'sms';
    public string $targetGrade  = '';
    public string $targetGroup  = 'all'; // all | grade | boarding | day
    public ?int $targetClassId  = null;

    protected $rules = [
        'title'         => 'required|string|max:255',
        'message'       => 'required|string',
        'type'          => 'required|in:general,fees,exam,report_card,attendance,emergency',
        'channel'       => 'required|in:sms,in_app,both',
        'targetGroup'   => 'required|in:all,grade,boarding,day',
        'targetGrade'   => 'nullable|string',
        'targetClassId' => 'nullable|integer',
    ];

    public function mount(bool $isAdminPortal = false): void
    {
        $this->isAdminPortal = $isAdminPortal;
    }

    public function send(): void
    {
        $this->validate();

        try {
            $user = Auth::user();

            if (!$user || !$user->school_id) {
                session()->flash('error', 'Unable to determine active school context.');
                return;
            }

            // Safely fetch target class if specified
            $targetClass = null;
            if ($this->targetClassId) {
                $targetClass = SchoolClass::where('school_id', $user->school_id)
                    ->find($this->targetClassId);

                if (!$targetClass) {
                    $this->addError('targetClassId', 'Selected class could not be found or has been removed.');
                    return;
                }
            }

            // Query active guardians safely with null guards
            $guardiansQuery = Guardian::query()
                ->where('school_id', $user->school_id)
                ->whereNotNull('phone')
                ->where('phone', '!=', '');

            if ($this->targetGroup === 'grade' && !empty($this->targetGrade)) {
                $guardiansQuery->whereHas('learners', function ($q) {
                    $q->where('grade', $this->targetGrade);
                });
            } elseif ($this->targetGroup === 'boarding') {
                $guardiansQuery->whereHas('learners', function ($q) {
                    $q->where('is_boarder', true);
                });
            } elseif ($this->targetGroup === 'day') {
                $guardiansQuery->whereHas('learners', function ($q) {
                    $q->where('is_boarder', false);
                });
            }

            if ($targetClass) {
                $guardiansQuery->whereHas('learners', function ($q) use ($targetClass) {
                    $q->where('school_class_id', $targetClass->id);
                });
            }

            $guardians = $guardiansQuery->get();

            if ($guardians->isEmpty()) {
                session()->flash('error', 'No valid recipients matching the selected criteria were found.');
                return;
            }

            // Create notification log record safely
            $notification = SchoolNotification::create([
                'school_id'       => $user->school_id,
                'sender_id'       => $user->id,
                'title'           => $this->title,
                'message'         => $this->message,
                'type'            => $this->type,
                'channel'         => $this->channel,
                'target_group'    => $this->targetGroup,
                'target_grade'    => $this->targetGrade ?: null,
                'school_class_id' => $this->targetClassId ?: null,
                'recipient_count' => $guardians->count(),
                'status'          => 'queued',
            ]);

            // Safely dispatch jobs per recipient without blowing up on individual failures
            foreach ($guardians as $guardian) {
                if (!empty($guardian->phone)) {
                    SendSmsJob::dispatch(
                        $user->school_id,
                        $guardian->phone,
                        $this->message,
                        $notification->id
                    );
                }
            }

            session()->flash('message', 'Notification queued successfully for ' . $guardians->count() . ' recipient(s).');
            $this->reset(['title', 'message', 'targetGrade', 'targetClassId']);

        } catch (\Throwable $e) {
            Log::error('SendNotification Execution Exception: ' . $e->getMessage(), [
                'user_id'   => Auth::id(),
                'school_id' => Auth::user()?->school_id,
                'file'      => $e->getFile(),
                'line'      => $e->getLine(),
                'trace'     => $e->getTraceAsString(),
            ]);

            session()->flash('error', 'An unexpected error occurred while sending the notification. Please check system logs.');
        }
    }

    public function render()
    {
        $schoolId = Auth::user()?->school_id;

        $classes = $schoolId 
            ? SchoolClass::where('school_id', $schoolId)->orderBy('name')->get() 
            : collect();

        return view('livewire.notifications.send-notification', [
            'classes' => $classes,
        ]);
    }
}
