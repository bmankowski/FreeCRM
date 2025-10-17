#!/usr/bin/env python3
"""
Script: \FreeCRM\Runtime\Vtiger_Language_Handler::translate_to_modifier_t.py
Description: Automatically replace \FreeCRM\Runtime\Vtiger_Language_Handler::translate() calls with Smarty modifier 't' in .tpl files
Usage: python3 \FreeCRM\Runtime\Vtiger_Language_Handler::translate_to_modifier_t.py [OPTIONS] [FILE_OR_DIRECTORY]
Author: FreeCRM Refactoring Tool
"""

import os
import re
import sys
import argparse
from pathlib import Path
from typing import List, Tuple

class Colors:
    RED = '\033[0;31m'
    GREEN = '\033[0;32m'
    YELLOW = '\033[1;33m'
    BLUE = '\033[0;34m'
    NC = '\033[0m'  # No Color

def print_colored(color: str, message: str) -> None:
    """Print colored output"""
    print(f"{color}{message}{Colors.NC}")

class \FreeCRM\Runtime\Vtiger_Language_Handler::translateRefactor:
    def __init__(self, dry_run: bool = False, verbose: bool = False):
        self.dry_run = dry_run
        self.verbose = verbose
        self.total_files = 0
        self.modified_files = 0
        self.total_replacements = 0
        
        # Regex patterns for different \FreeCRM\Runtime\Vtiger_Language_Handler::translate cases
        self.patterns = [
            # Pattern 1: \FreeCRM\Runtime\Vtiger_Language_Handler::translate('key') -> 'key'|t
            (r'\{vtranslate\([\'"](\w+)[\'"]\)\}', r"{\'\1\'|t}"),
            
            # Pattern 2: \FreeCRM\Runtime\Vtiger_Language_Handler::translate('key', $variable) -> 'key'|t:$variable
            (r'\{vtranslate\([\'"](\w+)[\'"],\s*(\$[A-Z_]+)\)\}', r"{\'\1\'|t:\2}"),
            
            # Pattern 3: \FreeCRM\Runtime\Vtiger_Language_Handler::translate('key', 'module') -> 'key'|t:'module'
            (r'\{vtranslate\([\'"](\w+)[\'"],\s*[\'"](\w+)[\'"]\)\}', r"{\'\1\'|t:\'\2\'}"),
            
            # Pattern 4: \FreeCRM\Runtime\Vtiger_Language_Handler::translate('key', $variable, param) -> 'key'|t:$variable:param
            (r'\{vtranslate\([\'"](\w+)[\'"],\s*(\$[A-Z_]+),\s*([^)]+)\)\}', r"{\'\1\'|t:\2:\3}"),
            
            # Pattern 5: \FreeCRM\Runtime\Vtiger_Language_Handler::translate({$variable}) -> {$variable}|t
            (r'\{vtranslate\(\{([^}]+)\}\)\}', r'{{\1}|t}'),
            
            # Pattern 6: \FreeCRM\Runtime\Vtiger_Language_Handler::translate({$variable}, $module) -> {$variable}|t:$module
            (r'\{vtranslate\(\{([^}]+)\},\s*(\$[A-Z_]+)\)\}', r'{{\1}|t:\2}'),
            
            # Pattern 7: \FreeCRM\Runtime\Vtiger_Language_Handler::translate({$variable}, 'module') -> {$variable}|t:'module'
            (r'\{vtranslate\(\{([^}]+)\},\s*[\'"](\w+)[\'"]\)\}', r'{{\1}|t:\'\2\'}'),
            
            # Pattern 8: \FreeCRM\Runtime\Vtiger_Language_Handler::translate(complex_expr, $variable) -> {complex_expr}|t:$variable
            (r'\{vtranslate\(([^,]+),\s*(\$[A-Z_]+)\)\}', r'{{\1}|t:\2}'),
            
            # Pattern 9: \FreeCRM\Runtime\Vtiger_Language_Handler::translate(complex_expr, 'module') -> {complex_expr}|t:'module'
            (r'\{vtranslate\(([^,]+),\s*[\'"](\w+)[\'"]\)\}', r'{{\1}|t:\'\2\'}'),
        ]
    
    def process_file(self, file_path: Path) -> int:
        """Process a single file and return number of replacements made"""
        if not file_path.exists():
            return 0
        
        # Check if file contains \FreeCRM\Runtime\Vtiger_Language_Handler::translate
        try:
            with open(file_path, 'r', encoding='utf-8', errors='ignore') as f:
                original_content = f.read()
        except Exception as e:
            if self.verbose:
                print_colored(Colors.RED, f"Error reading {file_path}: {e}")
            return 0
        
        if 'vtranslate(' not in original_content:
            return 0
        
        self.total_files += 1
        content = original_content
        total_replacements = 0
        
        if self.verbose:
            print_colored(Colors.BLUE, f"Processing: {file_path}")
        
        # Apply all patterns
        for pattern, replacement in self.patterns:
            matches = re.findall(pattern, content)
            if matches:
                content = re.sub(pattern, replacement, content)
                total_replacements += len(matches)
                if self.verbose:
                    print_colored(Colors.BLUE, f"  Applied pattern: {pattern[:30]}... ({len(matches)} matches)")
        
        if content != original_content:
            self.total_replacements += total_replacements
            
            if self.dry_run:
                print_colored(Colors.YELLOW, f"  Would modify {file_path} ({total_replacements} changes)")
                if self.verbose:
                    self._show_diff(original_content, content)
            else:
                # Apply changes
                try:
                    with open(file_path, 'w', encoding='utf-8') as f:
                        f.write(content)
                    self.modified_files += 1
                    print_colored(Colors.GREEN, f"  ✓ Modified {file_path} ({total_replacements} changes)")
                except Exception as e:
                    print_colored(Colors.RED, f"  Error writing {file_path}: {e}")
        
        return total_replacements
    
    def _show_diff(self, original: str, modified: str) -> None:
        """Show a simple diff of changes"""
        original_lines = original.split('\n')
        modified_lines = modified.split('\n')
        
        print("  Changes:")
        for i, (orig, mod) in enumerate(zip(original_lines, modified_lines)):
            if orig != mod and 'vtranslate(' in orig:
                print(f"    Line {i+1}:")
                print(f"    - {orig.strip()}")
                print(f"    + {mod.strip()}")
                print()
    
    def process_directory(self, dir_path: Path, file_pattern: str = "*.tpl") -> None:
        """Process directory recursively"""
        if not dir_path.exists():
            print_colored(Colors.RED, f"Error: Directory '{dir_path}' does not exist")
            return
        
        print_colored(Colors.BLUE, f"Scanning directory: {dir_path}")
        
        # Find files matching pattern
        if file_pattern == "*.tpl":
            files = list(dir_path.rglob("*.tpl"))
        elif file_pattern == "*.php":
            files = list(dir_path.rglob("*.php"))
        else:  # all files
            files = list(dir_path.rglob("*.tpl")) + list(dir_path.rglob("*.php"))
        
        for file_path in files:
            self.process_file(file_path)
    
    def show_summary(self) -> None:
        """Show summary of operations"""
        print()
        print_colored(Colors.BLUE, "=== Summary ===")
        print_colored(Colors.BLUE, f"Files scanned: {self.total_files}")
        
        if self.dry_run:
            print_colored(Colors.YELLOW, f"Files that would be modified: {self.modified_files}")
            print_colored(Colors.YELLOW, f"Total replacements that would be made: {self.total_replacements}")
        else:
            print_colored(Colors.GREEN, f"Files modified: {self.modified_files}")
            print_colored(Colors.GREEN, f"Total replacements made: {self.total_replacements}")
        
        if self.total_files == 0:
            print_colored(Colors.YELLOW, "No files with \FreeCRM\Runtime\Vtiger_Language_Handler::translate() found")
        
        print()
        if self.dry_run:
            print_colored(Colors.YELLOW, "This was a dry run. Use without --dry-run to apply changes.")
        else:
            print_colored(Colors.GREEN, "Refactoring completed successfully!")
            print_colored(Colors.BLUE, "Remember to test your application after these changes.")

