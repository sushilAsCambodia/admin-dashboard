<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class TicketSlave extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'ticket_id',
        'merchant_id',
        'game_play_id',
        'lottery_number',
        'big_bet_amount',
        'small_bet_amount',
        'three_a_amount',
        'three_c_amount',
        'bet_amount',
        'bet_net_amount',
        'rebate_amount',
        'rebate_percentage',
        'game_type',
        'bet_size',
        'prize_type',
        'winning_amount',
        'status',
        'progress_status',
        'child_ticket_no',
        'betting_date',

    ];

    protected $appends = ['odds'];

    public function getOddsAttribute()
    {
        if ($this?->ticket?->ticket_status === 'UNSETTLED') {
            return null;
        }

        $odd = $this?->merchant?->market?->oddSettings
            ->firstWhere('game_play_id', $this->game_play_id);

        return $odd ? $this->getOddValue($odd) : null;
    }

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }

    public function game()
    {
        return $this->belongsTo(GamePlay::class, 'game_play_id', 'id');
    }

    public function getOddValue($odd)
    {
        $prizeType = match ($this->prize_type) {
            'P1' => '_first',
            'P2' => '_second',
            'P3' => '_third',
            'S' => '_special',
            'C' => '_consolation',
            'No' => null,
            default => null,
        };

        if ($this->game_type === '4D') {
            return match ($this->bet_size) {
                'Both' => (float) ($this->big_bet_amount + $this->small_bet_amount) * ((float) $odd["big$prizeType"] + (float) $odd["small$prizeType"]),
                'Big' => (float) ($this->big_bet_amount) * (float) $odd["big$prizeType"],
                'Small' => (float) ($this->small_bet_amount) * (float) $odd["small$prizeType"],
                'No' => null,
                default => null,
            };
        } else {
            return match ($this->bet_size) {
                'Both' => (float) ($this->three_a_amount + $this->small_bet_amount) * ((float) $odd["three_a$prizeType"] + (float) $odd["three_c$prizeType"]),
                '3A' => (float) ($this->three_a_amount) * (float) $odd["three_a$prizeType"],
                '3C' => (float) ($this->three_c_amount) * (float) $odd["three_c$prizeType"],
                'No' => null,
                default => null,
            };
        }

        return null;
    }
}
