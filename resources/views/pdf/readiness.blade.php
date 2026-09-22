@extends('pdf._layout')
@php $titulo = 'Reporte de Appraisal Readiness Score'; @endphp

@section('content')

<div class="disclaimer">
  <div class="disclaimer-title">&#9888; Aviso Legal y de Certificación</div>
  <div class="disclaimer-text">{{ $disclaimer }}</div>
</div>

<p class="section-title">Readiness Score Global</p>

<div class="score-box">
  <div class="score-number">{{ number_format($score, 1) }}%</div>
  <div class="score-label">Índice de Preparación del Appraisal</div>
</div>

<p class="section-title">Desglose por Componente</p>

<table>
  <thead>
    <tr>
      <th>Componente</th>
      <th style="width:90px">Valor actual</th>
      <th style="width:90px">Total / Req.</th>
      <th style="width:80px">Tasa (%)</th>
      <th style="width:70px">Peso (%)</th>
      <th style="width:90px">Contribución</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td><strong>Prácticas evaluadas</strong></td>
      <td>{{ $breakdown['practices']['count'] }}</td>
      <td>{{ $breakdown['practices']['total'] }}</td>
      <td>{{ $breakdown['practices']['completion_rate'] }}%</td>
      <td>{{ $breakdown['practices']['weight'] }}%</td>
      <td><strong>{{ $breakdown['practices']['contribution'] }} pts</strong></td>
    </tr>
    <tr>
      <td><strong>Evidencias verificadas</strong></td>
      <td>{{ $breakdown['evidences']['count'] }}</td>
      <td>{{ $breakdown['evidences']['required'] }}</td>
      <td>{{ $breakdown['evidences']['completion_rate'] }}%</td>
      <td>{{ $breakdown['evidences']['weight'] }}%</td>
      <td><strong>{{ $breakdown['evidences']['contribution'] }} pts</strong></td>
    </tr>
    <tr>
      <td><strong>Salud de Gaps</strong></td>
      <td>{{ $breakdown['gaps']['total_count'] - $breakdown['gaps']['open_count'] }} cerrados</td>
      <td>{{ $breakdown['gaps']['total_count'] }} total</td>
      <td>{{ $breakdown['gaps']['health_rate'] }}%</td>
      <td>{{ $breakdown['gaps']['weight'] }}%</td>
      <td><strong>{{ $breakdown['gaps']['contribution'] }} pts</strong></td>
    </tr>
  </tbody>
</table>

<div class="footer" style="margin-top:6px; border:none; text-align:left;">
  Score ponderado: <strong>{{ number_format($score, 1) }}%</strong> &nbsp;|&nbsp;
  Prácticas 40% + Evidencias 40% + Gaps 20%
</div>

@endsection
