<?php

namespace App\Services\Reports;

use App\Models\Cart;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FinancialSnapshotService
{
    public function lineTicketTotalExpression(string $alias = 'harga_carts'): string
    {
        return "COALESCE($alias.quantity, 0) * COALESCE($alias.harga_ticket, 0)";
    }

    public function lineDiscountExpression(string $alias = 'harga_carts'): string
    {
        return "COALESCE($alias.disc, 0)";
    }

    public function cartLineSnapshotSubquery(): QueryBuilder
    {
        return DB::table('harga_carts')
            ->selectRaw('harga_carts.uid')
            ->selectRaw('SUM('.$this->lineTicketTotalExpression().') as ticket_total')
            ->selectRaw('SUM('.$this->lineDiscountExpression().') as discount_total')
            ->selectRaw('SUM(COALESCE(harga_carts.quantity, 0)) as total_quantity')
            ->whereNull('harga_carts.deleted_at')
            ->groupBy('harga_carts.uid');
    }

    public function joinLineSnapshots(Builder|QueryBuilder $query, string $alias = 'line_snapshots'): Builder|QueryBuilder
    {
        return $query->joinSub($this->cartLineSnapshotSubquery(), $alias, function ($join) use ($alias) {
            $join->on($alias.'.uid', '=', 'carts.uid');
        });
    }

    public function ownerRevenueSqlExpression(string $cartAlias = 'carts', string $lineAlias = 'line_snapshots'): string
    {
        $ticketTotal = "COALESCE($lineAlias.ticket_total, 0)";
        $discountTotal = "COALESCE($lineAlias.discount_total, 0)";
        $netTicket = "($ticketTotal - $discountTotal)";
        $legacyInferredTax = $this->positiveSqlExpression(
            "COALESCE($cartAlias.gross_amount, 0) - COALESCE($cartAlias.internet_fee, 0) - $netTicket"
        );
        $tax = "(CASE WHEN COALESCE($cartAlias.pajak, 0) > 0 THEN COALESCE($cartAlias.pajak, 0) ELSE $legacyInferredTax END)";

        return $this->positiveSqlExpression("$netTicket + $tax");
    }

    public function taxSnapshotSqlExpression(string $cartAlias = 'carts', string $lineAlias = 'line_snapshots'): string
    {
        $netTicket = "(COALESCE($lineAlias.ticket_total, 0) - COALESCE($lineAlias.discount_total, 0))";
        $legacyInferredTax = $this->positiveSqlExpression(
            "COALESCE($cartAlias.gross_amount, 0) - COALESCE($cartAlias.internet_fee, 0) - $netTicket"
        );

        return "(CASE WHEN COALESCE($cartAlias.pajak, 0) > 0 THEN COALESCE($cartAlias.pajak, 0) ELSE $legacyInferredTax END)";
    }

    public function applyOwnerRevenueSelect(Builder|QueryBuilder $query, string $alias = 'owner_revenue'): Builder|QueryBuilder
    {
        return $query->addSelect(DB::raw($this->ownerRevenueSqlExpression().' as '.$alias));
    }

    public function ownerRevenueForCart(Cart $cart): int
    {
        $lines = $cart->relationLoaded('hargaCarts')
            ? $cart->hargaCarts
            : $cart->hargaCarts()->get();

        return $this->ownerRevenueFromSnapshots(
            (int) $lines->sum(fn ($item) => (int) $item->quantity * (int) $item->harga_ticket),
            (int) $lines->sum(fn ($item) => (int) ($item->disc ?? 0)),
            (int) ($cart->pajak ?? 0),
            (int) ($cart->gross_amount ?? 0),
            (int) ($cart->internet_fee ?? 0)
        );
    }

    public function taxSnapshotForCart(Cart $cart): int
    {
        $lines = $cart->relationLoaded('hargaCarts')
            ? $cart->hargaCarts
            : $cart->hargaCarts()->get();

        return $this->taxSnapshotFromValues(
            (int) $lines->sum(fn ($item) => (int) $item->quantity * (int) $item->harga_ticket),
            (int) $lines->sum(fn ($item) => (int) ($item->disc ?? 0)),
            (int) ($cart->pajak ?? 0),
            (int) ($cart->gross_amount ?? 0),
            (int) ($cart->internet_fee ?? 0)
        );
    }

    public function collectionTotals(Collection $carts): array
    {
        $ticketTotal = 0;
        $discountTotal = 0;
        $quantityTotal = 0;
        $taxTotal = 0;
        $ownerRevenueTotal = 0;

        foreach ($carts as $cart) {
            $lines = $cart->relationLoaded('hargaCarts') ? $cart->hargaCarts : collect();
            $cartTicketTotal = (int) $lines->sum(fn ($item) => (int) $item->quantity * (int) $item->harga_ticket);
            $cartDiscountTotal = (int) $lines->sum(fn ($item) => (int) ($item->disc ?? 0));
            $ticketTotal += $cartTicketTotal;
            $discountTotal += $cartDiscountTotal;
            $quantityTotal += (int) $lines->sum('quantity');
            $taxTotal += $this->taxSnapshotFromValues(
                $cartTicketTotal,
                $cartDiscountTotal,
                (int) ($cart->pajak ?? 0),
                (int) ($cart->gross_amount ?? 0),
                (int) ($cart->internet_fee ?? 0)
            );
            $ownerRevenueTotal += $this->ownerRevenueForCart($cart);
        }

        return [
            'ticket_total' => $ticketTotal,
            'discount_total' => $discountTotal,
            'total_quantity' => $quantityTotal,
            'tax_total' => $taxTotal,
            'owner_revenue' => $ownerRevenueTotal,
        ];
    }

    private function ownerRevenueFromSnapshots(int $ticketTotal, int $discountTotal, int $pajak, int $grossAmount, int $internetFee): int
    {
        $netTicket = max(0, $ticketTotal - $discountTotal);

        return max(0, $netTicket + $this->taxSnapshotFromValues($ticketTotal, $discountTotal, $pajak, $grossAmount, $internetFee));
    }

    private function taxSnapshotFromValues(int $ticketTotal, int $discountTotal, int $pajak, int $grossAmount, int $internetFee): int
    {
        if ($pajak > 0) {
            return $pajak;
        }

        $netTicket = max(0, $ticketTotal - $discountTotal);

        return max(0, $grossAmount - $internetFee - $netTicket);
    }

    private function positiveSqlExpression(string $expression): string
    {
        return "(CASE WHEN ($expression) > 0 THEN ($expression) ELSE 0 END)";
    }
}
