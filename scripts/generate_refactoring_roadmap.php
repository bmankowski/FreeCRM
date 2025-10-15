<?php
/**
 * Generate a prioritized refactoring roadmap based on violations analysis
 * Usage: php scripts/generate_refactoring_roadmap.php
 */

if (php_sapi_name() !== 'cli') {
    die('This script must be run from command line');
}

require_once __DIR__ . '/analyze_tpl_violations.php';

class RefactoringRoadmapGenerator {
    
    private $analyzer;
    private $violations = [];
    
    public function __construct() {
        $this->analyzer = new TplMvcAnalyzer();
    }
    
    public function analyzeCodebase() {
        echo "Analyzing TPL files in the codebase...\n\n";
        
        $directories = [
            'layouts/basic/modules',
            'install/tpl',
        ];
        
        foreach ($directories as $dir) {
            $fullPath = dirname(__DIR__) . '/' . $dir;
            if (is_dir($fullPath)) {
                echo "Scanning: $dir ... ";
                $count = $this->analyzer->analyzeDirectory($fullPath);
                echo "$count files\n";
            }
        }
        
        $this->violations = $this->analyzer->getViolations();
        echo "\nAnalysis complete!\n";
        echo "Total violations found: " . count($this->violations) . "\n\n";
    }
    
    public function generateRoadmap() {
        $roadmap = "";
        $roadmap .= "# TPL Refactoring Roadmap\n\n";
        $roadmap .= "**Generated:** " . date('Y-m-d H:i:s') . "\n\n";
        $roadmap .= "**Total Violations:** " . count($this->violations) . "\n\n";
        
        // Overview
        $roadmap .= "## Overview\n\n";
        $roadmap .= $this->generateOverview();
        $roadmap .= "\n";
        
        // Priority Files
        $roadmap .= "## High Priority Files\n\n";
        $roadmap .= $this->generateHighPriorityFiles();
        $roadmap .= "\n";
        
        // By Module
        $roadmap .= "## Violations by Module\n\n";
        $roadmap .= $this->generateByModule();
        $roadmap .= "\n";
        
        // Effort Estimate
        $roadmap .= "## Effort Estimates\n\n";
        $roadmap .= $this->generateEffortEstimate();
        $roadmap .= "\n";
        
        // Recommended Phases
        $roadmap .= "## Recommended Refactoring Phases\n\n";
        $roadmap .= $this->generatePhases();
        $roadmap .= "\n";
        
        return $roadmap;
    }
    
    private function generateOverview() {
        $byType = $this->analyzer->getViolationsByType();
        $bySeverity = $this->analyzer->getViolationsBySeverity();
        
        $overview = "### Violations by Severity\n\n";
        $overview .= "| Severity | Count | Percentage |\n";
        $overview .= "|----------|-------|------------|\n";
        
        $total = count($this->violations);
        foreach (['high', 'medium', 'low'] as $severity) {
            $count = count($bySeverity[$severity] ?? []);
            $pct = $total > 0 ? round(($count / $total) * 100, 1) : 0;
            $overview .= sprintf("| %s | %d | %.1f%% |\n", ucfirst($severity), $count, $pct);
        }
        
        $overview .= "\n### Violations by Type\n\n";
        $overview .= "| Type | Count |\n";
        $overview .= "|------|-------|\n";
        
        arsort($byType);
        foreach ($byType as $type => $count) {
            $overview .= sprintf("| %s | %d |\n", $type, $count);
        }
        
        return $overview;
    }
    
