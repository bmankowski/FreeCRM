#!/usr/bin/env python3
"""
FreeCRM View Assignment Analyzer

This script analyzes the inheritance tree of classes that extend BaseViewController
and extracts all @viewer assignments from preProcess methods.

Author: bmankowski@gmail.com
License: FreeCRM Public License 1.1
"""

import os
import re
import sys
import argparse
from pathlib import Path
from typing import Dict, List, Set, Tuple, Optional
from collections import defaultdict


class ViewClass:
    """Represents a View class with its metadata"""
    
    def __init__(self, name: str, namespace: str, parent: str, file_path: str):
        self.name = name
        self.namespace = namespace
        self.parent = parent
        self.file_path = file_path
        self.full_name = f"{namespace}\\{name}" if namespace else name
        self.viewer_assignments_preprocess: List[Tuple[str, str]] = []  # [(var_name, value_expr), ...]
        self.viewer_assignments_process: List[Tuple[str, str]] = []  # [(var_name, value_expr), ...]
        self.calls_parent_preprocess: bool = False
        self.calls_parent_process: bool = False
        self.children: List['ViewClass'] = []
    
    @property
    def viewer_assignments(self):
        """Combine both preProcess and process assignments for backward compatibility"""
        return self.viewer_assignments_preprocess + self.viewer_assignments_process
    
    def __repr__(self):
        return f"ViewClass({self.full_name})"


