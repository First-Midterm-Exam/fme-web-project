<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Evidence extends Model
{
    use HasFactory;

    protected $table = 'evidences';

    public const TYPE_ACTA = 'acta';

    public const TYPE_INFORME = 'informe';

    public const TYPE_PLAN = 'plan';

    public const TYPE_REGISTRO = 'registro';

    public const TYPE_OTRO = 'otro';

    public const TYPES = [
        self::TYPE_ACTA => 'Acta',
        self::TYPE_INFORME => 'Informe',
        self::TYPE_PLAN => 'Plan',
        self::TYPE_REGISTRO => 'Registro',
        self::TYPE_OTRO => 'Otro',
    ];

    public const ALLOWED_EXTENSIONS = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'png', 'jpg', 'jpeg', 'txt'];

    public const MAX_FILE_SIZE_KB = 10240;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'project_id',
        'name',
        'type',
        'description',
        'status_id',
        'uploaded_by',
        'verification_reason',
        'verified_by',
        'verified_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'verified_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return BelongsTo<EvidenceStatus, $this>
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(EvidenceStatus::class, 'status_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * @return HasMany<EvidenceVersion, $this>
     */
    public function versions(): HasMany
    {
        return $this->hasMany(EvidenceVersion::class);
    }

    /**
     * @return HasOne<EvidenceVersion, $this>
     */
    public function currentVersion(): HasOne
    {
        return $this->hasOne(EvidenceVersion::class)->latestOfMany('number');
    }

    public static function generateNextCode(): string
    {
        $latestCode = static::query()
            ->where('code', 'LIKE', 'EV-%')
            ->lockForUpdate()
            ->orderByRaw('CAST(SUBSTR(code, 4) AS INTEGER) DESC')
            ->value('code');

        if (! $latestCode) {
            return 'EV-0001';
        }

        $number = (int) substr($latestCode, 3) + 1;

        return sprintf('EV-%04d', $number);
    }

    /**
     * @return BelongsToMany<Practice, $this>
     */
    public function practices(): BelongsToMany
    {
        return $this->belongsToMany(Practice::class, 'evidence_practice')->withTimestamps();
    }
}
