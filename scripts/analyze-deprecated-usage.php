<?php
/**
 * FreeCRM - Script do analizy użycia funkcji deprecated
 * 
 * @project FreeCRM
 * @author bmankowski@gmail.com
 * @copyright (c) FreeCRM
 * @license FreeCRM Public License 1.0
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

/**
 * Analizator użycia deprecated funkcji
 */
class DeprecatedUsageAnalyzer
{
    private array $deprecatedFunctions = [
        'vtlib\\Deprecated' => [
            'getSqlForNameInDisplayFormat',
            'checkFileAccessForInclusion',
            'getFullNameFromArray',
            'getFullNameFromQResult',
            'getModuleTranslationStrings',
            'checkFileAccess',
            'checkFileAccessForDeletion',
            'getBlockId',
            'createModuleMetaFile',
            'return_app_list_strings_language',
            'isFileAccessible',
            'getSettingsBlockId',
            'getSmartyCompiledTemplateFile',
            'getIdOfCustomViewByNameAll',
        ],
        'Record::getCurrentUserModel' => [
            'getCurrentUserModel',
        ],
        'Record::getCurrentUserId' => [
            'getCurrentUserId',
        ],
        'CurrentUser::get' => [
            'CurrentUser::get',
            'CurrentUser::getId',
        ],
    ];

    private string $projectRoot;
    private array $results = [];

    public function __construct(string $projectRoot)
    {
        $this->projectRoot = $projectRoot;
    }

    /**
     * Główna metoda analizy
     */
    public function analyze(): array
    {
        echo "🔍 Analizowanie użycia deprecated funkcji...\n\n";

        foreach ($this->deprecatedFunctions as $category => $functions) {
            echo "📦 Kategoria: {$category}\n";
            $this->results[$category] = [];

            foreach ($functions as $function) {
                $usage = $this->findUsage($function);
                $this->results[$category][$function] = $usage;
                
                $count = count($usage);
                $status = $count > 0 ? "⚠️  {$count} użyć" : "✅ Brak użyć";
                echo "  - {$function}: {$status}\n";
            }
            echo "\n";
        }

        return $this->results;
    }