    private function generateHighPriorityFiles() {
        $byFile = $this->analyzer->getViolationsByFile();
        
        // Calculate priority score for each file
        $fileScores = [];
        foreach ($byFile as $file => $violations) {
            $score = 0;
            foreach ($violations as $violation) {
                $score += $this->getSeverityWeight($violation['severity']);
            }
            $fileScores[$file] = [
                'score' => $score,
                'violations' => $violations,
                'count' => count($violations),
            ];
        }
        
        // Sort by score descending
        uasort($fileScores, function($a, $b) {
            return $b['score'] - $a['score'];
        });
        
        $output = "Files with most critical violations (top 20):\n\n";
        $output .= "| File | Violations | Score | High | Med | Low |\n";
        $output .= "|------|------------|-------|------|-----|-----|\n";
        
        $count = 0;
        foreach ($fileScores as $file => $data) {
            if ($count++ >= 20) break;
            
            $high = $medium = $low = 0;
            foreach ($data['violations'] as $v) {
                switch ($v['severity']) {
                    case 'high': $high++; break;
                    case 'medium': $medium++; break;
                    case 'low': $low++; break;
                }
            }
            
            $output .= sprintf(
                "| %s | %d | %d | %d | %d | %d |\n",
                $file,
                $data['count'],
                $data['score'],
                $high,
                $medium,
                $low
            );
        }
        
        return $output;
    }
    
    private function generateByModule() {
        $byFile = $this->analyzer->getViolationsByFile();
        
        // Group by module
        $byModule = [];
        foreach ($byFile as $file => $violations) {
            // Extract module from path: layouts/basic/modules/ModuleName/...
            if (preg_match('#layouts/basic/modules/([^/]+)/#', $file, $matches)) {
                $module = $matches[1];
            } elseif (preg_match('#install/tpl/#', $file)) {
                $module = 'Install';
            } else {
                $module = 'Other';
            }
            
            if (!isset($byModule[$module])) {
                $byModule[$module] = [
                    'files' => 0,
                    'violations' => 0,
                ];
            }
            
            $byModule[$module]['files']++;
            $byModule[$module]['violations'] += count($violations);
        }
        
        // Sort by violation count
        uasort($byModule, function($a, $b) {
            return $b['violations'] - $a['violations'];
        });
        
        $output = "| Module | Files | Violations | Avg per File |\n";
        $output .= "|--------|-------|------------|-------------|\n";
        
        foreach ($byModule as $module => $data) {
            $avg = round($data['violations'] / $data['files'], 1);
            $output .= sprintf(
                "| %s | %d | %d | %.1f |\n",
                $module,
                $data['files'],
                $data['violations'],
                $avg
            );
        }
        
        return $output;
    }
    
    private function generateEffortEstimate() {
        $byFile = $this->analyzer->getViolationsByFile();
        
        $totalFiles = count($byFile);
        $totalViolations = count($this->violations);
        
        // Estimate: 
        // - Simple violations (config, json): 5 min each
        // - Medium violations (model calls): 15 min each
        // - Complex violations (business logic): 30 min each
        
        $simpleMinutes = 0;
        $mediumMinutes = 0;
        $complexMinutes = 0;
        
        foreach ($this->violations as $violation) {
            switch ($violation['type']) {
                case 'appconfig':
                case 'json_encode':
                case 'debugger':
                    $simpleMinutes += 5;
                    break;
                    
                case 'model_call':
                case 'model_instance':
                case 'util_helper':
                case 'vtlib_functions':
                case 'field_classes':
                case 'uitype_calls':
                    $mediumMinutes += 15;
                    break;
                    
                case 'privilege':
                case 'array_operations':
                case 'complex_assign':
                    $complexMinutes += 30;
                    break;
            }
        }
        
        $totalMinutes = $simpleMinutes + $mediumMinutes + $complexMinutes;
        $totalHours = round($totalMinutes / 60, 1);
        $totalDays = round($totalHours / 8, 1);
        
        $output = "**Conservative Estimates:**\n\n";
        $output .= "| Category | Violations | Time per Item | Total Time |\n";
        $output .= "|----------|------------|---------------|------------|\n";
        $output .= sprintf("| Simple (config, JSON) | - | 5 min | %d min (%.1f hrs) |\n", 
            $simpleMinutes, $simpleMinutes/60);
        $output .= sprintf("| Medium (model calls) | - | 15 min | %d min (%.1f hrs) |\n", 
            $mediumMinutes, $mediumMinutes/60);
        $output .= sprintf("| Complex (business logic) | - | 30 min | %d min (%.1f hrs) |\n", 
            $complexMinutes, $complexMinutes/60);
        $output .= sprintf("| **TOTAL** | **%d** | - | **%d min (%.1f hrs / %.1f days)** |\n", 
            $totalViolations, $totalMinutes, $totalHours, $totalDays);
        
        $output .= "\n**Notes:**\n";
        $output .= "- Estimates include time for testing\n";
        $output .= "- Does not include code review time\n";
        $output .= "- Assumes automated tools are used\n";
        $output .= "- Add 25% buffer for unexpected issues\n";
        $output .= "- **Recommended timeline: " . ceil($totalDays * 1.25) . " working days**\n";
        
        return $output;
    }
    
