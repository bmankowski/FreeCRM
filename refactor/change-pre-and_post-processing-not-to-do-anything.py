#!/usr/bin/env python3
"""
Script to find preProcess and postProcess methods in FreeCRM controllers
that do actual work (not just call parent and return).

Author: bmankowski@gmail.com
"""

import os
import re
from pathlib import Path
from typing import List, Dict, Set


class MethodAnalyzer:
    """Analyzes FreeCRM controller methods for actual work being done."""
    
    def __init__(self, root_dir: str):
        self.root_dir = Path(root_dir)
        self.results = []
        
    def find_controller_files(self) -> List[Path]:
        """Find all controller/view files in the project."""
        controller_files = []
        
        # Search in src/Modules/*/Views/ and src/Modules/Settings/*/Views/
        src_path = self.root_dir / 'src' / 'Modules'
        
        if src_path.exists():
            for module_path in src_path.iterdir():
                if module_path.is_dir():
                    # Check regular Views directory
                    views_path = module_path / 'Views'
                    if views_path.exists():
                        controller_files.extend(views_path.glob('**/*.php'))
                    
                    # Check Settings subdirectory
                    settings_path = module_path / 'Settings'
                    if settings_path.is_dir():
                        for settings_module in settings_path.iterdir():
                            if settings_module.is_dir():
                                settings_views = settings_module / 'Views'
                                if settings_views.exists():
                                    controller_files.extend(settings_views.glob('**/*.php'))
        
        return sorted(controller_files)
    
    def extract_method_body(self, content: str, method_name: str) -> str:
        """Extract the body of a specific method from PHP code."""
        # Pattern to match method declaration
        pattern = rf'(public|protected|private)\s+function\s+{method_name}\s*\([^)]*\)\s*{{?'
        
        match = re.search(pattern, content)
        if not match:
            return ""
        
        start_pos = match.end()
        
        # Handle single-line methods without braces (unlikely but possible)
        if not content[match.end()-1] == '{':
            # Look for the opening brace
            brace_pos = content.find('{', start_pos)
            if brace_pos == -1:
                return ""
            start_pos = brace_pos + 1
        
        # Find matching closing brace
        brace_count = 1
        pos = start_pos
        
        while pos < len(content) and brace_count > 0:
            if content[pos] == '{':
                brace_count += 1
            elif content[pos] == '}':
                brace_count -= 1
            pos += 1
        
        if brace_count == 0:
            return content[start_pos:pos-1]
        
        return ""
    
    def does_actual_work(self, method_body: str) -> Dict[str, any]:
        """Check if method body does actual work beyond calling parent."""
        if not method_body:
            return {'does_work': False, 'reason': 'Empty method'}
        
        # Remove comments and clean up whitespace
        cleaned = re.sub(r'//.*?$', '', method_body, flags=re.MULTILINE)
        cleaned = re.sub(r'/\*.*?\*/', '', cleaned, flags=re.DOTALL)
        cleaned = cleaned.strip()
        
        # Check if method only calls parent and/or returns
        # Pattern: just "parent::methodName(...); return ...;" or "return true;" etc.
        simple_patterns = [
            r'^\s*parent::\w+\([^)]*\);\s*$',  # Just parent call
            r'^\s*return\s*;?\s*$',  # Just return
            r'^\s*return\s+true\s*;?\s*$',  # Just return true
            r'^\s*return\s+false\s*;?\s*$',  # Just return false
            r'^\s*parent::\w+\([^)]*\);\s*return\s*;?\s*$',  # parent then return
            r'^\s*parent::\w+\([^)]*\);\s*return\s+true\s*;?\s*$',  # parent then return true
            r'^\s*parent::\w+\([^)]*\);\s*return\s+false\s*;?\s*$',  # parent then return false
        ]
        
        for pattern in simple_patterns:
            if re.match(pattern, cleaned, re.DOTALL):
                return {'does_work': False, 'reason': 'Only calls parent/returns'}
        
        # Check for comment-only methods
        lines = [line.strip() for line in cleaned.split('\n') if line.strip()]
        if not lines or all(line.startswith('//') or line.startswith('/*') or line.startswith('*') for line in lines):
            return {'does_work': False, 'reason': 'Only comments'}
        
        # If we get here, the method does actual work
        # Let's identify what kind of work
        work_types = []
        
        if re.search(r'\$viewer->assign\s*\(', cleaned):
            work_types.append('viewer assignments')
        if re.search(r'\$viewer->view\s*\(', cleaned) or re.search(r'echo.*->fetch\s*\(', cleaned):
            work_types.append('renders templates')
        if re.search(r'\$this->\w+\s*=', cleaned):
            work_types.append('sets properties')
        if re.search(r'if\s*\(', cleaned):
            work_types.append('conditional logic')
        if re.search(r'new\s+\\', cleaned):
            work_types.append('creates objects')
        if re.search(r'->\w+\([^)]*\)', cleaned) and not re.search(r'^\s*parent::', cleaned):
            work_types.append('method calls')
        
        # Get a preview of the work being done
        preview_lines = cleaned.split('\n')[:5]  # First 5 lines
        preview = '\n'.join(preview_lines)
        if len(preview_lines) < len(cleaned.split('\n')):
            preview += '\n...'
        
        return {
            'does_work': True,
            'work_types': work_types,
            'preview': preview,
            'full_body': method_body
        }
    
    def analyze_file(self, file_path: Path):
        """Analyze a single controller file."""
        try:
            content = file_path.read_text(encoding='utf-8')
        except Exception as e:
            print(f"Error reading {file_path}: {e}")
            return
        
        # Extract class name
        class_match = re.search(r'class\s+(\w+)', content)
        class_name = class_match.group(1) if class_match else "Unknown"
        
        # Check for preProcess and postProcess methods
        for method_name in ['preProcess', 'postProcess']:
            method_body = self.extract_method_body(content, method_name)
            
            if method_body:
                analysis = self.does_actual_work(method_body)
                
                if analysis['does_work']:
                    relative_path = file_path.relative_to(self.root_dir)
                    self.results.append({
                        'file': str(relative_path),
                        'class': class_name,
                        'method': method_name,
                        'work_types': analysis.get('work_types', []),
                        'preview': analysis.get('preview', ''),
                        'line_count': len(method_body.split('\n'))
                    })
    
    def run_analysis(self):
        """Run the full analysis."""
        print("=" * 80)
        print("FreeCRM Controller Analysis: pre/postProcess Methods Doing Work")
        print("=" * 80)
        print()
        
        controller_files = self.find_controller_files()
        print(f"Found {len(controller_files)} controller files to analyze...")
        print()
        
        for file_path in controller_files:
            self.analyze_file(file_path)
        
        self.display_results()
    
    def display_results(self):
        """Display the analysis results."""
        if not self.results:
            print("✓ All preProcess and postProcess methods are clean!")
            print("  (They only call parent and/or return)")
            return
        
        # Group by type of work
        renders = [r for r in self.results if 'renders templates' in r.get('work_types', [])]
        assigns = [r for r in self.results if 'viewer assignments' in r.get('work_types', []) and 'renders templates' not in r.get('work_types', [])]
        others = [r for r in self.results if r not in renders and r not in assigns]
        
        print(f"Found {len(self.results)} method(s) that do actual work:")
        print()
        
        if renders:
            print(f"=== METHODS THAT RENDER TEMPLATES ({len(renders)}) ===")
            print()
            for idx, result in enumerate(renders, 1):
                self._display_result(idx, result)
            print()
        
        if assigns:
            print(f"=== METHODS THAT ONLY ASSIGN DATA ({len(assigns)}) ===")
            print()
            for idx, result in enumerate(assigns, 1):
                self._display_result(idx, result)
            print()
        
        if others:
            print(f"=== METHODS WITH OTHER WORK ({len(others)}) ===")
            print()
            for idx, result in enumerate(others, 1):
                self._display_result(idx, result)
            print()
        
        print("=" * 80)
        print(f"Summary:")
        print(f"  - {len(renders)} methods render templates (MUST fix)")
        print(f"  - {len(assigns)} methods only assign data (should move to process)")
        print(f"  - {len(others)} methods do other work (need review)")
        print(f"  - Total: {len(self.results)} methods need attention")
        print("=" * 80)
    
    def _display_result(self, idx: int, result: Dict):
        """Display a single result."""
        print(f"{idx}. {result['file']}")
        print(f"   Class: {result['class']}")
        print(f"   Method: {result['method']}()")
        print(f"   Work types: {', '.join(result['work_types'])}")
        print(f"   Lines: {result['line_count']}")
        print(f"   Preview:")
        for line in result['preview'].split('\n'):
            print(f"      {line}")
        print()


def main():
    """Main entry point."""
    # Determine the project root directory
    script_dir = Path(__file__).parent
    project_root = script_dir.parent
    
    if not (project_root / 'src').exists():
        print(f"Error: Could not find src directory in {project_root}")
        print("Please run this script from the refacto/ directory of FreeCRM project")
        return 1
    
    analyzer = MethodAnalyzer(project_root)
    analyzer.run_analysis()
    
    return 0


if __name__ == '__main__':
    exit(main())

