<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Category;
use App\Models\OrderItem;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

final class BestSellersPerCategoryWidget extends BaseWidget
{
    protected static ?int $sort = 8;

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '500px';

    protected static ?string $heading = 'Best Sellers per Category';

    public function getDescription(): ?string
    {
        return 'Top products by category (last 90 days, or all products if no sales data)';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Category::query()
                    ->where('is_active', true)
                    ->whereHas('products', function ($query) {
                        $query->where('is_active', true);
                    })
                    ->with(['products' => function ($query) {
                        $query->where('is_active', true);
                    }])
                    ->orderBy('name')
            )
            ->columns([
                TextColumn::make('name')
                    ->label('Category')
                    ->description('Product category')
                    ->weight('medium')
                    ->formatStateUsing(function ($record): string {
                        $productCount = $record->products->where('is_active', true)->count();

                        return "{$record->name} <span style='color: #6b7280; font-size: 0.85em;'>({$productCount} products)</span>";
                    })
                    ->html(),
                TextColumn::make('best_sellers')
                    ->label('Top Products')
                    ->description('Best-selling or newest products in this category')
                    ->formatStateUsing(function ($record): string {
                        return $this->getBestSellersHtml($record);
                    })
                    ->html(),
                TextColumn::make('sales_status')
                    ->label('Sales Activity')
                    ->description('Sales activity in the last 90 days')
                    ->alignCenter()
                    ->formatStateUsing(function ($record): string {
                        return $this->getSalesStatusBadge($record);
                    })
                    ->html(),
                TextColumn::make('category_stats')
                    ->label('Statistics')
                    ->description('Category performance metrics')
                    ->formatStateUsing(function ($record): string {
                        return $this->getCategoryStats($record);
                    })
                    ->html(),
            ])
            ->emptyStateHeading('No categories found')
            ->emptyStateDescription('No active categories with 3+ products found')
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(10);
    }

    private function getBestSellersHtml(Category $category): string
    {
        $bestSellers = $this->getBestSellers($category);

        if ($bestSellers['has_sales']) {
            $html = '<div style="display: flex; flex-direction: column; gap: 6px;">';

            foreach ($bestSellers['products'] as $index => $product) {
                $rank = $index + 1;
                $rankColor = $rank === 1 ? '#f59e0b' : ($rank === 2 ? '#6b7280' : '#9ca3af');

                $html .= "
                    <div style='display: flex; align-items: center; gap: 8px; padding: 4px 0;'>
                        <span style='background: {$rankColor}; color: white; padding: 2px 6px; border-radius: 4px; font-size: 0.7em; font-weight: 600; min-width: 20px; text-align: center;'>{$rank}</span>
                        <div style='flex: 1;'>
                            <div style='font-weight: 500; color: #1f2937;'>".e($product->product_name)."</div>
                            <div style='font-size: 0.8em; color: #6b7280;'>
                                ".number_format($product->total_quantity_sold).' units • ₱'.number_format($product->total_revenue, 2).'
                            </div>
                        </div>
                    </div>
                ';
            }

            $html .= '</div>';

            return $html;
        }
        // Show newest products when no sales data
        $html = '<div style="display: flex; flex-direction: column; gap: 4px;">';
        $html .= '<div style="font-size: 0.8em; color: #6b7280; margin-bottom: 4px;">No sales data yet • Showing newest products</div>';

        foreach ($bestSellers['products'] as $index => $product) {
            $rank = $index + 1;
            $rankColor = '#e5e7eb';

            $html .= "
                    <div style='display: flex; align-items: center; gap: 8px; padding: 4px 0;'>
                        <span style='background: {$rankColor}; color: #374151; padding: 2px 6px; border-radius: 4px; font-size: 0.7em; font-weight: 600; min-width: 20px; text-align: center;'>{$rank}</span>
                        <div style='flex: 1;'>
                            <div style='font-weight: 500; color: #1f2937;'>".e($product->name)."</div>
                            <div style='font-size: 0.8em; color: #6b7280;'>
                                ₱".number_format($product->price, 2).' • '.($product->is_active ? 'Active' : 'Inactive').'
                            </div>
                        </div>
                    </div>
                ';
        }

        $html .= '</div>';

        return $html;

    }

    private function getBestSellers(Category $category): array
    {
        // Try to get sales data from last 90 days
        $salesProducts = OrderItem::query()
            ->selectRaw('
                p.id as product_id,
                p.name as product_name,
                SUM(oi.quantity) as total_quantity_sold,
                SUM(oi.quantity * oi.price) as total_revenue,
                COUNT(DISTINCT oi.order_id) as orders_count,
                AVG(oi.price) as avg_price
            ')
            ->from('order_items as oi')
            ->join('products as p', 'oi.product_id', '=', 'p.id')
            ->join('orders as o', 'oi.order_id', '=', 'o.id')
            ->where('p.category_id', $category->id)
            ->where('p.is_active', true)
            ->where('o.created_at', '>=', now()->subDays(90))
            ->groupBy('p.id', 'p.name')
            ->orderBy('total_quantity_sold', 'desc')
            ->limit(3)
            ->get();

        if ($salesProducts->isNotEmpty()) {
            return [
                'has_sales' => true,
                'products' => $salesProducts,
            ];
        }

        // Fallback to newest products if no sales data
        $newestProducts = $category->products()
            ->where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->limit(3)
            ->get(['id', 'name', 'price', 'is_active', 'created_at']);

        return [
            'has_sales' => false,
            'products' => $newestProducts,
        ];
    }

    private function getSalesStatusBadge(Category $category): string
    {
        $recentSales = OrderItem::query()
            ->join('products as p', 'order_items.product_id', '=', 'p.id')
            ->join('orders as o', 'order_items.order_id', '=', 'o.id')
            ->where('p.category_id', $category->id)
            ->where('p.is_active', true)
            ->where('o.created_at', '>=', now()->subDays(90))
            ->sum('order_items.quantity');

        if ($recentSales > 100) {
            return "<span style='background: #10b981; color: white; padding: 4px 8px; border-radius: 4px; font-weight: 500; font-size: 0.85em;'>High</span>";
        }
        if ($recentSales > 0) {
            return "<span style='background: #f59e0b; color: white; padding: 4px 8px; border-radius: 4px; font-weight: 500; font-size: 0.85em;'>Low</span>";
        }

        return "<span style='background: #e5e7eb; color: #374151; padding: 4px 8px; border-radius: 4px; font-weight: 500; font-size: 0.85em;'>None</span>";

    }

    private function getCategoryStats(Category $category): string
    {
        $totalProducts = $category->products->where('is_active', true)->count();

        // Only show stats for categories with 3+ products
        if ($totalProducts < 3) {
            return '<div style="font-size: 0.8em; color: #6b7280;">Insufficient products</div>';
        }

        $recentSales = OrderItem::query()
            ->join('products as p', 'order_items.product_id', '=', 'p.id')
            ->join('orders as o', 'order_items.order_id', '=', 'o.id')
            ->where('p.category_id', $category->id)
            ->where('p.is_active', true)
            ->where('o.created_at', '>=', now()->subDays(90))
            ->sum('order_items.quantity');

        $avgPrice = $category->products->where('is_active', true)->avg('price') ?? 0;

        $html = '<div style="font-size: 0.8em; color: #374151;">';
        $html .= "<div><strong>{$totalProducts}</strong> products</div>";
        $html .= '<div><strong>'.number_format($recentSales).'</strong> sold</div>';
        $html .= '<div>Avg: <strong>₱'.number_format($avgPrice, 2).'</strong></div>';
        $html .= '</div>';

        return $html;
    }
}
