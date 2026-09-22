@extends('pdf._layout')
@php $titulo = 'Reporte de Prácticas'; @endphp

@section('content')
<p class="section-title">Evaluación de Prácticas</p>

@if($evaluations->isEmpty())
  <p class="empty">No hay evaluaciones de prácticas registradas para este appraisal.</p>
@else
<table>
  <thead>
    <tr>
      <th style="width:90px">Código</th>
      <th>Práctica</th>
      <th style="width:100px">Área</th>
      <th style="width:40px">Nivel</th>
      <th style="width:100px">Estado</th>
      <th style="width:60px">Criterios<br>Cumplidos</th>
    </tr>
  </thead>
  <tbody>
    @foreach($evaluations as $eval)
    @php
      $colors = [
        'Verificada'          => 'badge-success',
        'Cumple'              => 'badge-info',
        'Parcial'             => 'badge-warning',
        'Cumple parcialmente' => 'badge-warning',
        'No cumple'           => 'badge-danger',
        'No evaluada'         => 'badge-secondary',
        'No aplica'           => 'badge-secondary',
      ];
      $color = $colors[$eval->status] ?? 'badge-secondary';
    @endphp
    <tr>
      <td>{{ $eval->practice->code ?? '—' }}</td>
      <td>{{ $eval->practice->name ?? '—' }}</td>
      <td>{{ $eval->practice->practiceArea->name ?? '—' }}</td>
      <td style="text-align:center">{{ $eval->practice->level ?? '—' }}</td>
      <td><span class="badge {{ $color }}">{{ $eval->status }}</span></td>
      <td style="text-align:center">{{ $eval->metCriteriaCount() }} / {{ $eval->applicableCriteriaCount() }}</td>
    </tr>
    @endforeach
  </tbody>
</table>

<div class="footer" style="margin-top:6px; border:none;">
  Total evaluaciones: <strong>{{ $evaluations->count() }}</strong> &nbsp;|&nbsp;
  Verificadas: <strong>{{ $evaluations->where('status','Verificada')->count() }}</strong> &nbsp;|&nbsp;
  Sin evaluar: <strong>{{ $evaluations->where('status','No evaluada')->count() }}</strong>
</div>
@endif
@endsection
