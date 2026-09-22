@extends('pdf._layout')
@php $titulo = 'Reporte de Acciones Correctivas'; @endphp

@section('content')
<p class="section-title">Acciones Correctivas</p>

@if($actions->isEmpty())
  <p class="empty">No hay acciones correctivas registradas para este appraisal.</p>
@else
<table>
  <thead>
    <tr>
      <th style="width:75px">Gap</th>
      <th>Descripción de la acción</th>
      <th style="width:100px">Responsable</th>
      <th style="width:70px">Fecha límite</th>
      <th style="width:65px">Progreso</th>
      <th style="width:80px">Estado</th>
    </tr>
  </thead>
  <tbody>
    @foreach($actions as $action)
    @php
      $stColors = [
        'abierta'     => 'badge-warning',
        'en_progreso' => 'badge-primary',
        'cerrada'     => 'badge-success',
      ];
      $stLabels = [
        'abierta'     => 'Abierta',
        'en_progreso' => 'En progreso',
        'cerrada'     => 'Cerrada',
      ];
    @endphp
    <tr>
      <td>{{ $action->gap->code ?? '—' }}</td>
      <td>{{ $action->description }}</td>
      <td>{{ $action->responsible->name ?? '—' }}</td>
      <td>{{ $action->due_date?->format('d/m/Y') ?? '—' }}</td>
      <td style="text-align:center">{{ $action->progress_percent }}%</td>
      <td><span class="badge {{ $stColors[$action->status] ?? 'badge-secondary' }}">{{ $stLabels[$action->status] ?? $action->status }}</span></td>
    </tr>
    @endforeach
  </tbody>
</table>
<div class="footer" style="margin-top:6px; border:none;">
  Total: <strong>{{ $actions->count() }}</strong> &nbsp;|&nbsp;
  Cerradas: <strong>{{ $actions->where('status','cerrada')->count() }}</strong> &nbsp;|&nbsp;
  En progreso: <strong>{{ $actions->where('status','en_progreso')->count() }}</strong>
</div>
@endif
@endsection
