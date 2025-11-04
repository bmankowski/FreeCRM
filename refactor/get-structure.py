#!/usr/bin/env python3
"""
HTML Structure Extractor
Fetches HTML from a URL and outputs a simplified structure with only specific tags.
"""

import sys
import argparse
import requests
from bs4 import BeautifulSoup, Tag
# Disable SSL warnings for self-signed certificates
import urllib3
urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)


def extract_structure(element):
    """
    Recursively extract structure from HTML element.
    Keeps only specified tags with their class and id attributes.
    """
    # Tags we want to keep
    # allowed_tags = {'head', 'body', 'div', 'span', 'ul', 'table', 'li'}
    allowed_tags = {'head', 'body', 'div'}
    
    if not isinstance(element, Tag):
        return None
    
    # Skip tags we don't care about
    if element.name not in allowed_tags:
        # Process children of skipped tags
        result = []
        for child in element.children:
            child_result = extract_structure(child)
            if child_result:
                result.append(child_result)
        return ''.join(result) if result else None
    
    # Build the opening tag with class and id attributes
    attrs = []
    if element.get('class'):
        # class attribute can be a list in BeautifulSoup
        class_value = ' '.join(element.get('class'))
        attrs.append(f'class="{class_value}"')
    if element.get('id'):
        attrs.append(f'id="{element.get("id")}"')
    
    attr_string = ' ' + ' '.join(attrs) if attrs else ''
    opening_tag = f'<{element.name}{attr_string}>'
    closing_tag = f'</{element.name}>'
    
    # Process children
    children_html = []
    for child in element.children:
        child_result = extract_structure(child)
        if child_result:
            children_html.append(child_result)
    
    return opening_tag + ''.join(children_html) + closing_tag


def analyze_tags(html_string):
    """
    Analyze if every opened tag was properly closed.
    Returns a tuple (is_valid, analysis_report)
    """
    stack = []
    i = 0
    tag_pairs = []
    unclosed_tags = []
    
    while i < len(html_string):
        if html_string[i] == '<':
            end = html_string.find('>', i)
            if end == -1:
                break
            
            tag = html_string[i:end+1]
            
            # Extract tag name
            if tag.startswith('</'):
                # Closing tag
                tag_name = tag[2:-1].split()[0]
                if stack:
                    opened = stack.pop()
                    if opened == tag_name:
                        tag_pairs.append((opened, tag_name, 'matched'))
                    else:
                        tag_pairs.append((opened, tag_name, 'mismatched'))
                        unclosed_tags.append(opened)
                else:
                    tag_pairs.append((None, tag_name, 'unopened'))
            elif not tag.endswith('/>'):
                # Opening tag
                tag_name = tag[1:-1].split()[0]
                stack.append(tag_name)
            
            i = end + 1
        else:
            i += 1
    
    # Any remaining tags in stack are unclosed
    unclosed_tags.extend(stack)
    
    # Generate report
    report = []
    report.append("=" * 60)
    report.append("HTML STRUCTURE VALIDATION ANALYSIS")
    report.append("=" * 60)
    
    if not unclosed_tags and all(tp[2] == 'matched' for tp in tag_pairs):
        report.append("\n✓ All tags are properly closed and matched!")
        report.append(f"\nTotal tag pairs analyzed: {len(tag_pairs)}")
        is_valid = True
    else:
        is_valid = False
        report.append("\n✗ Issues found in HTML structure:")
        
        if unclosed_tags:
            report.append(f"\n  Unclosed tags ({len(unclosed_tags)}):")
            for tag in unclosed_tags:
                report.append(f"    - <{tag}>")
        
        mismatched = [(o, c) for o, c, status in tag_pairs if status == 'mismatched']
        if mismatched:
            report.append(f"\n  Mismatched tags ({len(mismatched)}):")
            for opened, closed in mismatched:
                report.append(f"    - Opened: <{opened}>, Closed: </{closed}>")
        
        unopened = [c for o, c, status in tag_pairs if status == 'unopened']
        if unopened:
            report.append(f"\n  Closing tags without opening ({len(unopened)}):")
            for tag in unopened:
                report.append(f"    - </{tag}>")
        
        report.append(f"\nTotal tag pairs analyzed: {len(tag_pairs)}")
    
    report.append("=" * 60)
    
    return is_valid, '\n'.join(report)


def pretty_print(html_string, indent=0, indent_size=2):
    """
    Pretty print HTML string with proper indentation.
    """
    result = []
    i = 0
    current_indent = indent
    
    while i < len(html_string):
        if html_string[i] == '<':
            # Find the end of the tag
            end = html_string.find('>', i)
            if end == -1:
                break
            
            tag = html_string[i:end+1]
            
            # Check if it's a closing tag
            if tag.startswith('</'):
                current_indent -= indent_size
                result.append(' ' * current_indent + tag + '\n')
            # Check if it's a self-closing tag or empty tag pair
            elif tag.endswith('/>') or (i + len(tag) < len(html_string) and 
                                        html_string[i + len(tag):i + len(tag) + 2] == '</'):
                result.append(' ' * current_indent + tag)
                # Check if immediately followed by closing tag
                if i + len(tag) < len(html_string) and html_string[i + len(tag):i + len(tag) + 2] == '</':
                    close_end = html_string.find('>', i + len(tag))
                    close_tag = html_string[i + len(tag):close_end + 1]
                    result.append(close_tag + '\n')
                    i = close_end + 1
                    continue
                else:
                    result.append('\n')
            else:
                result.append(' ' * current_indent + tag + '\n')
                current_indent += indent_size
            
            i = end + 1
        else:
            i += 1
    
    return ''.join(result)


