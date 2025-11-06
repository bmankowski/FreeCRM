#!/usr/bin/env python3
"""
Script to find preProcess and postProcess methods in FreeCRM controllers
that render templates.

Author: bmankowski@gmail.com
"""

import os
import re
from pathlib import Path
from typing import List, Dict, Set


class ControllerAnalyzer:
    """Analyzes FreeCRM controllers for template rendering in pre/post processing."""
    
    def __init__(self, root_dir: str):
        self.root_dir = Path(root_dir)
        self.results = []
        # Common template rendering method patterns in Yii2/FreeCRM
        self.render_patterns = [
            r'\$this->display\s*\(',
            r'\$viewer->display\s*\(',
            r'\$viewer->view\s*\(',
            r'->render\s*\(',
            r'echo.*->fetch\s*\(',
            r'print.*->fetch\s*\(',
        ]
        
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
    
    def check_renders_template(self, method_body: str) -> Dict[str, any]:
        """Check if method body contains template rendering calls."""
        renders = []
        
        for pattern in self.render_patterns:
            matches = re.finditer(pattern, method_body, re.IGNORECASE)
            for match in matches:
                # Get context around the match
                start = max(0, match.start() - 50)
                end = min(len(method_body), match.end() + 100)
                context = method_body[start:end].strip()
                renders.append({
                    'pattern': pattern,
                    'match': match.group(),
                    'context': context
                })
        
        return renders
    
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
                renders = self.check_renders_template(method_body)
                
                if renders:
                    relative_path = file_path.relative_to(self.root_dir)
                    self.results.append({
                        'file': str(relative_path),
                        'class': class_name,
                        'method': method_name,
                        'renders': renders
                    })
    
    def run_analysis(self):
        """Run the full analysis."""
        print("=" * 80)
        print("FreeCRM Controller Analysis: Template Rendering in pre/postProcess")
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
            print("✓ No template rendering found in preProcess or postProcess methods!")
            return
        
        print(f"Found {len(self.results)} method(s) that render templates:")
        print()
        
        for idx, result in enumerate(self.results, 1):
            print(f"{idx}. {result['file']}")
            print(f"   Class: {result['class']}")
            print(f"   Method: {result['method']}()")
            print(f"   Renders found: {len(result['renders'])}")
            
            for render_idx, render in enumerate(result['renders'], 1):
                print(f"      {render_idx}. Match: {render['match']}")
                print(f"         Context: {render['context'][:100]}...")
            
            print()
        
        print("=" * 80)
        print(f"Summary: {len(self.results)} methods need refactoring")
        print("=" * 80)


def main():
    """Main entry point."""
    # Determine the project root directory
    script_dir = Path(__file__).parent
    project_root = script_dir.parent
    
    if not (project_root / 'src').exists():
        print(f"Error: Could not find src directory in {project_root}")
        print("Please run this script from the refacto/ directory of FreeCRM project")
        return 1
    
    analyzer = ControllerAnalyzer(project_root)
    analyzer.run_analysis()
    
    return 0


if __name__ == '__main__':
    exit(main())