def main():
    parser = argparse.ArgumentParser(
        description="Replace \FreeCRM\Runtime\Vtiger_Language_Handler::translate() calls with Smarty modifier 't'",
        formatter_class=argparse.RawDescriptionHelpFormatter,
        epilog="""
Examples:
  python3 \FreeCRM\Runtime\Vtiger_Language_Handler::translate_to_modifier_t.py                           # Process current directory
  python3 \FreeCRM\Runtime\Vtiger_Language_Handler::translate_to_modifier_t.py layouts/basic/modules/    # Process specific directory
  python3 \FreeCRM\Runtime\Vtiger_Language_Handler::translate_to_modifier_t.py file.tpl                 # Process specific file
  python3 \FreeCRM\Runtime\Vtiger_Language_Handler::translate_to_modifier_t.py --dry-run layouts/       # Preview changes
        """
    )
    
    parser.add_argument('target', nargs='?', default='.', 
                       help='File or directory to process (default: current directory)')
    parser.add_argument('-d', '--dry-run', action='store_true',
                       help='Show what would be changed without making changes')
    parser.add_argument('-v', '--verbose', action='store_true',
                       help='Show detailed output')
    parser.add_argument('--tpl-only', action='store_true', default=True,
                       help='Only process .tpl files (default)')
    parser.add_argument('--php-only', action='store_true',
                       help='Only process .php files')
    parser.add_argument('--all-files', action='store_true',
                       help='Process both .tpl and .php files')
    
    args = parser.parse_args()
    
    # Determine file pattern
    if args.php_only:
        file_pattern = "*.php"
    elif args.all_files:
        file_pattern = "all"
    else:
        file_pattern = "*.tpl"
    
    # Show configuration
    print_colored(Colors.BLUE, "=== \FreeCRM\Runtime\Vtiger_Language_Handler::translate to modifier 't' refactoring tool ===")
    if args.dry_run:
        print_colored(Colors.YELLOW, "Mode: DRY RUN (no changes will be made)")
    else:
        print_colored(Colors.GREEN, "Mode: LIVE (changes will be applied)")
    print_colored(Colors.BLUE, f"Target: {args.target}")
    print_colored(Colors.BLUE, f"File pattern: {file_pattern}")
    print()
    
    # Initialize refactorer
    refactorer = \FreeCRM\Runtime\Vtiger_Language_Handler::translateRefactor(dry_run=args.dry_run, verbose=args.verbose)
    
    # Process target
    target_path = Path(args.target)
    
    if target_path.is_file():
        refactorer.process_file(target_path)
    elif target_path.is_dir():
        refactorer.process_directory(target_path, file_pattern)
    else:
        print_colored(Colors.RED, f"Error: '{args.target}' is neither a file nor a directory")
        sys.exit(1)
    
    # Show summary
    refactorer.show_summary()

if __name__ == "__main__":
    main()
