<?php

namespace App\Services\Asistente;

use App\Models\Appraisal;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use League\CommonMark\Extension\Table\TableExtension;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;

class GeneradorArchivos
{
    public const DIRECTORIO = 'asistente';

    public const VIGENCIA_HORAS = 24;

    public const TIPOS_MIME = [
        'pdf' => 'application/pdf',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ];

    /**
     * @return array{id: string, formato: string, nombre: string, url: string, tamano_bytes: int, expira_en: string}
     */
    public function generar(string $formato, Appraisal $appraisal, ContextoReporte $contexto, string $markdown): array
    {
        $disco = $this->disco();
        $this->depurarVencidos($disco);

        $id = 'rep-'.Str::lower((string) Str::ulid());
        $ruta = self::DIRECTORIO.'/'.$id.'.'.$formato;

        if ($formato === 'xlsx') {
            $disco->makeDirectory(self::DIRECTORIO);
            $this->escribirExcel($disco->path($ruta), $appraisal, $contexto);
        } else {
            $disco->put($ruta, $this->renderizarPdf($appraisal, $contexto, $markdown));
        }

        $nombre = 'reporte-'.Str::slug($contexto->tipo).'-'.now()->format('Y-m-d').'.'.$formato;
        $expira = now()->addHours(self::VIGENCIA_HORAS);

        return [
            'id' => $id,
            'formato' => $formato,
            'nombre' => $nombre,
            'url' => URL::temporarySignedRoute('api.asistente.archivos.descarga', $expira, [
                'archivo' => $id,
                'formato' => $formato,
                'nombre' => $nombre,
            ]),
            'tamano_bytes' => $disco->size($ruta),
            'expira_en' => $expira->utc()->toIso8601ZuluString(),
        ];
    }

    public function rutaDe(string $archivo, string $formato): string
    {
        return self::DIRECTORIO.'/'.$archivo.'.'.$formato;
    }

    public function disco(): Filesystem
    {
        return Storage::disk('local');
    }

    private function renderizarPdf(Appraisal $appraisal, ContextoReporte $contexto, string $markdown): string
    {
        $html = view('reportes.asistente', [
            'appraisal' => $appraisal,
            'titulo' => $contexto->titulo,
            'contenido' => Str::markdown($markdown, ['html_input' => 'escape'], [new TableExtension]),
            'generadoEn' => now(),
        ])->render();

        $dompdf = new Dompdf(new Options(['defaultFont' => 'DejaVu Sans']));
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4');
        $dompdf->render();

        return (string) $dompdf->output();
    }

    private function escribirExcel(string $ruta, Appraisal $appraisal, ContextoReporte $contexto): void
    {
        $negrita = (new Style)->setFontBold();

        $writer = new Writer;
        $writer->openToFile($ruta);

        $writer->addRow(Row::fromValues([$contexto->titulo], $negrita));
        $writer->addRow(Row::fromValues(['Appraisal', $appraisal->name]));
        $writer->addRow(Row::fromValues(['Proyecto', $appraisal->project->name]));
        $writer->addRow(Row::fromValues(['Generado', now()->format('Y-m-d H:i')]));

        if ($contexto->nota !== null) {
            $writer->addRow(Row::fromValues(['Nota', $contexto->nota]));
        }

        $writer->addRow(Row::fromValues([]));
        $writer->addRow(Row::fromValues($contexto->columnas, $negrita));

        foreach ($contexto->filas as $fila) {
            $writer->addRow(Row::fromValues($fila));
        }

        $writer->close();
    }

    private function depurarVencidos(Filesystem $disco): void
    {
        $limite = now()->subHours(self::VIGENCIA_HORAS)->getTimestamp();

        foreach ($disco->files(self::DIRECTORIO) as $archivo) {
            if ($disco->lastModified($archivo) < $limite) {
                $disco->delete($archivo);
            }
        }
    }
}
