#!/usr/bin/env python3
"""
Simple \FreeCRM\Runtime\Vtiger_Language_Handler::translate to modifier 't' refactoring tool
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
            # Pattern 1: \FreeCRM\Runtime\Vtiger_Language_Handler::translate('key') -> "key"|t
            (r'\{vtranslate\([\'"]([A-Za-z_][A-Za-z0-9_]*)[\'"]\)\}', r'{"\1"|t}'),
            
            # Pattern 2: \FreeCRM\Runtime\Vtiger_Language_Handler::translate('key', 'module') -> "key"|t:'module'
            (r'\{vtranslate\([\'"]([A-Za-z_][A-Za-z0-9_]*)[\'"],\s*[\'"]([A-Za-z_][A-Za-z0-9_]*)[\'"]\)\}', r'{"\1"|t:"\2"}'),
            
            # Pattern 3: \FreeCRM\Runtime\Vtiger_Language_Handler::translate('key', $MODULE_NAME) -> "key"|t:$MODULE_NAME
            (r'\{vtranslate\([\'"]([A-Za-z_][A-Za-z0-9_\s]*)[\'"]\s*,\s*(\$MODULE_NAME)\)\}', r'{"\1"|t:\2}'),
            
            # Pattern 4: \FreeCRM\Runtime\Vtiger_Language_Handler::translate('key', $QUALIFIED_MODULE) -> "key"|t:$QUALIFIED_MODULE
            (r'\{vtranslate\([\'"]([A-Za-z_][A-Za-z0-9_\s]*)[\'"]\s*,\s*(\$QUALIFIED_MODULE)\)\}', r'{"\1"|t:\2}'),
            
            # Pattern 5: \FreeCRM\Runtime\Vtiger_Language_Handler::translate('key', $MODULE) -> "key"|t:$MODULE
            (r'\{vtranslate\([\'"]([A-Za-z_][A-Za-z0-9_\s]*)[\'"]\s*,\s*(\$MODULE)\)\}', r'{"\1"|t:\2}'),
            
            # Pattern 6: \FreeCRM\Runtime\Vtiger_Language_Handler::translate('key', $RELATED_MODULE) -> "key"|t:$RELATED_MODULE
            (r'\{vtranslate\([\'"]([A-Za-z_][A-Za-z0-9_\s]*)[\'"]\s*,\s*(\$RELATED_MODULE)\)\}', r'{"\1"|t:\2}'),
            
            # Pattern 7: \FreeCRM\Runtime\Vtiger_Language_Handler::translate($VARIABLE, $MODULE) -> $VARIABLE|t:$MODULE
            (r'\{vtranslate\((\$[A-Za-z_][A-Za-z0-9_]*),\s*(\$MODULE)\)\}', r'{\1|t:\2}'),
            
            # Pattern 8: \FreeCRM\Runtime\Vtiger_Language_Handler::translate($OBJECT->method(), $MODULE) -> $OBJECT->method()|t:$MODULE
            (r'\{vtranslate\((\$[A-Za-z_][A-Za-z0-9_]*->[A-Za-z_][A-Za-z0-9_]*\(\)),\s*(\$MODULE)\)\}', r'{\1|t:\2}'),
            
            # Pattern 9: \FreeCRM\Runtime\Vtiger_Language_Handler::translate($OBJECT->method(), $MODULE_NAME) -> $OBJECT->method()|t:$MODULE_NAME
            (r'\{vtranslate\(([^,]+),\s*(\$MODULE_NAME)\)\}', r'{\1|t:\2}'),
            
            # Pattern 10: \FreeCRM\Runtime\Vtiger_Language_Handler::translate($OBJECT->method('param'), $MODULE) -> $OBJECT->method('param')|t:$MODULE
            (r'\{vtranslate\((\$[A-Za-z_][A-Za-z0-9_]*->[A-Za-z_][A-Za-z0-9_]*\([^)]*\)),\s*(\$MODULE)\)\}', r'{\1|t:\2}'),
            
            # Pattern 11: \FreeCRM\Runtime\Vtiger_Language_Handler::translate('key'|modifier, $MODULE) -> 'key'|modifier|t:$MODULE
            (r'\{vtranslate\(([^\s,]+(?:\|[^\s,]+)*),\s*(\$MODULE)\)\}', r'{\1|t:\2}'),
            
            # Pattern 12: \FreeCRM\Runtime\Vtiger_Language_Handler::translate('key', 'string_literal') -> "key"|t:"string_literal"
            (r'\{vtranslate\([\'"]([^\'"]*)[\'"],\s*[\'"]([^\'"]*)[\'"]\)\}', r'{"\1"|t:"\2"}'),
            
            # Pattern 13: \FreeCRM\Runtime\Vtiger_Language_Handler::translate('key', $MODULENAME) -> "key"|t:$MODULENAME
            (r'\{vtranslate\([\'"]([^\'"]*)[\'"]\s*,\s*(\$MODULENAME)\)\}', r'{"\1"|t:\2}'),
            
            # Pattern 14: \FreeCRM\Runtime\Vtiger_Language_Handler::translate('key', $RELATED_MODULE_NAME) -> "key"|t:$RELATED_MODULE_NAME
            (r'\{vtranslate\([\'"]([^\'"]*)[\'"]\s*,\s*(\$RELATED_MODULE_NAME)\)\}', r'{"\1"|t:\2}'),
            
            # Pattern 15: \FreeCRM\Runtime\Vtiger_Language_Handler::translate($VARIABLE) -> $VARIABLE|t (only single variables, not complex expressions)
            (r'\{vtranslate\((\$[A-Za-z_][A-Za-z0-9_]*)\)\}', r'{\1|t}'),
            
            # Pattern 16: \FreeCRM\Runtime\Vtiger_Language_Handler::translate($VARIABLE, 'string_literal') -> $VARIABLE|t:"string_literal" (no braces in first param)
            (r'\{vtranslate\(([^,){}]+)\s*,\s*[\'"]([^\'"]*)[\'"]\)\}', r'{\1|t:"\2"}'),
            
            # Pattern 17: \FreeCRM\Runtime\Vtiger_Language_Handler::translate('key'|modifier, $VARIABLE) -> 'key'|modifier|t:$VARIABLE (special case for |cat, no braces in first param)
            (r'\{vtranslate\(([^\s,{}]+(?:\|[^\s,{}]+)*),\s*([^)]+)\)\}', r'{\1|t:\2}'),
            
            # Pattern 18: \FreeCRM\Runtime\Vtiger_Language_Handler::translate(complex_expression_without_comma) -> complex_expression|t (single parameter only, no braces inside)
            (r'\{vtranslate\(([^,){}]*(?:\([^)]*\)[^,){}]*)*)\)\}', r'{\1|t}'),
        ]
    
    def process_file(self, file_path: Path) -> int:
        """Process a single file and return number of replacements made"""
        try:
            with open(file_path, 'r', encoding='utf-8') as f:
                content = f.read()
            
            original_content = content
            replacements = 0
            changes_shown = []
            
            # Apply each pattern
            for i, (pattern, replacement) in enumerate(self.patterns, 1):
                matches = re.finditer(pattern, content)
                for match in matches:
                    old_text = match.group(0)
                    new_text = re.sub(pattern, replacement, old_text)
                    if old_text != new_text:
                        changes_shown.append({
                            'pattern_num': i,
                            'old': old_text,
                            'new': new_text,
                            'line': content[:match.start()].count('\n') + 1
                        })
                
                new_content = re.sub(pattern, replacement, content)
                if new_content != content:
                    replacements += content.count('vtranslate(') - new_content.count('vtranslate(')
                    content = new_content
            
            # Only write if changes were made
            if content != original_content:
                if self.dry_run and changes_shown:
                    print(f"  📝 {file_path}:")
                    for change in changes_shown:
                        print(f"    Pattern {change['pattern_num']} (line {change['line']}):")
                        print(f"      OLD: {change['old']}")
                        print(f"      NEW: {change['new']}")
                        print()
                elif not self.dry_run:
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
        
        print(f"=== Simple \FreeCRM\Runtime\Vtiger_Language_Handler::translate to modifier 't' refactoring tool ===")
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
            # Skip files that don't contain \FreeCRM\Runtime\Vtiger_Language_Handler::translate
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
            print("No simple \FreeCRM\Runtime\Vtiger_Language_Handler::translate patterns found to refactor.")

def main():
    parser = argparse.ArgumentParser(description='Simple \FreeCRM\Runtime\Vtiger_Language_Handler::translate to modifier t refactoring tool')
    parser.add_argument('target', help='Target file or directory to process')
    parser.add_argument('--dry-run', action='store_true', help='Show what would be changed without making changes')
    parser.add_argument('--verbose', action='store_true', help='Show detailed output')
    parser.add_argument('--pattern', default='*.tpl', help='File pattern to match (default: *.tpl)')
    
    args = parser.parse_args()
    
    refactorer = SimpleVtranslateRefactorer(args.dry_run, args.verbose, args.pattern)
    refactorer.process_directory(args.target)

if __name__ == '__main__':
    main()