class ViewAnalyzer:
    """Analyzes PHP View classes and their inheritance"""
    
    def __init__(self, base_path: str):
        self.base_path = Path(base_path)
        self.classes: Dict[str, ViewClass] = {}
        self.class_by_simple_name: Dict[str, List[ViewClass]] = defaultdict(list)
        
    def find_view_files(self) -> List[Path]:
        """Find all PHP files in Modules/*/Views/ directories"""
        views_pattern = self.base_path / "src" / "Modules" / "*" / "Views" / "*.php"
        files = list(self.base_path.glob("src/Modules/*/Views/*.php"))
        
        # Also check BaseViewController
        base_controller = self.base_path / "src" / "Base" / "Controllers" / "BaseViewController.php"
        if base_controller.exists():
            files.append(base_controller)
            
        base_action_controller = self.base_path / "src" / "Base" / "Controllers" / "BaseActionController.php"
        if base_action_controller.exists():
            files.append(base_action_controller)
            
        return files
    
    def parse_php_file(self, file_path: Path) -> Optional[ViewClass]:
        """Parse a PHP file and extract class information"""
        try:
            with open(file_path, 'r', encoding='utf-8') as f:
                content = f.read()
        except Exception as e:
            print(f"Error reading {file_path}: {e}")
            return None
        
        # Extract namespace
        namespace_match = re.search(r'namespace\s+([\w\\]+)\s*;', content)
        namespace = namespace_match.group(1) if namespace_match else ""
        
        # Extract class name and parent
        class_match = re.search(
            r'(?:abstract\s+)?class\s+(\w+)\s+extends\s+([\w\\]+)',
            content
        )
        
        if not class_match:
            # Check if it's a class without extends
            class_match = re.search(r'(?:abstract\s+)?class\s+(\w+)\s*(?:\{|$)', content)
            if not class_match:
                return None
            class_name = class_match.group(1)
            parent = None
        else:
            class_name = class_match.group(1)
            parent = class_match.group(2)
        
        # Create ViewClass object
        view_class = ViewClass(
            name=class_name,
            namespace=namespace,
            parent=parent,
            file_path=str(file_path.relative_to(self.base_path))
        )
        
        # Extract viewer assignments from preProcess and process methods
        view_class.viewer_assignments_preprocess = self.extract_viewer_assignments(content, 'preProcess')
        view_class.viewer_assignments_process = self.extract_viewer_assignments(content, 'process')
        
        # Check if parent methods are called
        view_class.calls_parent_preprocess = self.check_parent_call(content, 'preProcess')
        view_class.calls_parent_process = self.check_parent_call(content, 'process')
        
        return view_class
    
    def check_parent_call(self, content: str, method_name: str) -> bool:
        """Check if parent::methodName() is called in the method"""
        method_body = self.extract_method_body(content, method_name)
        
        if not method_body:
            return False
        
        # Look for parent::methodName( in the method body
        pattern = rf'parent\s*::\s*{method_name}\s*\('
        return bool(re.search(pattern, method_body))
    
    def extract_method_body(self, content: str, method_name: str) -> Optional[str]:
        """Extract method body by counting braces properly"""
        # Find method start
        method_pattern = rf'public\s+function\s+{method_name}\s*\([^)]*\)'
        match = re.search(method_pattern, content)
        
        if not match:
            return None
        
        # Find the opening brace
        start_pos = match.end()
        brace_pos = content.find('{', start_pos)
        
        if brace_pos == -1:
            return None
        
        # Count braces to find matching closing brace
        brace_count = 1
        pos = brace_pos + 1
        in_string = False
        string_char = None
        escape_next = False
        
        while pos < len(content) and brace_count > 0:
            char = content[pos]
            
            if escape_next:
                escape_next = False
                pos += 1
                continue
            
            if char == '\\':
                escape_next = True
                pos += 1
                continue
            
            if in_string:
                if char == string_char:
                    in_string = False
            else:
                if char in ('"', "'"):
                    in_string = True
                    string_char = char
                elif char == '{':
                    brace_count += 1
                elif char == '}':
                    brace_count -= 1
            
            pos += 1
        
        if brace_count == 0:
            return content[brace_pos + 1:pos - 1]
        
        return None
    
    def extract_viewer_assignments(self, content: str, method_name: str = 'preProcess') -> List[Tuple[str, str]]:
        """Extract all $viewer->assign() calls from specified method"""
        assignments = []
        
        # Extract method body
        method_body = self.extract_method_body(content, method_name)
        
        if not method_body:
            return assignments
        
        # Find all $viewer->assign() calls
        # Pattern matches: $viewer->assign('KEY', value);
        # We need to handle both simple and complex values
        lines = method_body.split('\n')
        i = 0
        
        while i < len(lines):
            line = lines[i]
            
            # Check if line contains $viewer->assign(
            if '$viewer' in line and '->assign(' in line:
                # Try to extract the full statement (might span multiple lines)
                full_statement = line
                
                # Count parentheses to see if statement is complete
                open_parens = line.count('(') - line.count(')')
                j = i + 1
                
                while open_parens > 0 and j < len(lines):
                    full_statement += '\n' + lines[j]
                    open_parens += lines[j].count('(') - lines[j].count(')')
                    j += 1
                
                # Now extract the assignment
                assign_match = re.search(
                    r'\$viewer\s*->\s*assign\s*\(\s*([\'"])([^\'"]+)\1\s*,\s*(.+?)\s*\)\s*;',
                    full_statement,
                    re.DOTALL
                )
                
                if assign_match:
                    var_name = assign_match.group(2)
                    value_expr = assign_match.group(3).strip()
                    # Clean up the value expression
                    value_expr = re.sub(r'\s+', ' ', value_expr)
                    # Remove trailing parentheses and whitespace
                    value_expr = value_expr.rstrip()
                    assignments.append((var_name, value_expr))
                
                i = j
            else:
                i += 1
        
        return assignments
    
    def resolve_parent_class(self, parent: str, namespace: str) -> str:
        """Resolve parent class to full namespace"""
        if not parent:
            return None
        
        # If parent starts with \, it's already fully qualified
        if parent.startswith('\\'):
            return parent.lstrip('\\')
        
        # If parent contains \, it's a relative namespace
        if '\\' in parent:
            # Check if it's relative to current namespace
            if parent.startswith('App\\'):
                return parent
            # Otherwise prepend current namespace
            return f"{namespace}\\{parent}"
        
        # Simple class name - could be in same namespace or imported
        # First check same namespace
        full_name = f"{namespace}\\{parent}"
        if full_name in self.classes:
            return full_name
        
        # Check if there's a class with this simple name
        if parent in self.class_by_simple_name:
            candidates = self.class_by_simple_name[parent]
            if len(candidates) == 1:
                return candidates[0].full_name
            # Multiple candidates - prefer same top-level namespace
            ns_parts = namespace.split('\\')
            for candidate in candidates:
                cand_parts = candidate.namespace.split('\\')
                if ns_parts[0] == cand_parts[0]:
                    return candidate.full_name
        
        # Return as-is and hope for the best
        return parent
    
    def analyze(self):
        """Main analysis function"""
        print("Finding View files...")
        files = self.find_view_files()
        print(f"Found {len(files)} files to analyze\n")
        
        # Parse all files
        print("Parsing files...")
        for file_path in files:
            view_class = self.parse_php_file(file_path)
            if view_class:
                self.classes[view_class.full_name] = view_class
                self.class_by_simple_name[view_class.name].append(view_class)
        
        print(f"Parsed {len(self.classes)} classes\n")
        
        # Build inheritance tree
        print("Building inheritance tree...")
        for view_class in self.classes.values():
            if view_class.parent:
                parent_full = self.resolve_parent_class(view_class.parent, view_class.namespace)
                if parent_full and parent_full in self.classes:
                    parent_obj = self.classes[parent_full]
                    parent_obj.children.append(view_class)
    
    def find_root_classes(self) -> List[ViewClass]:
        """Find BaseViewController as the root for the tree"""
        roots = []
        
        # Look for BaseViewController specifically
        for view_class in self.classes.values():
            if view_class.name == 'BaseViewController':
                roots.append(view_class)
                break
        
        # If BaseViewController not found, fall back to finding natural roots
        if not roots:
            for view_class in self.classes.values():
                if not view_class.parent:
                    roots.append(view_class)
                    continue
                
                parent_full = self.resolve_parent_class(view_class.parent, view_class.namespace)
                if parent_full not in self.classes:
                    # Parent not in our list, so this is a root
                    roots.append(view_class)
        
        # Sort by name for consistent output
        return sorted(roots, key=lambda x: x.full_name)
    
    def get_all_assignments(self, view_class: ViewClass) -> Dict[str, str]:
        """Get all viewer assignments including inherited ones"""
        assignments = {}
        
        # Walk up the inheritance chain
        chain = []
        current = view_class
        visited = set()
        
        while current:
            if current.full_name in visited:
                break
            visited.add(current.full_name)
            chain.append(current)
            
            # Find parent
            if current.parent:
                parent_full = self.resolve_parent_class(current.parent, current.namespace)
                current = self.classes.get(parent_full)
            else:
                break
        
        # Process from top (base) to bottom (current class)
        # Process preProcess first, then process (so process can override preProcess)
        for cls in reversed(chain):
            for var_name, value_expr in cls.viewer_assignments_preprocess:
                assignments[var_name] = value_expr
            for var_name, value_expr in cls.viewer_assignments_process:
                assignments[var_name] = value_expr
        
        return assignments
    
    def print_tree(self, view_class: ViewClass, indent: int = 0, printed: Set[str] = None, show_inherited: bool = False):
        """Print inheritance tree with viewer assignments"""
        if printed is None:
            printed = set()
        
        if view_class.full_name in printed:
            return
        
        printed.add(view_class.full_name)
        
        prefix = "  " * indent
        
        # Print class name and file
        print(f"{prefix}📁 {view_class.name}")
        print(f"{prefix}   File: {view_class.file_path}")
        print(f"{prefix}   Namespace: {view_class.namespace or '(global)'}")
        
        # Print viewer assignments from preProcess
        if view_class.viewer_assignments_preprocess:
            if view_class.calls_parent_preprocess:
                parent_call_info = " ✓ calls parent::preProcess"
            else:
                parent_call_info = " ⚠ does NOT call parent::preProcess"
            print(f"{prefix}   Viewer Assignments in preProcess [{parent_call_info}]:")
            for var_name, value_expr in view_class.viewer_assignments_preprocess:
                # Truncate long values
                if len(value_expr) > 80:
                    value_expr = value_expr[:77] + "..."
                print(f"{prefix}      • {var_name} = {value_expr}")
        elif view_class.calls_parent_preprocess:
            print(f"{prefix}   preProcess: [✓ calls parent::preProcess, no local assignments]")
        
        # Print viewer assignments from process
        if view_class.viewer_assignments_process:
            if view_class.calls_parent_process:
                parent_call_info = " ✓ calls parent::process"
            else:
                parent_call_info = " ⚠ does NOT call parent::process"
            print(f"{prefix}   Viewer Assignments in process [{parent_call_info}]:")
            for var_name, value_expr in view_class.viewer_assignments_process:
                # Truncate long values
                if len(value_expr) > 80:
                    value_expr = value_expr[:77] + "..."
                print(f"{prefix}      • {var_name} = {value_expr}")
        elif view_class.calls_parent_process:
            print(f"{prefix}   process: [✓ calls parent::process, no local assignments]")
        
        # If no assignments at all and no parent calls
        if (not view_class.viewer_assignments_preprocess and 
            not view_class.viewer_assignments_process and 
            not view_class.calls_parent_preprocess and 
            not view_class.calls_parent_process):
            print(f"{prefix}   (no viewer assignments in preProcess or process)")
        
        # Optionally show cumulative assignments
        if show_inherited and indent > 0:  # Don't show for root
            all_assignments = self.get_all_assignments(view_class)
            if all_assignments and len(all_assignments) > len(view_class.viewer_assignments):
                print(f"{prefix}   Total Cumulative Assignments: {len(all_assignments)}")
        
        print()
        
        # Print children
        if view_class.children:
            sorted_children = sorted(view_class.children, key=lambda x: x.full_name)
            for child in sorted_children:
                self.print_tree(child, indent + 1, printed, show_inherited)
    
    def find_duplicate_assignments(self, view_class: ViewClass) -> List[Tuple[str, str, str]]:
        """Find assignments in preProcess that duplicate parent assignments
        
        Returns list of (var_name, value_expr, parent_class_name) for duplicates
        """
        duplicates = []
        
        # Only check if this class calls parent::preProcess
        if not view_class.calls_parent_preprocess:
            return duplicates
        
        # Build parent chain
        parent_assignments = {}  # {var_name: (value_expr, class_name)}
        current = view_class
        visited = set()
        
        while current:
            if current.full_name in visited:
                break
            visited.add(current.full_name)
            
            # Find parent
            if current.parent:
                parent_full = self.resolve_parent_class(current.parent, current.namespace)
                current = self.classes.get(parent_full)
                
                # Collect parent's preProcess assignments
                if current:
                    for var_name, value_expr in current.viewer_assignments_preprocess:
                        # Keep the first (highest) parent that sets this variable
                        if var_name not in parent_assignments:
                            parent_assignments[var_name] = (value_expr, current.name)
            else:
                break
        
        # Check this class's assignments against parent assignments
        for var_name, value_expr in view_class.viewer_assignments_preprocess:
            if var_name in parent_assignments:
                parent_value, parent_class = parent_assignments[var_name]
                # Check if value expression is exactly the same (true duplicate)
                if value_expr == parent_value:
                    duplicates.append((var_name, value_expr, parent_class))
        
        return duplicates
    
    def print_duplicate_tree(self, view_class: ViewClass, indent: int = 0, printed: Set[str] = None):
        """Print tree showing only classes with duplicate preProcess assignments"""
        if printed is None:
            printed = set()
        
        if view_class.full_name in printed:
            return
        
        printed.add(view_class.full_name)
        
        prefix = "  " * indent
        
        # Find duplicates for this class
        duplicates = self.find_duplicate_assignments(view_class)
        
        # Only print if there are duplicates
        if duplicates:
            print(f"{prefix}📁 {view_class.name}")
            print(f"{prefix}   File: {view_class.file_path}")
            print(f"{prefix}   Duplicate assignments in preProcess (already set by parent):")
            for var_name, value_expr, parent_class in duplicates:
                # Truncate long values
                if len(value_expr) > 60:
                    value_expr = value_expr[:57] + "..."
                print(f"{prefix}      ⚠️  {var_name} = {value_expr}")
                print(f"{prefix}          (already set by {parent_class})")
            print()
        
        # Always recurse to children
        if view_class.children:
            sorted_children = sorted(view_class.children, key=lambda x: x.full_name)
            for child in sorted_children:
                self.print_duplicate_tree(child, indent + 1 if duplicates else indent, printed)
    
    def print_summary(self):
        """Print a summary of all viewer assignments by variable name"""
        print("\n" + "=" * 80)
        print("SUMMARY: All Viewer Assignments by Variable Name")
        print("=" * 80 + "\n")
        
        # Collect all assignments
        all_assignments = defaultdict(list)
        for view_class in self.classes.values():
            for var_name, value_expr in view_class.viewer_assignments_preprocess:
                all_assignments[var_name].append((view_class.name, value_expr, 'preProcess'))
            for var_name, value_expr in view_class.viewer_assignments_process:
                all_assignments[var_name].append((view_class.name, value_expr, 'process'))
        
        # Print grouped by variable name
        for var_name in sorted(all_assignments.keys()):
            print(f"\n{var_name}:")
            assignments = all_assignments[var_name]
            
            # Group by unique value expressions and method
            unique_values = {}
            for class_name, value_expr, method in assignments:
                key = (value_expr, method)
                if key not in unique_values:
                    unique_values[key] = []
                unique_values[key].append(class_name)
            
            for (value_expr, method), class_names in sorted(unique_values.items()):
                # Truncate long values
                if len(value_expr) > 100:
                    value_expr = value_expr[:97] + "..."
                print(f"   {value_expr}")
                print(f"      Used in {method}: {', '.join(sorted(set(class_names)))}")


    def print_class_cumulative(self, class_name: str):
        """Print cumulative assignments for a specific class"""
        # Find the class
        matching_classes = []
        
        for full_name, view_class in self.classes.items():
            if view_class.name == class_name or full_name == class_name:
                matching_classes.append(view_class)
        
        if not matching_classes:
            print(f"Class '{class_name}' not found!")
            print(f"\nAvailable classes:")
            for cls in sorted(self.classes.values(), key=lambda x: x.name):
                print(f"  - {cls.name} ({cls.full_name})")
            return
        
        for view_class in matching_classes:
            print("=" * 80)
            print(f"Cumulative Viewer Assignments for: {view_class.name}")
            print(f"Full name: {view_class.full_name}")
            print(f"File: {view_class.file_path}")
            print("=" * 80 + "\n")
            
            # Get inheritance chain
            chain = []
            current = view_class
            visited = set()
            
            while current:
                if current.full_name in visited:
                    break
                visited.add(current.full_name)
                chain.append(current)
                
                if current.parent:
                    parent_full = self.resolve_parent_class(current.parent, current.namespace)
                    current = self.classes.get(parent_full)
                else:
                    break
            
            # Print inheritance chain
            print("Inheritance Chain:")
            for i, cls in enumerate(reversed(chain)):
                indent = "  " * i
                print(f"{indent}└─ {cls.name}")
            
            print("\n" + "-" * 80)
            print("Assignments by Class (in inheritance order):")
            print("-" * 80 + "\n")
            
            # Print assignments from each class in chain
            all_vars = {}
            for cls in reversed(chain):
                if cls.viewer_assignments_preprocess or cls.viewer_assignments_process or cls.calls_parent_preprocess or cls.calls_parent_process:
                    print(f"{cls.name}:")
                    
                    if cls.viewer_assignments_preprocess:
                        if cls.calls_parent_preprocess:
                            parent_info = " [✓ calls parent::preProcess]"
                        else:
                            parent_info = " [⚠ does NOT call parent::preProcess]"
                        print(f"  preProcess{parent_info}:")
                        for var_name, value_expr in cls.viewer_assignments_preprocess:
                            if len(value_expr) > 100:
                                value_expr = value_expr[:97] + "..."
                            print(f"    • {var_name} = {value_expr}")
                            all_vars[var_name] = (value_expr, cls.name, 'preProcess')
                    elif cls.calls_parent_preprocess:
                        print(f"  preProcess: [✓ calls parent::preProcess, no local assignments]")
                    
                    if cls.viewer_assignments_process:
                        if cls.calls_parent_process:
                            parent_info = " [✓ calls parent::process]"
                        else:
                            parent_info = " [⚠ does NOT call parent::process]"
                        print(f"  process{parent_info}:")
                        for var_name, value_expr in cls.viewer_assignments_process:
                            if len(value_expr) > 100:
                                value_expr = value_expr[:97] + "..."
                            print(f"    • {var_name} = {value_expr}")
                            all_vars[var_name] = (value_expr, cls.name, 'process')
                    elif cls.calls_parent_process:
                        print(f"  process: [✓ calls parent::process, no local assignments]")
                    
                    print()
            
            # Print final cumulative list
            print("-" * 80)
            print(f"Final Cumulative Assignments ({len(all_vars)} total):")
            print("-" * 80 + "\n")
            
            for var_name in sorted(all_vars.keys()):
                value_expr, from_class, from_method = all_vars[var_name]
                if len(value_expr) > 80:
                    value_expr = value_expr[:77] + "..."
                print(f"  {var_name:<25} = {value_expr}")
                print(f"  {'':<25}   (from {from_class}.{from_method})")
                print()


