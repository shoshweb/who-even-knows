import xml.etree.ElementTree as ET
import zipfile
import re
import json
import argparse
import tempfile
import os
import shutil

def combine_split_tags(root):
    """
    Finds and merges tags that are split across multiple <w:t> elements.
    """
    ns = {'w': 'http://schemas.openxmlformats.org/wordprocessingml/2006/main'}
    for p in root.findall('.//w:p', ns):
        runs = p.findall('.//w:r', ns)
        i = 0
        while i < len(runs):
            run = runs[i]
            t = run.find('w:t', ns)
            if t is not None and t.text and '{$' in t.text and '}' not in t.text:
                full_tag_text = t.text
                runs_to_merge = [run]

                for j in range(i + 1, len(runs)):
                    next_run = runs[j]
                    next_t = next_run.find('w:t', ns)
                    if next_t is not None and next_t.text:
                        full_tag_text += next_t.text
                        runs_to_merge.append(next_run)
                        if '}' in next_t.text:
                            first_run_t = runs_to_merge[0].find('w:t', ns)
                            first_run_t.text = full_tag_text

                            for k in range(1, len(runs_to_merge)):
                                t_to_clear = runs_to_merge[k].find('w:t', ns)
                                if t_to_clear is not None:
                                    t_to_clear.text = ""

                            i = j
                            break
            i += 1

def replace_tags(root, data):
    """
    Replaces merge tags in the given XML root with the corresponding data.
    """
    ns = {'w': 'http://schemas.openxmlformats.org/wordprocessingml/2006/main'}
    tag_regex = re.compile(r'(\{\$[^|}]+\|?([^}]+)?\})')
    for t in root.findall('.//w:t', ns):
        if t.text and '{$' in t.text:
            new_text = t.text
            for match in tag_regex.finditer(t.text):
                full_tag = match.group(1)

                parts = full_tag.strip('{$}').split('|')
                tag_name = parts[0]
                modifiers = parts[1:]

                value = str(data.get(tag_name, full_tag))

                for mod in modifiers:
                    if mod == 'upper':
                        value = value.upper()

                new_text = new_text.replace(full_tag, value)
            t.text = new_text

def extract_gravity_forms_data(data_path):
    with open(data_path, 'r') as f:
        gravity_forms_data = json.load(f)

    mapped_data = {}
    # This is a simplified data extraction. For the real implementation,
    # we would need to handle the full Gravity Forms structure.
    # For now, we assume a simple key-value pair for testing.
    if isinstance(gravity_forms_data, list):
        for form in gravity_forms_data:
            for field in form.get('fields', []):
                admin_label = field.get('adminLabel')
                if admin_label:
                    mapped_data[admin_label.strip('{}')] = field.get('label', '')
    else: # It's our simple test_data.json
        mapped_data = gravity_forms_data
    return mapped_data

def process_docx(template_path, data_path, output_path):
    """
    Processes a .docx template, replacing merge tags with data from a JSON file.
    """
    with tempfile.TemporaryDirectory() as temp_dir:
        with zipfile.ZipFile(template_path, 'r') as zip_ref:
            zip_ref.extractall(temp_dir)

        document_xml_path = os.path.join(temp_dir, 'word/document.xml')
        tree = ET.parse(document_xml_path)
        root = tree.getroot()

        combine_split_tags(root)

        data = extract_gravity_forms_data(data_path)

        replace_tags(root, data)

        tree.write(document_xml_path)

        with zipfile.ZipFile(output_path, 'w', zipfile.ZIP_DEFLATED) as zip_ref:
            for folder_name, subfolders, filenames in os.walk(temp_dir):
                for filename in filenames:
                    file_path = os.path.join(folder_name, filename)
                    arcname = os.path.relpath(file_path, temp_dir)
                    zip_ref.write(file_path, arcname)

if __name__ == '__main__':
    parser = argparse.ArgumentParser(description='Process a .docx template.')
    parser.add_argument('template_path', help='The path to the .docx template.')
    parser.add_argument('data_path', help='The path to the JSON data file.')
    parser.add_argument('output_path', help='The path to the output .docx file.')
    args = parser.parse_args()

    process_docx(args.template_path, args.data_path, args.output_path)
