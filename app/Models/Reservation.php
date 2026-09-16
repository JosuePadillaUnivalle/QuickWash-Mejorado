<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Reservation extends Model
{
    public const ACTIVE = ['pendiente', 'en_proceso'];
    public const LABELS = ['pendiente' => 'Pendiente', 'en_proceso' => 'En proceso', 'finalizada' => 'Finalizada', 'cancelada' => 'Cancelada'];
    public const TRANSITIONS = ['pendiente' => ['en_proceso', 'cancelada'], 'en_proceso' => ['finalizada', 'cancelada'], 'finalizada' => [], 'cancelada' => []];
    protected $fillable = ['user_id', 'machine_id', 'starts_at', 'ends_at', 'garment_count', 'status'];
    public function canBeCancelledByStudent(): bool
    {
        return $this->status === 'pendiente' && $this->starts_at->isFuture();
    }
    protected function casts(): array { return ['starts_at' => 'datetime', 'ends_at' => 'datetime']; }
    public function user() { return $this->belongsTo(User::class); }
    public function machine() { return $this->belongsTo(Machine::class)->withTrashed(); }
    public function getCodeAttribute(): string { return 'QW-'.str_pad((string) $this->id, 4, '0', STR_PAD_LEFT); }
}