    /**
     * Znajdź użycia funkcji w kodzie
     */
    private function findUsage(string $function): array
    {
        $usage = [];
        $pattern = $this->getSearchPattern($function);

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->projectRoot)
        );

        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            // Pomijamy vendor i cache
            $relativePath = str_replace($this->projectRoot . '/', '', $file->getPathname());
            if (strpos($relativePath, 'vendor/') === 0 || 
                strpos($relativePath, 'cache/') === 0 ||
                strpos($relativePath, 'node_modules/') === 0) {
                continue;
            }

            $content = file_get_contents($file->getPathname());
            if (preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
                foreach ($matches[0] as $match) {
                    $lineNumber = substr_count(substr($content, 0, $match[1]), "\n") + 1;
                    $usage[] = [
                        'file' => $relativePath,
                        'line' => $lineNumber,
                        'context' => $this->getContext($content, $match[1]),
                    ];
                }
            }
        }

        return $usage;
    }

    /**
     * Pobierz wzorzec wyszukiwania dla funkcji
     */
    private function getSearchPattern(string $function): string
    {
        // Escapowanie specjalnych znaków
        $escaped = preg_quote($function, '/');

        // Różne wzorce w zależności od typu funkcji
        if (strpos($function, '::') !== false) {
            // Metoda statyczna: CurrentUser::get
            return '/' . $escaped . '\s*\(/';
        } elseif (strpos($function, 'vtlib\\Deprecated') !== false) {
            // vtlib\Deprecated::functionName
            return '/\\\\?vtlib\\\\Deprecated::' . preg_quote(str_replace('vtlib\\Deprecated::', '', $function), '/') . '\s*\(/';
        } else {
            // Metoda klasy: getCurrentUserModel
            return '/->' . $escaped . '\s*\(|::' . $escaped . '\s*\(/';
        }
    }

    /**
     * Pobierz kontekst użycia (3 linie przed i po)
     */
    private function getContext(string $content, int $offset): string
    {
        $start = max(0, $offset - 200);
        $length = 400;
        $snippet = substr($content, $start, $length);
        
        // Znajdź początek linii
        $lineStart = strrpos(substr($content, 0, $offset), "\n");
        if ($lineStart === false) {
            $lineStart = 0;
        } else {
            $lineStart = $offset - ($offset - $lineStart);
        }

        $line = substr($content, $lineStart, strpos(substr($content, $lineStart), "\n") ?: 200);
        return trim($line);
    }

    /**
     * Generuj raport HTML
     */
    public function generateHtmlReport(string $outputFile): void
    {
        $html = "<!DOCTYPE html>\n<html>\n<head>\n";
        $html .= "<meta charset='UTF-8'>\n";
        $html .= "<title>Raport Użycia Deprecated Funkcji</title>\n";
        $html .= "<style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            h1 { color: #333; }
            h2 { color: #666; margin-top: 30px; }
            table { border-collapse: collapse; width: 100%; margin-top: 20px; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #f2f2f2; }
            .file { font-family: monospace; color: #0066cc; }
            .line { color: #666; }
            .context { font-family: monospace; background-color: #f9f9f9; padding: 5px; }
            .count { font-weight: bold; }
            .high { color: #d32f2f; }
            .medium { color: #f57c00; }
            .low { color: #388e3c; }
        </style>\n";
        $html .= "</head>\n<body>\n";
        $html .= "<h1>📊 Raport Użycia Deprecated Funkcji</h1>\n";
        $html .= "<p>Wygenerowano: " . date('Y-m-d H:i:s') . "</p>\n";

        foreach ($this->results as $category => $functions) {
            $html .= "<h2>{$category}</h2>\n";
            $html .= "<table>\n";
            $html .= "<tr><th>Funkcja</th><th>Liczba użyć</th><th>Plik</th><th>Linia</th><th>Kontekst</th></tr>\n";

            foreach ($functions as $function => $usage) {
                $count = count($usage);
                $priority = $this->getPriority($count);
                
                if ($count === 0) {
                    $html .= "<tr><td>{$function}</td><td class='count low'>0</td><td colspan='3'>✅ Brak użyć</td></tr>\n";
                } else {
                    $first = true;
                    foreach ($usage as $item) {
                        $rowspan = $first ? " rowspan='{$count}'" : "";
                        $countCell = $first ? "<td class='count {$priority}'{$rowspan}>{$count}</td>" : "";
                        $html .= "<tr>";
                        if ($first) {
                            $html .= "<td{$rowspan}>{$function}</td>";
                        }
                        $html .= $countCell;
                        $html .= "<td class='file'>{$item['file']}</td>";
                        $html .= "<td class='line'>{$item['line']}</td>";
                        $html .= "<td class='context'>" . htmlspecialchars($item['context']) . "</td>";
                        $html .= "</tr>\n";
                        $first = false;
                    }
                }
            }

            $html .= "</table>\n";
        }

        $html .= "</body>\n</html>\n";

        file_put_contents($outputFile, $html);
        echo "📄 Raport HTML zapisany do: {$outputFile}\n";
    }

    /**
     * Generuj raport JSON
     */
    public function generateJsonReport(string $outputFile): void
    {
        $json = json_encode($this->results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        file_put_contents($outputFile, $json);
        echo "📄 Raport JSON zapisany do: {$outputFile}\n";
    }

    /**
     * Określ priorytet na podstawie liczby użyć
     */
    private function getPriority(int $count): string
    {
        if ($count >= 20) {
            return 'high';
        } elseif ($count >= 5) {
            return 'medium';
        } else {
            return 'low';
        }
    }

    /**
     * Wyświetl podsumowanie
     */
    public function printSummary(): void
    {
        echo "\n" . str_repeat("=", 60) . "\n";
        echo "📊 PODSUMOWANIE\n";
        echo str_repeat("=", 60) . "\n\n";

        $totalUsage = 0;
        $categories = [];

        foreach ($this->results as $category => $functions) {
            $categoryTotal = 0;
            foreach ($functions as $function => $usage) {
                $count = count($usage);
                $categoryTotal += $count;
                $totalUsage += $count;
            }
            $categories[$category] = $categoryTotal;
        }

        echo "Całkowita liczba użyć deprecated funkcji: {$totalUsage}\n\n";
        echo "Według kategorii:\n";
        foreach ($categories as $category => $count) {
            $priority = $this->getPriority($count);
            $icon = $count > 0 ? "⚠️" : "✅";
            echo "  {$icon} {$category}: {$count} użyć\n";
        }

        echo "\n" . str_repeat("=", 60) . "\n";
    }
}

// Uruchomienie skryptu
if (php_sapi_name() === 'cli') {
    $projectRoot = dirname(__DIR__);
    $analyzer = new DeprecatedUsageAnalyzer($projectRoot);
    
    $results = $analyzer->analyze();
    $analyzer->printSummary();
    
    // Generuj raporty
    $reportsDir = $projectRoot . '/reports';
    if (!is_dir($reportsDir)) {
        mkdir($reportsDir, 0755, true);
    }
    
    $analyzer->generateHtmlReport($reportsDir . '/deprecated-usage-report.html');
    $analyzer->generateJsonReport($reportsDir . '/deprecated-usage-report.json');
    
    echo "\n✅ Analiza zakończona!\n";
} else {
    echo "Ten skrypt może być uruchomiony tylko z linii poleceń.\n";
}

