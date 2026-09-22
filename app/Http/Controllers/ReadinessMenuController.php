<?php

namespace App\Http\Controllers;

use App\Models\Appraisal;
use App\Models\User;
use App\Support\Modulos;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReadinessMenuController extends Controller
{
    public function __invoke(Request $request, string $destino): RedirectResponse|View
    {
        /** @var User $user */
        $user = $request->user();

        $appraisal = Appraisal::query()
            ->visibleFor($user)
            ->orderByDesc('id')
            ->get()
            ->sortBy(fn (Appraisal $appraisal): int => $appraisal->isActivo() ? 0 : 1)
            ->first();

        if ($appraisal instanceof Appraisal) {
            return redirect()->route($destino, $appraisal);
        }

        return view('modulos.index', [
            'modulo' => Modulos::item((string) $request->route()?->getName()),
        ]);
    }
}
