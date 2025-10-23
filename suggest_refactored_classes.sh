#!/bin/bash

suggest_new_class() {
    local old_class="$1"
    IFS='_' read -ra parts <<< "$old_class"
    local module="${parts[0]}"
    local len=${#parts[@]}
    local last="${parts[$len-1]}"
    
    if [[ "$last" == "Model" ]]; then
        local subdir="${parts[$len-2]}"
        printf '%s\t%s\n' "\\App\\Modules\\${module}\\Models\\${subdir}" "src/Modules/${module}/Models/${subdir}.php"
    elif [[ "$last" == "View" ]]; then
        local name=""
        for ((i=1; i<len-1; i++)); do name+="${parts[i]}"; done
        printf '%s\t%s\n' "\\App\\Modules\\${module}\\Views\\${name}" "src/Modules/${module}/Views/${name}.php"
    elif [[ "$last" == "Action" ]]; then
        local name=""
        for ((i=1; i<len-1; i++)); do name+="${parts[i]}"; done
        printf '%s\t%s\n' "\\App\\Modules\\${module}\\Actions\\${name}" "src/Modules/${module}/Actions/${name}.php"
    elif [[ "$last" == "Helper" ]]; then
        local name=""
        for ((i=1; i<len-1; i++)); do name+="${parts[i]}"; done
        printf '%s\t%s\n' "\\App\\Modules\\${module}\\Helpers\\${name}" "src/Modules/${module}/Helpers/${name}.php"
    elif [[ "$last" == "UIType" ]]; then
        local name=""
        for ((i=1; i<len-1; i++)); do name+="${parts[i]}"; done
        printf '%s\t%s\n' "\\App\\Fields\\UITypes\\${name}" "src/Fields/UITypes/${name}.php"
    else
        local name=""
        for ((i=1; i<len; i++)); do name+="${parts[i]}"; done
        printf '%s\t%s\n' "\\App\\Modules\\${module}\\${name}" "src/Modules/${module}/${name}.php"
    fi
}

find_class_definition() {
    local class_name="$1"
    local base_dir="/home/bmankowski/projects/FreeCRM"
    
    # Search for class definition in all PHP files
    # Match: "class ClassName" (with word boundaries)
    local search_results=$(grep -r --include="*.php" -l "^\s*class\s\+${class_name}\s" "$base_dir" 2>/dev/null)
    
    # Count number of matches
    local count=$(echo "$search_results" | grep -c "^" 2>/dev/null || echo 0)
    
    if [[ $count -eq 1 ]]; then
        # Extract relative path
        local full_path="$search_results"
        local rel_path="${full_path#$base_dir/}"
        echo "$rel_path"
    fi
}

cut -f2 /home/bmankowski/projects/FreeCRM/aliases_waiting_to_be_changed.txt | grep -E "^[A-Z][a-zA-Z_]+_[a-zA-Z_]+$" | while read old_class; do
    result=$(suggest_new_class "$old_class")
    new_class=$(echo "$result" | cut -f1)
    file_path=$(echo "$result" | cut -f2)
    
    if [ -f "/home/bmankowski/projects/FreeCRM/$file_path" ]; then
        status="FOUND"
        printf '%s\t%s\t%s\t%s\n' "$old_class" "$new_class" "$file_path" "$status"
    else
        # File is missing, try to find the class definition
        alternative=$(find_class_definition "$old_class")
        
        if [ -n "$alternative" ]; then
            status="MISSING (Alternative: $alternative)"
        else
            status="MISSING"
        fi
        
        printf '%s\t%s\t%s\t%s\n' "$old_class" "$new_class" "$file_path" "$status"
    fi
done