def main():
    """Main entry point"""
    parser = argparse.ArgumentParser(
        description='Analyze FreeCRM View classes and their viewer assignments',
        formatter_class=argparse.RawDescriptionHelpFormatter,
        epilog="""
Examples:
  %(prog)s                          # Show full inheritance tree
  %(prog)s --class ListView         # Show cumulative assignments for ListView
  %(prog)s --summary-only           # Show only the summary
  %(prog)s --class Detail --quiet   # Show class info without the tree
        """
    )
    
    parser.add_argument(
        '--class', '-c',
        dest='class_name',
        help='Show cumulative assignments for a specific class'
    )
    
    parser.add_argument(
        '--summary-only', '-s',
        action='store_true',
        help='Show only the summary of all assignments'
    )
    
    parser.add_argument(
        '--duplicates-only', '-d',
        action='store_true',
        help='Show only the duplicate assignments tree'
    )
    
    parser.add_argument(
        '--quiet', '-q',
        action='store_true',
        help='Suppress the full tree output (useful with --class)'
    )
    
    args = parser.parse_args()
    
    # Get script directory
    script_dir = Path(__file__).parent.resolve()
    
    if not args.quiet:
        print("=" * 80)
        print("FreeCRM View Assignment Analyzer")
        print("=" * 80)
        print(f"\nAnalyzing project at: {script_dir}\n")
    
    analyzer = ViewAnalyzer(script_dir)
    analyzer.analyze()
    
    # Handle specific class query
    if args.class_name:
        analyzer.print_class_cumulative(args.class_name)
        return
    
    # Get roots for tree display
    roots = analyzer.find_root_classes()
    
    # Print inheritance trees (unless quiet, summary-only, or duplicates-only)
    if not args.quiet and not args.summary_only and not args.duplicates_only:
        print("\n" + "=" * 80)
        print("INHERITANCE TREE WITH VIEWER ASSIGNMENTS")
        print("=" * 80 + "\n")
        
        printed = set()
        for root in roots:
            analyzer.print_tree(root, printed=printed)
    
    # Print duplicate assignments tree (unless summary-only)
    if not args.quiet and not args.summary_only:
        print("\n" + "=" * 80)
        print("DUPLICATE ASSIGNMENTS TREE")
        print("Classes with duplicate preProcess assignments (same var + value as parent)")
        print("=" * 80 + "\n")
        
        printed_dups = set()
        for root in roots:
            analyzer.print_duplicate_tree(root, printed=printed_dups)
        
        # Count total duplicates
        total_dups = 0
        for view_class in analyzer.classes.values():
            duplicates = analyzer.find_duplicate_assignments(view_class)
            total_dups += len(duplicates)
        
        if total_dups == 0:
            print("✅ No duplicate assignments found! All classes are clean.\n")
        else:
            print(f"\n⚠️  Total duplicate assignments found: {total_dups}\n")
    
    # Print summary (unless specific class was requested or duplicates-only)
    if not args.class_name and not args.duplicates_only:
        analyzer.print_summary()
    
    if not args.quiet:
        print("\n" + "=" * 80)
        print(f"Analysis complete! Total classes: {len(analyzer.classes)}")
        print("=" * 80)


if __name__ == "__main__":
    main()

