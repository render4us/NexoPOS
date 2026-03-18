<?php

namespace Modules\Autoatendimento\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use Modules\Autoatendimento\Models\KioskSetting;

class KioskController extends Controller
{
    /**
     * Serve a landing page pública do kiosk.
     */
    public function index()
    {
        $setting = KioskSetting::instance();

        if (! $setting->ativo) {
            abort(404);
        }

        return view('Autoatendimento::kiosk', compact('setting'));
    }

    /**
     * Retorna categorias e produtos disponíveis para o kiosk (JSON público).
     */
    public function produtos()
    {
        $categories = ProductCategory::select('id', 'name')
            ->orderBy('name')
            ->get()
            ->map(fn ($c) => [
                'id'   => $c->id,
                'name' => $c->name,
            ]);

        $products = Product::with(['galleries', 'unit_quantities'])
            ->where('status', 'available')
            ->orderBy('name')
            ->get()
            ->map(function ($p) {
                $unitQty = $p->unit_quantities->first();

                if (! $unitQty) {
                    return null;
                }

                return [
                    'id'               => $p->id,
                    'name'             => $p->name,
                    'description'      => $p->description ?? '',
                    'price'            => (float) ($unitQty->sale_price ?? 0),
                    'unit_quantity_id' => $unitQty->id,
                    'category_id'      => $p->category_id,
                    'image'            => $p->galleries->first()?->url ?? null,
                ];
            })
            ->filter(fn ($p) => $p !== null && $p['price'] > 0)
            ->values();

        return response()->json(compact('categories', 'products'));
    }
}
