<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8"/>
<title>{{ $titulo ?? 'Reporte' }}</title>
<style>
  * { margin:0; padding:0; box-sizing:border-box; }
  body { font-family: DejaVu Sans, sans-serif; font-size:10px; color:#1a1a2e; background:#fff; }
  .header { background:#1a1a2e; color:#fff; padding:18px 24px 14px; margin-bottom:16px; }
  .header h1 { font-size:16px; font-weight:700; letter-spacing:.5px; margin-bottom:4px; }
  .header .meta { font-size:9px; color:#a0aec0; }
  .header .meta span { color:#e2e8f0; font-weight:600; }
  .badge { display:inline-block; padding:2px 7px; border-radius:4px; font-size:8.5px; font-weight:700; text-transform:uppercase; letter-spacing:.3px; }
  .badge-success { background:#d1fae5; color:#065f46; }
  .badge-warning { background:#fef3c7; color:#92400e; }
  .badge-danger  { background:#fee2e2; color:#991b1b; }
  .badge-info    { background:#dbeafe; color:#1e40af; }
  .badge-secondary { background:#f3f4f6; color:#374151; }
  .badge-primary { background:#e0e7ff; color:#3730a3; }
  table { width:100%; border-collapse:collapse; margin-bottom:12px; }
  thead tr { background:#1a1a2e; color:#fff; }
  thead th { padding:7px 8px; font-size:9px; text-align:left; font-weight:700; text-transform:uppercase; letter-spacing:.4px; }
  tbody tr:nth-child(even) { background:#f9fafb; }
  tbody tr:nth-child(odd)  { background:#fff; }
  tbody td { padding:6px 8px; border-bottom:1px solid #e5e7eb; font-size:9.5px; vertical-align:top; }
  .section-title { font-size:11px; font-weight:700; color:#1a1a2e; margin:12px 0 6px; padding-bottom:4px; border-bottom:2px solid #1a1a2e; text-transform:uppercase; letter-spacing:.5px; }
  .info-grid { display:table; width:100%; margin-bottom:14px; }
  .info-row  { display:table-row; }
  .info-label { display:table-cell; width:140px; font-weight:700; color:#4b5563; font-size:9px; padding:3px 0; }
  .info-value { display:table-cell; color:#111827; font-size:9px; padding:3px 0; }
  .footer { margin-top:16px; padding-top:8px; border-top:1px solid #e5e7eb; font-size:8px; color:#9ca3af; text-align:center; }
  .disclaimer { background:#fffbeb; border:1.5px solid #f59e0b; border-radius:6px; padding:10px 12px; margin:12px 0; }
  .disclaimer-title { font-size:9px; font-weight:700; color:#b45309; text-transform:uppercase; letter-spacing:.4px; margin-bottom:4px; }
  .disclaimer-text  { font-size:9px; color:#78350f; line-height:1.5; }
  .empty { text-align:center; color:#9ca3af; font-style:italic; padding:20px 0; font-size:10px; }
  .progress-bar-bg { background:#e5e7eb; border-radius:4px; height:8px; width:100%; }
  .progress-bar-fill { background:#4f46e5; border-radius:4px; height:8px; }
  .score-box { background:#1a1a2e; color:#fff; border-radius:8px; padding:14px 20px; text-align:center; margin:10px 0; }
  .score-number { font-size:36px; font-weight:700; color:#818cf8; }
  .score-label  { font-size:9px; color:#a0aec0; margin-top:2px; }
</style>
</head>
<body>
<div class="header">
  <h1>{{ $titulo ?? 'Reporte CMMI' }}</h1>
  <div class="meta">
    Proyecto: <span>{{ $appraisal->project->name ?? 'N/A' }}</span> &nbsp;|&nbsp;
    Appraisal: <span>{{ $appraisal->name }}</span> &nbsp;|&nbsp;
    Nivel objetivo: <span>{{ $appraisal->target_level }}</span> &nbsp;|&nbsp;
    Generado: <span>{{ $generated_at }}</span>
  </div>
</div>
<div style="padding:0 16px 16px;">
  @yield('content')
</div>
<div class="footer" style="padding:0 16px 8px;">
  Sistema de Gestión CMMI V3.0 &mdash; Documento de uso interno &mdash; Generado el {{ $generated_at }}
</div>
</body>
</html>
