<?php
/*
Sitio Web Creado por Jerez.
Direccion: Miraflorez #1280
Quintero - Chile
jaime.jerez1978@gmail.com
https://www.
Creado, Programado y Diseñado por Jerez.
JJA
*/

/* -------------------------------------------------------------------------------------------------------------
   ------------------------------------- INICIO Jerez pdf_simple.php ----------------------------------------
   ------------------------------------------------------------------------------------------------------------- */

// TÍTULO: GENERADOR DE PDF SIMPLE (sin librerías externas)
//
// Arma un PDF de texto tamaño carta/A4 con títulos, pares "campo: valor" y párrafos, saltando de página solos.
// Usa las fuentes estándar Helvetica (no necesita instalar nada en el servidor).
// Uso:
//   $pdf = new PdfSimple('Título del documento');
//   $pdf->titulo('Datos del titular'); $pdf->campo('RUT', '12.345.678-5'); $pdf->parrafo('Texto...');
//   $contenido = $pdf->generar();   // texto binario del PDF listo para enviar al navegador

declare(strict_types=1);

final class PdfSimple
{
    // Tamaño de la hoja A4 en puntos y márgenes
    private const ANCHO = 595.0;
    private const ALTO = 842.0;
    private const MARGEN = 50.0;

    // Anchos de los caracteres 32 a 126 de Helvetica (por cada 1000 unidades de tamaño de letra)
    private const ANCHOS = [
        278,278,355,556,556,889,667,191,333,333,389,584,278,333,278,278,
        556,556,556,556,556,556,556,556,556,556,278,278,584,584,584,556,
        1015,667,667,722,722,667,611,778,722,278,500,667,556,833,722,778,
        667,778,722,667,611,722,667,944,667,667,611,278,278,278,469,556,
        333,556,556,500,556,556,278,556,556,222,222,500,222,833,556,556,
        556,556,333,500,278,556,500,722,500,500,500,334,260,334,584,
    ];

    /** @var string[] contenido de cada página ya armado */
    private array $paginas = [];
    private string $actual = '';
    private float $y;
    private string $tituloDocumento;

    public function __construct(string $tituloDocumento = 'Documento')
    {
        $this->tituloDocumento = $tituloDocumento;
        $this->nuevaPagina();
    }

    // Pasa un texto UTF-8 a la codificación que entiende el PDF (Windows-1252)
    private function codificar(string $texto): string
    {
        $t = @iconv('UTF-8', 'CP1252//TRANSLIT//IGNORE', $texto);
        return $t === false ? preg_replace('/[^\x20-\x7E]/', '?', $texto) : $t;
    }

    // Escapa los caracteres que tienen significado especial dentro de un texto del PDF
    private function escapar(string $textoCodificado): string
    {
        return str_replace(['\\', '(', ')', "\r", "\n"], ['\\\\', '\\(', '\\)', '', ''], $textoCodificado);
    }

    // Calcula cuánto mide un texto (en puntos) con un tamaño de letra dado
    private function ancho(string $textoCodificado, float $tam, bool $negrita = false): float
    {
        $suma = 0;
        $n = strlen($textoCodificado);
        for ($i = 0; $i < $n; $i++) {
            $c = ord($textoCodificado[$i]);
            $suma += ($c >= 32 && $c <= 126) ? self::ANCHOS[$c - 32] : 556;
        }
        return $suma * $tam / 1000 * ($negrita ? 1.06 : 1.0);
    }

    // Parte un texto en líneas que caben en el ancho indicado (corta palabras muy largas)
    private function envolver(string $textoCodificado, float $tam, float $anchoMax, bool $negrita = false): array
    {
        $lineas = [];
        foreach (preg_split('/\r\n|\r|\n/', $textoCodificado) as $parrafo) {
            $actual = '';
            foreach (preg_split('/ +/', $parrafo) as $palabra) {
                // Si una sola palabra no cabe en una línea, se corta en trozos
                while ($this->ancho($palabra, $tam, $negrita) > $anchoMax) {
                    $corte = strlen($palabra);
                    while ($corte > 1 && $this->ancho(substr($palabra, 0, $corte), $tam, $negrita) > $anchoMax) {
                        $corte--;
                    }
                    if ($actual !== '') { $lineas[] = $actual; $actual = ''; }
                    $lineas[] = substr($palabra, 0, $corte);
                    $palabra = substr($palabra, $corte);
                }
                $prueba = $actual === '' ? $palabra : $actual . ' ' . $palabra;
                if ($actual !== '' && $this->ancho($prueba, $tam, $negrita) > $anchoMax) {
                    $lineas[] = $actual;
                    $actual = $palabra;
                } else {
                    $actual = $prueba;
                }
            }
            $lineas[] = $actual;
        }
        return $lineas;
    }

    private function nuevaPagina(): void
    {
        if ($this->actual !== '') {
            $this->paginas[] = $this->actual;
        }
        $this->actual = '';
        $this->y = self::ALTO - self::MARGEN;
    }

    // Se asegura de que quepa el alto pedido; si no, abre una página nueva
    private function espacio(float $alto): void
    {
        if ($this->y - $alto < self::MARGEN + 20) {
            $this->nuevaPagina();
        }
    }

    // Escribe una línea de texto en la posición actual
    private function linea(string $textoCodificado, float $x, float $tam, bool $negrita = false, string $color = '0 g'): void
    {
        $fuente = $negrita ? '/F2' : '/F1';
        $this->actual .= sprintf("BT %s %s %.2F Tf %.2F %.2F Td (%s) Tj ET\n", $color, $fuente, $tam, $x, $this->y, $this->escapar($textoCodificado));
    }

