<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'sku',
        'description',
        'category',
        'price',
        'cost',
        'quantity_in_stock',
        'reorder_threshold',
        'low_stock_threshold',
        'track_stock',
        'barcode',
        'expiry_date',
        'tax_percentage',
        'tax_fixed',
        'is_active',
        'image',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'cost' => 'decimal:2',
        'tax_percentage' => 'decimal:2',
        'tax_fixed' => 'decimal:2',
        'expiry_date' => 'date',
        'is_active' => 'boolean',
        'track_stock' => 'boolean',
    ];

    protected $appends = ['image_url'];

    // Relationships
    public function invoiceItems(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    // Scopes
    public function scopeLowStock($query)
    {
        return $query->where('track_stock', true)
            ->whereColumn('quantity_in_stock', '<=', 'low_stock_threshold');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeExpiringSoon($query, $days = 30)
    {
        return $query->whereBetween('expiry_date', [now(), now()->addDays($days)]);
    }

    // Accessors
    public function getImageUrlAttribute(): ?string
    {
        if (!$this->image) {
            return null;
        }
        
        // If it's already a full URL, return it
        if (filter_var($this->image, FILTER_VALIDATE_URL)) {
            return $this->image;
        }
        
        // Generate full URL with APP_URL and the path as stored (images/products/...)
        return config('app.url') . '/' . $this->image;
    }

    public function getProfitMarginAttribute(): ?float
    {
        if ($this->cost && $this->cost > 0) {
            return (($this->price - $this->cost) / $this->cost) * 100;
        }
        return null;
    }

    public function getIsLowStockAttribute(): bool
    {
        if (!$this->track_stock) {
            return false;
        }
        return $this->quantity_in_stock <= $this->low_stock_threshold;
    }

    public function getNeedsReorderAttribute(): bool
    {
        if (!$this->track_stock) {
            return false;
        }
        return $this->quantity_in_stock <= $this->reorder_threshold;
    }

    // Stock Management Methods
    public function addStock($quantity, $notes = null,  $createdBy = null, $referenceType = null,  $referenceId = null): StockMovement
    {
        $quantityBefore = $this->quantity_in_stock;
        $this->increment('quantity_in_stock', $quantity);
        $this->refresh();

        return $this->stockMovements()->create([
            'type' => 'in',
            'quantity' => $quantity,
            'quantity_before' => $quantityBefore,
            'quantity_after' => $this->quantity_in_stock,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'notes' => $notes,
            'created_by' => $createdBy ?? auth()->id(),
        ]);
    }

    public function removeStock($quantity, $notes = null, $createdBy = null, $referenceType = null, $referenceId = null): StockMovement
    {
        $quantityBefore = $this->quantity_in_stock;
        $this->decrement('quantity_in_stock', $quantity);
        $this->refresh();

        return $this->stockMovements()->create([
            'type' => 'out',
            'quantity' => $quantity,
            'quantity_before' => $quantityBefore,
            'quantity_after' => $this->quantity_in_stock,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'notes' => $notes,
            'created_by' => $createdBy ?? auth()->id(),
        ]);
    }

    public function adjustStock($newQuantity, $notes = null, $createdBy = null): StockMovement
    {
        $quantityBefore = $this->quantity_in_stock;
        $quantityDiff = $newQuantity - $quantityBefore;

        $this->update(['quantity_in_stock' => $newQuantity]);

        return $this->stockMovements()->create([
            'type' => 'adjustment',
            'quantity' => abs($quantityDiff),
            'quantity_before' => $quantityBefore,
            'quantity_after' => $newQuantity,
            'notes' => $notes,
            'created_by' => $createdBy ?? auth()->id(),
        ]);
    }

    public function markDamaged($quantity, $notes = null, $createdBy = null): StockMovement
    {
        $quantityBefore = $this->quantity_in_stock;
        $this->decrement('quantity_in_stock', $quantity);
        $this->refresh();

        return $this->stockMovements()->create([
            'type' => 'damaged',
            'quantity' => $quantity,
            'quantity_before' => $quantityBefore,
            'quantity_after' => $this->quantity_in_stock,
            'notes' => $notes,
            'created_by' => $createdBy ?? auth()->id(),
        ]);
    }

    public function markExpired($quantity, $notes = null, $createdBy = null): StockMovement
    {
        $quantityBefore = $this->quantity_in_stock;
        $this->decrement('quantity_in_stock', $quantity);
        $this->refresh();

        return $this->stockMovements()->create([
            'type' => 'expired',
            'quantity' => $quantity,
            'quantity_before' => $quantityBefore,
            'quantity_after' => $this->quantity_in_stock,
            'notes' => $notes,
            'created_by' => $createdBy ?? auth()->id(),
        ]);
    }
}
