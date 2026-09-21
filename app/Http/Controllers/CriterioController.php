<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreCriterioRequest;
use App\Models\Criterio;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CriterioController extends Controller
{
    /**
     * Muestra la lista de criterios asociados a una práctica.
     */
    public function index(Request $request): View
    {
        $practicaId = $request->query('practica_id');

        $criterios = Criterio::query()
            ->when($practicaId, fn ($query, $id) => $query->where('practica_id', $id))
            ->orderBy('orden', 'asc')
            ->get();

        return view('criterios.index', compact('criterios', 'practicaId'));
    }

    /**
     * Formulario de creación de criterio.
     */
    public function create(Request $request): View
    {
        $practicaId = $request->query('practica_id');

        return view('criterios.create', compact('practicaId'));
    }

    /**
     * Almacena un criterio recién creado en la base de datos.
     */
    public function store(StoreCriterioRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['orden'] = (int) ($validated['orden'] ?? 0);
        $validated['estado'] = $request->boolean('estado', true);

        Criterio::create($validated);

        return redirect()
            ->route('criterios.index', ['practica_id' => $request->integer('practica_id')])
            ->with('success', 'El criterio de evaluación fue registrado exitosamente.');
    }

    /**
     * Formulario de edición de un criterio.
     */
    public function edit(Criterio $criterio): View
    {
        return view('criterios.edit', compact('criterio'));
    }

    /**
     * Actualiza el criterio en la base de datos.
     */
    public function update(StoreCriterioRequest $request, Criterio $criterio): RedirectResponse
    {
        $validated = $request->validated();
        $validated['orden'] = (int) ($validated['orden'] ?? 0);
        $validated['estado'] = $request->boolean('estado', false);

        $criterio->update($validated);

        return redirect()
            ->route('criterios.index', ['practica_id' => $criterio->practica_id])
            ->with('success', 'El criterio de evaluación fue actualizado exitosamente.');
    }

    /**
     * Elimina el criterio de la base de datos.
     */
    public function destroy(Criterio $criterio): RedirectResponse
    {
        $practicaId = $criterio->practica_id;
        $criterio->delete();

        return redirect()
            ->route('criterios.index', ['practica_id' => $practicaId])
            ->with('success', 'El criterio de evaluación fue eliminado exitosamente.');
    }
}
