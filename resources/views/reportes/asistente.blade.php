<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $titulo }}</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10.5px; color: #1f2937; }
        .encabezado { border-bottom: 2px solid #0d6efd; padding-bottom: 8px; margin-bottom: 14px; }
        .encabezado h1 { font-size: 17px; margin: 0 0 4px; color: #0d6efd; }
        .encabezado p { margin: 0; color: #6b7280; }
        h2 { font-size: 14px; color: #111827; margin: 16px 0 8px; }
        h3 { font-size: 12px; margin: 12px 0 6px; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th { background: #0d6efd; color: #ffffff; text-align: left; padding: 5px; }
        td { border-bottom: 1px solid #e5e7eb; padding: 5px; vertical-align: top; }
        tr:nth-child(even) td { background: #f9fafb; }
        blockquote { border-left: 3px solid #f59e0b; margin: 8px 0; padding: 4px 10px; color: #92400e; background: #fffbeb; }
        .pie { margin-top: 18px; font-size: 9px; color: #9ca3af; text-align: right; }
    </style>
</head>
<body>
    <div class="encabezado">
        <h1>{{ $titulo }}</h1>
        <p>{{ $appraisal->name }} · {{ $appraisal->project->name }} · Nivel objetivo {{ $appraisal->target_level }}</p>
    </div>

    {!! $contenido !!}

    <p class="pie">DIMA LTDA — Plataforma CMMI · Generado el {{ $generadoEn->format('d/m/Y H:i') }}</p>
</body>
</html>
