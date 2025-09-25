<?php
require_once __DIR__ . '/../vendor/autoload.php'; // Agregar autoload de Composer
require_once __DIR__ . '/../includes/helpers.php';

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\TemplateProcessor;
use PhpOffice\PhpWord\Element\Paragraph;
use PhpOffice\PhpWord\Shared\Html;

require_role(ROLE_SUPERVISOR, ROLE_ADMIN);

// Obtener ID del informe
$report_id = (int)($_GET['report_id'] ?? 0);
if (!$report_id) {
    http_response_code(400);
    echo 'report_id requerido';
    exit;
}

// Conectar a la base de datos
$pdo = db();

// Obtener datos del informe
$r = $pdo->prepare("SELECT id, titulo AS title, periodo AS period FROM informes WHERE id = ?");
$r->execute([$report_id]);
$report = $r->fetch();
if (!$report) {
    http_response_code(404);
    echo 'Informe no encontrado';
    exit;
}

// Obtener secciones del informe
$s = $pdo->prepare("SELECT rs.titulo AS title, rs.contenido AS content, d.nombre AS dept 
    FROM secciones_informe rs 
    LEFT JOIN departamentos d ON d.id = rs.departamento_id
    WHERE rs.informe_id = ?
    ORDER BY rs.orden ASC");
$s->execute([$report_id]);
$sections = $s->fetchAll();

// Nombre del archivo de descarga
$downloadName = 'informe_' . $report_id . '_' . date('Ymd_His') . '.docx';
$templatePath = __DIR__ . '/../plantilla/plantilla.docx';

//ruta del proyecto
define('PROJECT_ROOT', $_SERVER['DOCUMENT_ROOT']);

// Verificar que la plantilla existe
if (!is_file($templatePath)) {
    http_response_code(500);
    echo 'Plantilla no encontrada';
    exit;
}

// Procesar la plantilla
try {
    $templateProcessor = new TemplateProcessor($templatePath);
    
    // Reemplazar valores simples
    $templateProcessor->setValue('periodo', strtoupper($report['period']));
    $templateProcessor->setValue('año', date('Y'));
    
    // Procesar cada sección para los marcadores de contenido
    foreach ($sections as $index => $section) {
        $marker = 'contenido' . ($index + 1);
        $htmlContent = $section['content'] ?? '';

        if (!empty(trim($htmlContent))) {
            // Generar el XML de Word (WordProcessingML) a partir del HTML
            $innerXml = generar_xml_desde_html($htmlContent);
            
            // Inyectar el bloque XML en la plantilla.
            // Usamos setValue, no setComplexValue, para inyectar XML crudo.
            $templateProcessor->setValue($marker, $innerXml);
        } else {
            // Si no hay contenido, dejar el marcador vacío
            $templateProcessor->setValue($marker, '');
        }
    }

    // Guardar el documento procesado
    $tempFile = tempnam(sys_get_temp_dir(), 'docx');
    $templateProcessor->saveAs($tempFile);
    
} catch (\Throwable $e) {
    error_log('[word_generator] Error procesando plantilla: ' . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine());
    http_response_code(500);
    echo 'Error generando documento. Revise los logs del servidor.';
    exit;
}

// Enviar el archivo al navegador
output_docx_file($tempFile, $downloadName);

// Limpiar archivos temporales
@unlink($tempFile);
exit;

// =============================
// Funciones auxiliares
// =============================

/**
 * Envía un archivo DOCX al navegador para su descarga.
 */
function output_docx_file(string $path, string $filename): void {
    while (ob_get_level() > 0) { ob_end_clean(); }
    header('Content-Description: File Transfer');
    header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    header('Content-Length: ' . filesize($path));
    readfile($path);
}

/**
 * Convierte un string de HTML en WordProcessingML para ser inyectado en la plantilla.
 */
function generar_xml_desde_html(string $html): string {
    // Crear un objeto PhpWord temporal en memoria
    $tempPhpWord = new PhpWord();
    $section = $tempPhpWord->addSection();

    // Parsear el HTML y añadirlo al objeto PhpWord temporal
    renderCKEditorContent($section, $html);

    // Guardar el documento temporal en un archivo para poder extraer su XML
    $tempDocxPath = tempnam(sys_get_temp_dir(), 'temp_docx');
    $writer = IOFactory::createWriter($tempPhpWord, 'Word2007');
    $writer->save($tempDocxPath);

    // Extraer el contenido XML del cuerpo del documento temporal
    $innerXml = '';
    $zip = new ZipArchive();
    if ($zip->open($tempDocxPath) === true) {
        // Leer el contenido del archivo principal del documento
        $xmlContent = $zip->getFromName('word/document.xml');
        if ($xmlContent) {
            // Usar DOMDocument para extraer solo el contenido dentro de <w:body>
            $dom = new DOMDocument();
            $dom->loadXML($xmlContent);
            $bodyNode = $dom->getElementsByTagName('body')->item(0);
            if ($bodyNode) {
                foreach ($bodyNode->childNodes as $childNode) {
                    // Guardar el XML de cada nodo hijo del cuerpo
                    $innerXml .= $dom->saveXML($childNode);
                }
            }
        }
        $zip->close();
    }

    // Limpiar el archivo temporal
    @unlink($tempDocxPath);

    return $innerXml;
}

/**
 * Parsea el contenido HTML de CKEditor y lo renderiza en una sección de PhpWord.
 */
function renderCKEditorContent($section, $html) {
    if (empty(trim($html))) return;

    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    // Forzar la codificación a UTF-8 para evitar problemas con caracteres especiales
    $dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();

    foreach ($dom->childNodes as $node) {
        processNode($section, $node);
    }
}

/**
 * Procesa un nodo DOM y lo convierte en el elemento de PhpWord correspondiente.
 */
function processNode($element, $node) {
    if ($node instanceof \DOMText) {
        // Ignorar nodos de texto vacíos que solo contienen saltos de línea
        if (trim($node->nodeValue) !== '') {
            $element->addText(htmlspecialchars($node->nodeValue));
        }
        return;
    }

    if ($node instanceof \DOMElement) {
        switch (strtolower($node->nodeName)) {
            case 'h1':
                $element->addTitle(htmlspecialchars($node->nodeValue), 1);
                break;
            case 'h2':
                $element->addTitle(htmlspecialchars($node->nodeValue), 2);
                break;
            case 'h3':
                $element->addTitle(htmlspecialchars($node->nodeValue), 3);
                break;
            case 'p':
            case 'ul':
            case 'ol':
                // Dejar que el helper de HTML maneje párrafos y listas
                Html::addHtml($element, $node->ownerDocument->saveHTML($node), false, false);
                break;
            case 'table':
                addTableToWord($element, $node);
                break;
            case 'img':
                addImageToWord($element, $node);
                break;
            default:
                // Para otros nodos, intentar con el helper de HTML como último recurso
                if (trim($node->nodeValue) !== '') {
                    Html::addHtml($element, $node->ownerDocument->saveHTML($node), false, false);
                }
                break;
        }
    }
}

/**
 * Añade una imagen a la sección de PhpWord.
 */
function addImageToWord($element, $imgNode) {
    $src = $imgNode->getAttribute('src');
    
    // Construir la ruta absoluta del archivo en el servidor
    // Eliminar cualquier parte del dominio si está presente (ej: /informe2/uploads/...)
    $relativePath = parse_url($src, PHP_URL_PATH);
    $absolutePath = $_SERVER['DOCUMENT_ROOT'] . $relativePath;

    if (file_exists($absolutePath)) {
        $style = [];
        // Extraer el ancho del estilo en línea si existe
        if (preg_match('/width:\s*(\d+)px;/', $imgNode->getAttribute('style'), $matches)) {
            // Convertir píxeles a una medida que Word entiende (aproximadamente)
            $style['width'] = intval($matches[1]);
        }
        $element->addImage($absolutePath, $style);
    } else {
        $errorText = '[Imagen no encontrada: ' . htmlspecialchars($src) . ']';
        $element->addText($errorText, ['color' => 'red', 'italic' => true]);
        error_log("Imagen no encontrada en la ruta: " . $absolutePath);
    }
}

/**
 * Añade una tabla a la sección de PhpWord.
 */
function addTableToWord($section, $tableNode) {
    $tableStyle = ['borderSize' => 6, 'borderColor' => '999999', 'cellMargin' => 80, 'width' => 5000, 'unit' => 'pct'];
    $table = $section->addTable($tableStyle);

    $rows = $tableNode->getElementsByTagName('tr');
    foreach ($rows as $rowNode) {
        $table->addRow();
        $cells = $rowNode->childNodes; // Usar childNodes para obtener th y td
        foreach ($cells as $cellNode) {
            if ($cellNode instanceof \DOMElement && (strtolower($cellNode->nodeName) === 'td' || strtolower($cellNode->nodeName) === 'th')) {
                
                $isHeader = strtolower($cellNode->nodeName) === 'th';
                $cellStyle = $isHeader ? ['bgColor' => 'D9D9D9'] : [];
                
                $cell = $table->addCell(null, $cellStyle);
                
                // Extraer el contenido HTML de la celda para procesarlo
                $cellContentHtml = '';
                foreach ($cellNode->childNodes as $innerNode) {
                    $cellContentHtml .= $innerNode->ownerDocument->saveHTML($innerNode);
                }

                // Usar el helper de HTML para renderizar el contenido dentro de la celda
                if(!empty(trim($cellContentHtml))) {
                    Html::addHtml($cell, $cellContentHtml, false, false);
                } else {
                    $cell->addText(''); // Añadir celda vacía si no hay contenido
                }
            }
        }
    }
    $section->addTextBreak(1); // Espacio después de la tabla
}