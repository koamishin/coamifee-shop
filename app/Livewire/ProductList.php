<?php

declare(strict_types=1);

namespace App\Livewire;

use Livewire\Component;

final class ProductList extends Component
{
    public function render()
    {
        return view('livewire.product-list');
    }
}
