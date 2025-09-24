<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->string('query')->toString();

        $paginator = Product::query()
            ->when($q, function (Builder $b) use ($q) {
                $b->where(function (Builder $w) use ($q) {
                    $w->where('title', 'like', "%{$q}%")
                        ->orWhere('description', 'like', "%{$q}%");
                });
            })
            ->orderByDesc('id')
            ->paginate(perPage: 25);

        return response()->json($paginator);
    }

    public function show(int $id)
    {
        $product = Product::with(['variants', 'images'])->findOrFail($id);
        return response()->json($product);
    }
}
