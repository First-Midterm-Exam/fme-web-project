@extends('pdf._layout')
@php $titulo = 'Reporte de Evidencias'; @endphp

@section('content')
<p class="section-title">Evidencias del Proyecto</p>

@if($evidences->isEmpty())
  <p class="empty">No hay evidencias registradas para este proyecto.</p>
@else
<table>
  <thead>
    <tr>
      <th style="width:70px">Código</th>
      <th>Nombre</th>
      <th style="width:70px">Tipo</th>
      <th style="width:80px">Estado</th>
      <th style="width:100px">Subida por</th>
      <th style="width:100px">Verificada por</th>
      <th style="width:75px">Fecha verif.</th>
    </tr>
  </thead>
  <tbody>
    @foreach($evidences as $ev)
    @php
      $sName  = $ev->status->name ?? '—';
      $colors = [
        'Verificada' => 'badge-success',
        'Aprobada'   => 'badge-success',
        'Pendiente'  => 'badge-warning',
        'Rechazada'  => 'badge-danger',
      ];
      $color = $colors[$sName] ?? 'badge-secondary';
    @endphp
    <tr>
      <td>{{ $ev->code }}</td>
      <td>{{ $ev->name }}</td>
      <td>{{ \App\Models\Evidence::TYPES[$ev->type] ?? $ev->type }}</td>
      <td><span class="badge {{ $color }}">{{ $sName }}</span></td>
      <td>{{ $ev->uploadedBy->name ?? '—' }}</td>
      <td>{{ $ev->verifiedBy->name ?? '—' }}</td>
      <td>{{ $ev->verified_at?->format('d/m/Y') ?? '—' }}</td>
    </tr>
    @endforeach
  </tbody>
</table>
<div class="footer" style="margin-top:6px; border:none;">
  Total: <strong>{{ $evidences->count() }}</strong> &nbsp;|&nbsp;
  Verificadas: <strong>{{ $evidences->filter(fn($e) => in_array($e->status->name ?? '', ['Verificada','Aprobada']))->count() }}</strong>
</div>
@endif
@endsection
