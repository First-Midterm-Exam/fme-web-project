@extends('pdf._layout')
@php $titulo = 'Reporte de Gaps'; @endphp

@section('content')
<p class="section-title">Gaps Identificados</p>

@if($gaps->isEmpty())
  <p class="empty">No hay gaps registrados para este appraisal.</p>
@else
<table>
  <thead>
    <tr>
      <th style="width:75px">Código</th>
      <th>Título</th>
      <th style="width:90px">Práctica</th>
      <th style="width:65px">Severidad</th>
      <th style="width:80px">Estado</th>
      <th style="width:95px">Responsable</th>
      <th style="width:70px">Fecha límite</th>
    </tr>
  </thead>
  <tbody>
    @foreach($gaps as $gap)
    @php
      $sevColors = [
        'Crítica' => 'badge-danger',
        'Alta'    => 'badge-warning',
        'Media'   => 'badge-info',
        'Baja'    => 'badge-secondary',
      ];
      $stColors = [
        'Verificado' => 'badge-success',
        'Cerrado'    => 'badge-success',
        'Resuelto'   => 'badge-info',
        'En progreso'=> 'badge-primary',
        'Abierto'    => 'badge-warning',
      ];
    @endphp
    <tr>
      <td>{{ $gap->code }}</td>
      <td>{{ $gap->title }}</td>
      <td>{{ $gap->practiceEvaluation->practice->code ?? '—' }}</td>
      <td><span class="badge {{ $sevColors[$gap->severity] ?? 'badge-secondary' }}">{{ $gap->severity }}</span></td>
      <td><span class="badge {{ $stColors[$gap->status] ?? 'badge-secondary' }}">{{ $gap->status }}</span></td>
      <td>{{ $gap->assignedTo->name ?? '—' }}</td>
      <td>{{ $gap->due_date?->format('d/m/Y') ?? '—' }}</td>
    </tr>
    @endforeach
  </tbody>
</table>
<div class="footer" style="margin-top:6px; border:none;">
  Total: <strong>{{ $gaps->count() }}</strong> &nbsp;|&nbsp;
  Abiertos: <strong>{{ $gaps->where('status','Abierto')->count() }}</strong> &nbsp;|&nbsp;
  En progreso: <strong>{{ $gaps->where('status','En progreso')->count() }}</strong> &nbsp;|&nbsp;
  Cerrados/Verificados: <strong>{{ $gaps->whereIn('status',['Cerrado','Verificado'])->count() }}</strong>
</div>
@endif
@endsection
