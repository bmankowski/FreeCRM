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

grep -E "^[A-Z][a-zA-Z_]+_[a-zA-Z_]+$" /home/bmankowski/projects/FreeCRM/missing_aliases.txt | while read old_class; do
    result=$(suggest_new_class "$old_class")
    new_class=$(echo "$result" | cut -f1)
    file_path=$(echo "$result" | cut -f2)
    
    [ -f "/home/bmankowski/projects/FreeCRM/$file_path" ] && status="FOUND" || status="MISSING"
    
    printf '%s\t%s\t%s\t%s\n' "$old_class" "$new_class" "$file_path" "$status"
done
