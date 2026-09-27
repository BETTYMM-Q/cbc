<?php

namespace App\Services;

use App\Models\SchoolSetting;
use App\Models\StaffAttendance;
use App\Models\User;
use Illuminate\Http\Request;

class SchoolAttendanceLocationService
{
    public function settings(): array
    {
        $values = SchoolSetting::whereIn('key', [
            'attendance_auto_clock_enabled', 'attendance_allowed_ip_ranges',
            'attendance_school_latitude', 'attendance_school_longitude', 'attendance_geofence_meters',
        ])->pluck('value', 'key');

        return [
            'automatic_enabled' => filter_var($values->get('attendance_auto_clock_enabled', false), FILTER_VALIDATE_BOOLEAN),
            'allowed_ip_ranges' => trim((string) $values->get('attendance_allowed_ip_ranges', '')),
            'latitude' => $values->get('attendance_school_latitude') !== null ? (float) $values->get('attendance_school_latitude') : null,
            'longitude' => $values->get('attendance_school_longitude') !== null ? (float) $values->get('attendance_school_longitude') : null,
            'geofence_meters' => max(10, (int) ($values->get('attendance_geofence_meters') ?: 150)),
        ];
    }

    public function automaticClockIn(User $user, Request $request): ?StaffAttendance
    {
        $staff = $user->resolvedStaffMember();
        $settings = $this->settings();
        $ip = (string) $request->ip();

        if (! $staff || ! $settings['automatic_enabled'] || ! $this->ipMatches($ip, $settings['allowed_ip_ranges'])) {
            return null;
        }

        return $this->clockIn($user, 'approved_ip', $ip);
    }

    public function clockIn(User $user, string $method = 'manual', ?string $ip = null, ?float $latitude = null, ?float $longitude = null): StaffAttendance
    {
        $staff = $user->resolvedStaffMember();
        abort_unless($staff && $user->school_id, 403, 'Your teacher profile is not linked to this account.');
        $this->assertLocationAllowed($latitude, $longitude);

        $record = StaffAttendance::firstOrCreate([
            'staff_id' => $staff->id,
            'attendance_date' => today()->toDateString(),
        ], ['user_id' => $user->id]);

        if (! $record->clock_in_at) {
            $record->fill([
                'clock_in_at' => now(), 'clock_in_method' => $method, 'clock_in_ip' => $ip,
                'clock_in_latitude' => $latitude, 'clock_in_longitude' => $longitude,
            ])->save();
        }

        return $record->fresh();
    }

    public function clockOut(User $user, string $method = 'manual', ?string $ip = null, ?float $latitude = null, ?float $longitude = null): StaffAttendance
    {
        $staff = $user->resolvedStaffMember();
        abort_unless($staff && $user->school_id, 403, 'Your teacher profile is not linked to this account.');
        $this->assertLocationAllowed($latitude, $longitude);

        $record = StaffAttendance::where('staff_id', $staff->id)->whereDate('attendance_date', today())->first();
        abort_unless($record?->clock_in_at, 422, 'Clock in before recording a clock out time.');

        if (! $record->clock_out_at) {
            $record->fill([
                'clock_out_at' => now(), 'clock_out_method' => $method, 'clock_out_ip' => $ip,
                'clock_out_latitude' => $latitude, 'clock_out_longitude' => $longitude,
            ])->save();
        }

        return $record->fresh();
    }

    public function ipMatches(string $ip, string $ranges): bool
    {
        if (! filter_var($ip, FILTER_VALIDATE_IP)) return false;
        foreach (preg_split('/[\s,;]+/', trim($ranges), -1, PREG_SPLIT_NO_EMPTY) as $range) {
            if ($range === $ip) return true;
            if (! str_contains($range, '/')) continue;
            [$network, $bits] = array_pad(explode('/', $range, 2), 2, null);
            if (! filter_var($network, FILTER_VALIDATE_IP) || ! ctype_digit((string) $bits)) continue;
            $packedIp = inet_pton($ip);
            $packedNetwork = inet_pton($network);
            $maxBits = strlen($packedIp) === 4 ? 32 : 128;
            if ($packedIp === false || $packedNetwork === false || strlen($packedIp) !== strlen($packedNetwork) || (int) $bits > $maxBits) continue;
            $whole = intdiv((int) $bits, 8);
            $remaining = (int) $bits % 8;
            if (substr($packedIp, 0, $whole) !== substr($packedNetwork, 0, $whole)) continue;
            if ($remaining === 0 || (ord($packedIp[$whole]) & (0xFF << (8 - $remaining))) === (ord($packedNetwork[$whole]) & (0xFF << (8 - $remaining)))) return true;
        }
        return false;
    }

    private function assertLocationAllowed(?float $latitude, ?float $longitude): void
    {
        if ($latitude === null || $longitude === null) return; // Manual clocking remains available when location permission is declined.
        $settings = $this->settings();
        if ($settings['latitude'] === null || $settings['longitude'] === null) return;
        abort_unless($this->distanceMeters($latitude, $longitude, $settings['latitude'], $settings['longitude']) <= $settings['geofence_meters'], 422, 'You are outside the school attendance location. You may clock in manually without sharing location.');
    }

    private function distanceMeters(float $latitude, float $longitude, float $schoolLatitude, float $schoolLongitude): float
    {
        $earth = 6371000;
        $latDelta = deg2rad($schoolLatitude - $latitude);
        $lngDelta = deg2rad($schoolLongitude - $longitude);
        $a = sin($latDelta / 2) ** 2 + cos(deg2rad($latitude)) * cos(deg2rad($schoolLatitude)) * sin($lngDelta / 2) ** 2;
        return $earth * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