def login_to_freecrm(base_url, username='admin', password='admin'):
    """
    Login to FreeCRM and return a session with cookies.
    """
    from urllib.parse import urlparse
    session = requests.Session()
    
    # Extract base URL
    parsed = urlparse(base_url)
    base = f"{parsed.scheme}://{parsed.netloc}"
    login_url = f"{base}/index.php?module=Users&action=Login"
    
    # First, get the login page to establish session and cookies
    try:
        init_response = session.get(f"{base}/index.php", timeout=30, verify=False)
        print(f"Initial GET cookies: {session.cookies.get_dict()}", file=sys.stderr)
    except requests.RequestException as e:
        print(f"Initial GET failed: {e}", file=sys.stderr)
        pass  # Continue even if this fails
    
    # Prepare login data - form POST data
    login_data = {
        'username': username,
        'password': password
    }
    
    try:
        # Perform login
        response = session.post(login_url, data=login_data, timeout=30, verify=False, allow_redirects=True)
        
        # Debug: print the final URL after redirects
        print(f"Login POST completed. Final URL: {response.url}", file=sys.stderr)
        print(f"POST response cookies: {session.cookies.get_dict()}", file=sys.stderr)
        
        # Check for error parameter in URL (FreeCRM redirects to error=2 on failed login)
        if 'error=' in response.url:
            print(f"ERROR: Login failed - server returned error code", file=sys.stderr)
            print(f"Possible reasons: wrong credentials, IP blocked by brute force protection", file=sys.stderr)
        elif 'module=Users' in response.url and 'Login' in response.url:
            # Still on login page/action
            print(f"Warning: Login failed - still on login page", file=sys.stderr)
            print(f"Please verify credentials are correct for this server", file=sys.stderr)
        else:
            print(f"Login successful for user: {username}", file=sys.stderr)
        
        return session
    except requests.RequestException as e:
        print(f"Warning: Login request failed: {e}", file=sys.stderr)
        return session


def main():
    # Parse command-line arguments
    parser = argparse.ArgumentParser(
        description='Extract HTML structure from a URL or file',
        epilog='''
Examples:
  # Fetch from URL with authentication:
  %(prog)s --url "http://localhost/index.php?module=Accounts&view=List" --username admin --password admin
  
  # Read from local file:
  %(prog)s -f /path/to/page.html
        ''',
        formatter_class=argparse.RawDescriptionHelpFormatter
    )
    parser.add_argument('--url', dest='url',
                        help='URL to fetch and analyze')
    parser.add_argument('-u', '--username', default='admin', 
                        help='Username for authentication (default: admin)')
    parser.add_argument('-p', '--password', default='admin',
                        help='Password for authentication (default: admin)')
    parser.add_argument('-f', '--file', dest='filename',
                        help='Read HTML from local file instead of fetching URL')
    
    # If no arguments provided, print help and exit
    if len(sys.argv) == 1:
        parser.print_help()
        sys.exit(0)
    
    args = parser.parse_args()
    
    # Check if we have either URL or file
    if not args.url and not args.filename:
        parser.error('Either URL or -f/--file must be specified')
    
    if args.filename and args.url:
        parser.error('Cannot specify both URL and -f/--file at the same time')
    
    # Read HTML content
    if args.filename:
        # Read from file
        try:
            with open(args.filename, 'r', encoding='utf-8') as f:
                html_content = f.read()
            print(f"Reading HTML from file: {args.filename}", file=sys.stderr)
        except FileNotFoundError:
            print(f"Error: File not found: {args.filename}", file=sys.stderr)
            sys.exit(1)
        except Exception as e:
            print(f"Error reading file: {e}", file=sys.stderr)
            sys.exit(1)
    else:
        # Fetch from URL
        # Login to FreeCRM first
        session = login_to_freecrm(args.url, args.username, args.password)
        
        try:
            # Fetch the HTML content using authenticated session
            response = session.get(args.url, timeout=30, verify=False)
            response.raise_for_status()
            html_content = response.text
        except requests.RequestException as e:
            print(f"Error fetching URL: {e}", file=sys.stderr)
            sys.exit(1)
    
    # Parse HTML
    soup = BeautifulSoup(html_content, 'html.parser')
    
    # Extract structure starting from the root
    structure = extract_structure(soup)
    
    if structure:
        # Pretty print the output
        pretty_output = pretty_print(structure)
        print(pretty_output, end='')
        
        # Stage 2: Analyze tag matching
        print("\n")
        is_valid, analysis_report = analyze_tags(structure)
        print(analysis_report)
        
        # Exit with appropriate code
        sys.exit(0 if is_valid else 2)
    else:
        print("No matching structure found", file=sys.stderr)
        sys.exit(1)


if __name__ == '__main__':
    main()