    // TÍTULO GRANDE de la primera página
    public function titulo(string $texto): void
    {
        $t = $this->codificar($texto);
        foreach ($this->envolver($t, 18, self::ANCHO - 2 * self::MARGEN, true) as $l) {
            $this->espacio(24);
            $this->linea($l, self::MARGEN, 18, true);
            $this->y -= 24;
        }
        $this->y -= 2;
    }

    // Subtítulo de sección, con una línea debajo
    public function seccion(string $texto): void
    {
        $this->espacio(40);
        $this->y -= 8;
        $this->linea($this->codificar($texto), self::MARGEN, 13, true, '0.1 0.25 0.55 rg');
        $this->y -= 5;
        $this->actual .= sprintf("0.7 G 0.6 w %.2F %.2F m %.2F %.2F l S\n", self::MARGEN, $this->y, self::ANCHO - self::MARGEN, $this->y);
        $this->y -= 14;
    }

    // Texto en negrita corto (por ejemplo el encabezado de un registro)
    public function subtitulo(string $texto): void
    {
        $this->espacio(30);
        $this->y -= 4;
        foreach ($this->envolver($this->codificar($texto), 10.5, self::ANCHO - 2 * self::MARGEN, true) as $l) {
            $this->espacio(14);
            $this->linea($l, self::MARGEN, 10.5, true);
            $this->y -= 14;
        }
    }

    // Párrafo de texto normal
    public function parrafo(string $texto, float $tam = 10): void
    {
        foreach ($this->envolver($this->codificar($texto), $tam, self::ANCHO - 2 * self::MARGEN) as $l) {
            $this->espacio($tam + 4);
            $this->linea($l, self::MARGEN, $tam);
            $this->y -= $tam + 4;
        }
    }

    // Par "Campo: valor" (el campo en negrita a la izquierda y el valor a la derecha, con ajuste de línea)
    public function campo(string $etiqueta, string $valor, float $tam = 9.5): void
    {
        $anchoEtiqueta = 180.0;
        $x2 = self::MARGEN + $anchoEtiqueta;
        $lineasEtiqueta = $this->envolver($this->codificar($etiqueta), $tam, $anchoEtiqueta - 8, true);
        $lineasValor = $this->envolver($this->codificar($valor === '' ? '-' : $valor), $tam, self::ANCHO - self::MARGEN - $x2);
        $filas = max(count($lineasEtiqueta), count($lineasValor));
        for ($i = 0; $i < $filas; $i++) {
            $this->espacio($tam + 4);
            if (isset($lineasEtiqueta[$i])) $this->linea($lineasEtiqueta[$i], self::MARGEN, $tam, true);
            if (isset($lineasValor[$i])) $this->linea($lineasValor[$i], $x2, $tam);
            $this->y -= $tam + 4;
        }
    }

    public function espacioVertical(float $puntos = 8): void
    {
        $this->y -= $puntos;
    }

    // Arma el archivo PDF completo y lo devuelve como texto binario
    public function generar(): string
    {
        $paginas = $this->paginas;
        $paginas[] = $this->actual;
        $total = count($paginas);

        // Objetos fijos: 1 catálogo, 2 lista de páginas, 3 fuente normal, 4 fuente en negrita, 5 información
        $objetos = [];
        $objetos[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objetos[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
        $objetos[4] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';
        $objetos[5] = '<< /Title (' . $this->escapar($this->codificar($this->tituloDocumento)) . ') /Producer (Control de Combustible) /CreationDate (D:' . date('YmdHis') . ') >>';

        $ids = [];
        $siguiente = 6;
        foreach ($paginas as $i => $contenido) {
            // Pie de página con el número de hoja
            $pie = sprintf("BT 0.4 g /F1 8 Tf %.2F %.2F Td (%s) Tj ET\n", self::MARGEN, 30.0,
                $this->escapar($this->codificar($this->tituloDocumento . '  -  Página ' . ($i + 1) . ' de ' . $total)));
            $flujo = $contenido . $pie;
            $idContenido = $siguiente++;
            $idPagina = $siguiente++;
            $objetos[$idContenido] = '<< /Length ' . strlen($flujo) . " >>\nstream\n" . $flujo . "endstream";
            $objetos[$idPagina] = sprintf('<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %d %d] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents %d 0 R >>', self::ANCHO, self::ALTO, $idContenido);
            $ids[] = $idPagina;
        }
        $kids = implode(' ', array_map(static fn($id) => $id . ' 0 R', $ids));
        $objetos[2] = '<< /Type /Pages /Kids [' . $kids . '] /Count ' . count($ids) . ' >>';

        ksort($objetos);
        $salida = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $posiciones = [];
        foreach ($objetos as $num => $cuerpo) {
            $posiciones[$num] = strlen($salida);
            $salida .= $num . " 0 obj\n" . $cuerpo . "\nendobj\n";
        }
        $inicioXref = strlen($salida);
        $maximo = max(array_keys($objetos));
        $salida .= "xref\n0 " . ($maximo + 1) . "\n0000000000 65535 f \n";
        for ($n = 1; $n <= $maximo; $n++) {
            $salida .= sprintf("%010d 00000 n \n", $posiciones[$n]);
        }
        $salida .= "trailer\n<< /Size " . ($maximo + 1) . " /Root 1 0 R /Info 5 0 R >>\nstartxref\n" . $inicioXref . "\n%%EOF\n";
        return $salida;
    }
}

/* -------------------------------------------------------------------------------------------------------------
   -------------------------------------- FIN Jerez pdf_simple.php ------------------------------------------
   ------------------------------------------------------------------------------------------------------------- */

/*
Sitio Web Creado por Jerez.
Direccion: Miraflorez #1280
Quintero - Chile
jaime.jerez1978@gmail.com
https://www.
Creado, Programado y Diseñado por Jerez.
JJA
*/
