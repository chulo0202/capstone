<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PcicTrackingUpdate extends Model
{
    public const STATUSES = [
        'under_review' => 'Under review',
        'documents_requested' => 'Additional documents requested',
        'incident_verified' => 'Damage incident verified by FAMS',
        'incident_rejected' => 'Damage incident rejected by FAMS',
        'claim_approved_by_fams' => 'Claim approved by FAMS',
        'application_approved' => 'Insurance application approved by FAMS',
        'application_rejected' => 'Application rejected by FAMS',
        'approved' => 'Approved by PCIC',
        'rejected' => 'Rejected by PCIC',
        'release_scheduled' => 'Release scheduled',
        'released' => 'Released',
    ];

    public const ADMIN_STATUSES = [
        'under_review', 'documents_requested', 'approved', 'rejected', 'release_scheduled', 'released',
    ];

    public const OFFICIAL_STATUSES = ['approved', 'rejected', 'release_scheduled', 'released'];

    protected $fillable = ['user_id', 'status', 'remarks', 'pcic_reference', 'expected_release_date', 'attachment_path'];

    protected $casts = ['expected_release_date' => 'date'];

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }
}
