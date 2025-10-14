#!/usr/bin/env python3
"""
Simple vtranslate to modifier 't' refactoring tool
Handles only basic cases to avoid syntax errors
"""

import re
import argparse
from pathlib import Path

class SimpleVtranslateRefactorer:
    def __init__(self, dry_run: bool, verbose: bool, file_pattern: str):
        self.dry_run = dry_run
        self.verbose = verbose
        self.file_pattern = file_pattern
        self.total_files = 0
        self.modified_files = 0
        self.total_replacements = 0
        
        # Only handle simple, safe patterns
        self.patterns = [
            # Pattern 1: vtranslate('key') -> "key"|t
            (r'\{vtranslate\([\'"]([A-Za-z_][A-Za-z0-9_]*)[\'"]\)\}', r'{"\1"|t}'),
            
            # Pattern 2: vtranslate('key', 'module') -> "key"|t:'module'
            (r'\{vtranslate\([\'"]([A-Za-z_][A-Za-z0-9_]*)[\'"],\s*[\'"]([A-Za-z_][A-Za-z0-9_]*)[\'"]\)\}', r'{"\1"|t:"\2"}'),
            
            # Pattern 3: vtranslate('key', $MODULE_NAME) -> "key"|t:$MODULE_NAME
            (r'\{vtranslate\([\'"]([A-Za-z_][A-Za-z0-9_\s]*)[\'"]\s*,\s*(\$MODULE_NAME)\)\}', r'{"\1"|t:\2}'),
            
            # Pattern 4: vtranslate('key', $QUALIFIED_MODULE) -> "key"|t:$QUALIFIED_MODULE
            (r'\{vtranslate\([\'"]([A-Za-z_][A-Za-z0-9_\s]*)[\'"]\s*,\s*(\$QUALIFIED_MODULE)\)\}', r'{"\1"|t:\2}'),
            
            # Pattern 5: vtranslate('key', $MODULE) -> "key"|t:$MODULE
            (r'\{vtranslate\([\'"]([A-Za-z_][A-Za-z0-9_\s]*)[\'"]\s*,\s*(\$MODULE)\)\}', r'{"\1"|t:\2}'),
            
            # Pattern 6: vtranslate('key', $RELATED_MODULE) -> "key"|t:$RELATED_MODULE
            (r'\{vtranslate\([\'"]([A-Za-z_][A-Za-z0-9_\s]*)[\'"]\s*,\s*(\$RELATED_MODULE)\)\}', r'{"\1"|t:\2}'),
            
            # Pattern 7: vtranslate($VARIABLE, $MODULE) -> $VARIABLE|t:$MODULE
            (r'\{vtranslate\((\$[A-Za-z_][A-Za-z0-9_]*),\s*(\$MODULE)\)\}', r'{\1|t:\2}'),
            
            # Pattern 8: vtranslate($OBJECT->method(), $MODULE) -> $OBJECT->method()|t:$MODULE
            (r'\{vtranslate\((\$[A-Za-z_][A-Za-z0-9_]*->[A-Za-z_][A-Za-z0-9_]*\(\)),\s*(\$MODULE)\)\}', r'{\1|t:\2}'),
            
            # Pattern 9: vtranslate($OBJECT->method(), $MODULE_NAME) -> $OBJECT->method()|t:$MODULE_NAME
            (r'\{vtranslate\(([^,]+),\s*(\$MODULE_NAME)\)\}', r'{\1|t:\2}'),
            
            # Pattern 10: vtranslate($OBJECT->method('param'), $MODULE) -> $OBJECT->method('param')|t:$MODULE
            (r'\{vtranslate\((\$[A-Za-z_][A-Za-z0-9_]*->[A-Za-z_][A-Za-z0-9_]*\([^)]*\)),\s*(\$MODULE)\)\}', r'{\1|t:\2}'),
            
            # Pattern 11: vtranslate('key'|modifier, $MODULE) -> 'key'|modifier|t:$MODULE
            (r'\{vtranslate\(([^\s,]+(?:\|[^\s,]+)*),\s*(\$MODULE)\)\}', r'{\1|t:\2}'),
        ]
    
    def process_file(self, file_path: Path) -> int:
        """Process a single file and return number of replacements made"""
        try:
            with open(file_path, 'r', encoding='utf-8') as f:
                content = f.read()
            
            original_content = content
            replacements = 0
            
            # Apply each pattern
            for pattern, replacement in self.patterns:
                new_content = re.sub(pattern, replacement, content)
                if new_content != content:
                    replacements += content.count('vtranslate(') - new_content.count('vtranslate(')
                    content = new_content
            
            # Only write if changes were made
            if content != original_content:
                if not self.dry_run:
                    with open(file_path, 'w', encoding='utf-8') as f:
                        f.write(content)
                
                if self.verbose:
                    print(f"  ✓ Modified {file_path} ({replacements} changes)")
                
                self.modified_files += 1
                self.total_replacements += replacements
                return replacements
            
            return 0
            
        except Exception as e:
            print(f"  ✗ Error processing {file_path}: {e}")
            return 0
    
    def process_directory(self, target_path: str):
        """Process all .tpl files in the target directory or a single file"""
        target = Path(target_path)
        
        if not target.exists():
            print(f"Error: Path '{target_path}' does not exist")
            return
        
        print(f"=== Simple vtranslate to modifier 't' refactoring tool ===")
        print(f"{'Mode: DRY RUN' if self.dry_run else 'Mode: LIVE (changes will be applied)'}")
        print(f"Target: {target}")
        print(f"File pattern: {self.file_pattern}")
        print()
        
        # Check if it's a single file
        if target.is_file():
            if target.suffix == '.tpl':
                print(f"Processing single file: {target.name}")
                self.process_file(target)
            else:
                print(f"Error: File '{target_path}' is not a .tpl file")
            return
        
        # Find all .tpl files
        tpl_files = list(target.rglob(self.file_pattern))
        self.total_files = len(tpl_files)
        
        if self.total_files == 0:
            print("No .tpl files found")
            return
        
        print(f"Scanning directory: {target.name}")
        
        # Process each file
        for file_path in tpl_files:
            # Skip files that don't contain vtranslate
            try:
                with open(file_path, 'r', encoding='utf-8') as f:
                    if 'vtranslate(' not in f.read():
                        continue
            except:
                continue
            
            self.process_file(file_path)
        
        # Summary
        print()
        print("=== Summary ===")
        print(f"Files scanned: {self.total_files}")
        print(f"Files modified: {self.modified_files}")
        print(f"Total replacements made: {self.total_replacements}")
        print()
        
        if self.total_replacements > 0:
            print("Refactoring completed successfully!")
            print("Remember to test your application after these changes.")
        else:
            print("No simple vtranslate patterns found to refactor.")

def main():
    parser = argparse.ArgumentParser(description='Simple vtranslate to modifier t refactoring tool')
    parser.add_argument('target', help='Target file or directory to process')
    parser.add_argument('--dry-run', action='store_true', help='Show what would be changed without making changes')
    parser.add_argument('--verbose', action='store_true', help='Show detailed output')
    parser.add_argument('--pattern', default='*.tpl', help='File pattern to match (default: *.tpl)')
    
    args = parser.parse_args()
    
    refactorer = SimpleVtranslateRefactorer(args.dry_run, args.verbose, args.pattern)
    refactorer.process_directory(args.target)

if __name__ == '__main__':
    main()
