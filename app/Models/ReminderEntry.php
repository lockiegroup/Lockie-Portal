<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReminderEntry extends Model
{
    protected $fillable = [
        'year', 'month', 'account_code', 'name', 'add1', 'postcode',
        'doc_no', 'order_value', 'email', 'env_sets', 'box_colour', 'env_colour',
        'description', 'phone', 'status', 'called_by_user_id', 'called_date',
        'call_notes', 'has_ordered',
    ];

    protected $casts = [
        'has_ordered'  => 'boolean',
        'called_date'  => 'date',
        'order_value'  => 'decimal:2',
        'env_sets'     => 'decimal:2',
    ];

    const STATUSES = [
        'pending'            => 'Pending',
        'order_placed'       => 'Order Placed',
        'unable_to_contact'  => 'Unable to Contact / No Response',
        'using_spares'       => 'Using Spares / 2 Years Supply',
        'lost_price'         => 'Lost to Competitor - Price',
        'lost_quality'       => 'Lost to Competitor - Quality',
        'amalgamated'        => 'Amalgamated / Church Closed',
        'parish_giving'      => 'Moved to Parish Giving Scheme',
        'no_longer_required' => 'No Longer Required',
        'moved_stock'        => 'Moved to Stock Products',
    ];

    const STATUS_COLOURS = [
        'pending'            => ['bg' => '#ffffff', 'text' => '#334155'],
        'order_placed'       => ['bg' => '#dcfce7', 'text' => '#166534'],
        'unable_to_contact'  => ['bg' => '#fef3c7', 'text' => '#92400e'],
        'using_spares'       => ['bg' => '#dbeafe', 'text' => '#1e40af'],
        'lost_price'         => ['bg' => '#fee2e2', 'text' => '#991b1b'],
        'lost_quality'       => ['bg' => '#fee2e2', 'text' => '#991b1b'],
        'amalgamated'        => ['bg' => '#f1f5f9', 'text' => '#64748b'],
        'parish_giving'      => ['bg' => '#ede9fe', 'text' => '#5b21b6'],
        'no_longer_required' => ['bg' => '#fce7f3', 'text' => '#9d174d'],
        'moved_stock'        => ['bg' => '#e0f2fe', 'text' => '#0369a1'],
    ];

    const STATUS_SORT = [
        'order_placed'       => 1,
        'unable_to_contact'  => 2,
        'using_spares'       => 3,
        'moved_stock'        => 4,
        'lost_price'         => 5,
        'lost_quality'       => 6,
        'amalgamated'        => 7,
        'parish_giving'      => 8,
        'no_longer_required' => 9,
        'pending'            => 10,
    ];

    public function calledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'called_by_user_id');
    }

    public function rowBg(): string
    {
        return self::STATUS_COLOURS[$this->status]['bg'] ?? '#ffffff';
    }
}
