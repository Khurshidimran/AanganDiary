<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'user_id', 'employee_code', 'designation', 'department', 'employment_type', 'joining_date',
    'cnic', 'date_of_birth', 'address', 'emergency_contact_name', 'emergency_contact_phone',
    'bank_name', 'bank_account_number', 'basic_salary', 'employment_status', 'notes',
])]
class Employee extends Model
{
    use HasUuid, SoftDeletes;

    public const EMPLOYMENT_TYPE_FULL_TIME = 'full_time';
    public const EMPLOYMENT_TYPE_PART_TIME = 'part_time';
    public const EMPLOYMENT_TYPE_CONTRACT = 'contract';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_ON_LEAVE = 'on_leave';
    public const STATUS_TERMINATED = 'terminated';

    protected function casts(): array
    {
        return [
            'joining_date' => 'date',
            'date_of_birth' => 'date',
            'basic_salary' => 'decimal:2',
        ];
    }

    /**
     * Employee and RiderProfile both belongsTo User independently (neither
     * references the other directly), so there's no single Eloquent relation
     * connecting them — go through the shared user: $employee->user->riderProfile.
     * Eager-load 'user.riderProfile' wherever that's needed to avoid N+1.
     * Rider-operational data (vehicle, zone, wallet) stays in RiderProfile;
     * Employee only holds HR/payroll data. See PayrollService::deliveryEarningsFor().
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function advances(): HasMany
    {
        return $this->hasMany(EmployeeAdvance::class);
    }

    public function payrollRunItems(): HasMany
    {
        return $this->hasMany(PayrollRunItem::class);
    }
}
