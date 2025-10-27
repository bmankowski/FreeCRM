#!/bin/bash

# Script to convert classes-in-codebase.txt to a tree view format
# Input: documentation/classes-in-codebase.txt
# Output: documentation/classes-in-codebase-treeview.txt

INPUT_FILE="/home/bmankowski/projects/FreeCRM/documentation/classes-in-codebase.txt"
OUTPUT_FILE="/home/bmankowski/projects/FreeCRM/documentation/classes-in-codebase-treeview.txt"

# Check if input file exists
if [ ! -f "$INPUT_FILE" ]; then
    echo "Error: Input file $INPUT_FILE not found"
    exit 1
fi

# Use Python for better tree generation
python3 - <<'PYTHON_SCRIPT'
import sys
from collections import defaultdict
from typing import Dict, List, Tuple

def read_classes(filename: str) -> List[Tuple[str, str]]:
    """Read classes from file and return list of (class_path, parent_class)"""
    classes = []
    try:
        with open(filename, 'r') as f:
            for line in f:
                line = line.strip()
                if not line:
                    continue
                
                parts = line.split(None, 1)
                class_name = parts[0]
                parent_class = parts[1] if len(parts) > 1 else ""
                
                # Remove leading backslash
                class_name = class_name.lstrip('\\')
                
                classes.append((class_name, parent_class))
    except FileNotFoundError:
        print(f"Error: File {filename} not found", file=sys.stderr)
        sys.exit(1)
    
    return classes

def build_tree(classes: List[Tuple[str, str]]) -> Dict:
    """Build a tree structure from class list"""
    tree = {}
    
    for class_path, parent_class in classes:
        parts = class_path.split('\\')
        current = tree
        
        # Navigate/create the tree structure
        for i, part in enumerate(parts):
            if part not in current:
                current[part] = {
                    '_children': {},
                    '_parent': parent_class if i == len(parts) - 1 else "",
                    '_full_path': '\\'.join(parts[:i+1]),
                    '_is_leaf': i == len(parts) - 1
                }
            elif i == len(parts) - 1:
                # Update parent info for leaf nodes
                current[part]['_parent'] = parent_class
                current[part]['_is_leaf'] = True
            
            current = current[part]['_children']
    
    return tree

def print_tree(tree: Dict, prefix: str = "", output_lines: List[str] = None, is_root: bool = True):
    """Print tree structure with proper formatting"""
    if output_lines is None:
        output_lines = []
    
    items = sorted(tree.items())
    
    for i, (name, data) in enumerate(items):
        is_last_item = (i == len(items) - 1)
        
        # Determine the connector
        if is_root:
            connector = ""
        else:
            connector = "└── " if is_last_item else "├── "
        
        # Determine if this is a class (leaf) or namespace (branch)
        has_children = bool(data['_children'])
        is_leaf = data.get('_is_leaf', False)
        parent_class = data.get('_parent', '')
        
        # Format the output
        if is_leaf and parent_class:
            marker = "●"  # Filled circle for classes with inheritance
            inheritance = f" → extends {parent_class}"
        elif is_leaf:
            marker = "○"  # Empty circle for classes without inheritance
            inheritance = ""
        else:
            marker = "📁" if has_children else "○"
            inheritance = ""
        
        # Add the line
        line = f"{prefix}{connector}{marker} {name}{inheritance}"
        output_lines.append(line)
        
        # Recurse for children
        if has_children:
            # Determine the new prefix for children
            if is_root:
                new_prefix = ""
            else:
                extension = "    " if is_last_item else "│   "
                new_prefix = prefix + extension
            
            print_tree(data['_children'], new_prefix, output_lines, False)
    
    return output_lines

def main():
    input_file = "/home/bmankowski/projects/FreeCRM/documentation/classes-in-codebase.txt"
    output_file = "/home/bmankowski/projects/FreeCRM/documentation/classes-in-codebase-treeview.txt"
    
    # Read and process classes
    classes = read_classes(input_file)
    
    # Build tree structure
    tree = build_tree(classes)
    
    # Generate output
    output_lines = []
    output_lines.append("╔" + "═" * 78 + "╗")
    output_lines.append("║" + " " * 20 + "FreeCRM Class Hierarchy Tree" + " " * 30 + "║")
    output_lines.append("╚" + "═" * 78 + "╝")
    output_lines.append("")
    output_lines.append("Legend:")
    output_lines.append("  📁 - Namespace (folder containing classes/namespaces)")
    output_lines.append("  ● - Class that extends another class")
    output_lines.append("  ○ - Interface or standalone class")
    output_lines.append("")
    output_lines.append("─" * 80)
    output_lines.append("")
    
    # Print the tree
    tree_lines = print_tree(tree, "", [], True)
    output_lines.extend(tree_lines)
    
    output_lines.append("")
    output_lines.append("─" * 80)
    output_lines.append(f"Total classes: {len(classes)}")
    output_lines.append("")
    
    # Write to file
    with open(output_file, 'w', encoding='utf-8') as f:
        f.write('\n'.join(output_lines))
    
    print(f"Tree view generated successfully: {output_file}")
    print(f"Total classes processed: {len(classes)}")

if __name__ == "__main__":
    main()
PYTHON_SCRIPT