    private function generatePhases() {
        $output = "### Phase 1: Low-Hanging Fruit (Week 1)\n";
        $output .= "**Goal:** Fix simple, automated violations\n\n";
        $output .= "- Run automated refactoring on AppConfig calls\n";
        $output .= "- Fix JSON encoding violations\n";
        $output .= "- Update debugger calls\n";
        $output .= "- Target: ~40% of violations\n\n";
        
        $output .= "### Phase 2: Model Calls (Weeks 2-3)\n";
        $output .= "**Goal:** Move model instantiation to controllers\n\n";
        $output .= "- Identify all model instantiations\n";
        $output .= "- Update controllers to provide instances\n";
        $output .= "- Update templates to use provided instances\n";
        $output .= "- Target: ~30% of violations\n\n";
        
        $output .= "### Phase 3: Business Logic (Weeks 4-5)\n";
        $output .= "**Goal:** Extract business logic from templates\n\n";
        $output .= "- Move array operations to controllers\n";
        $output .= "- Extract permission checks\n";
        $output .= "- Move field formatting to models\n";
        $output .= "- Target: ~20% of violations\n\n";
        
        $output .= "### Phase 4: Complex Refactoring (Week 6)\n";
        $output .= "**Goal:** Handle edge cases and complex templates\n\n";
        $output .= "- Refactor templates with multiple violation types\n";
        $output .= "- Create helper methods for common patterns\n";
        $output .= "- Update documentation\n";
        $output .= "- Target: Remaining 10% of violations\n\n";
        
        $output .= "### Phase 5: Testing & Verification (Week 7)\n";
        $output .= "**Goal:** Ensure everything works\n\n";
        $output .= "- Full regression testing\n";
        $output .= "- Performance benchmarking\n";
        $output .= "- Code review\n";
        $output .= "- Documentation updates\n";
        
        return $output;
    }
    
    private function getSeverityWeight($severity) {
        switch ($severity) {
            case 'high': return 10;
            case 'medium': return 5;
            case 'low': return 1;
            default: return 0;
        }
    }
}

// CLI execution
if (php_sapi_name() === 'cli') {
    echo "\n";
    echo str_repeat('=', 80) . "\n";
    echo "TPL Refactoring Roadmap Generator\n";
    echo str_repeat('=', 80) . "\n\n";
    
    $generator = new RefactoringRoadmapGenerator();
    
    try {
        $generator->analyzeCodebase();
        
        echo "Generating roadmap...\n\n";
        $roadmap = $generator->generateRoadmap();
        
        // Save to file
        $roadmapFile = dirname(__DIR__) . '/documentation/tpl-refactoring-roadmap.md';
        file_put_contents($roadmapFile, $roadmap);
        
        echo str_repeat('=', 80) . "\n";
        echo "Roadmap saved to: $roadmapFile\n";
        echo str_repeat('=', 80) . "\n\n";
        
        echo $roadmap;
        
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
        exit(1);
    }
}

