<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Reservation extends Model
{
    public const ACTIVE = ['pendiente', 'en_proceso', 'esperando_recogida'];
    public const LABELS = ['pendiente' => 'Pendiente', 'en_proceso' => 'En proceso', 'esperando_recogida' => 'Esperando recogida', 'finalizada' => 'Finalizado', 'cancelada' => 'Cancelada'];
    public const TRANSITIONS = ['pendiente' => ['cancelada'], 'en_proceso' => [], 'esperando_recogida' => [], 'finalizada' => [], 'cancelada' => []];
    protected $fillable = ['user_id', 'machine_id', 'starts_at', 'ends_at', 'garment_count', 'status', 'processing_started_at', 'processing_ends_at', 'collected_at'];
    public function canBeCancelledByStudent(): bool
    {
        return $this->status === 'pendiente' && $this->starts_at->isFuture();
    }
    protected function casts(): array { return ['starts_at' => 'datetime', 'ends_at' => 'datetime', 'processing_started_at' => 'datetime', 'processing_ends_at' => 'datetime', 'collected_at' => 'datetime']; }
    public function user() { return $this->belongsTo(User::class); }
    public function machine() { return $this->belongsTo(Machine::class)->withTrashed(); }
    public function getCodeAttribute(): string { return 'QW-'.str_pad((string) $this->id, 4, '0', STR_PAD_LEFT); }
}
