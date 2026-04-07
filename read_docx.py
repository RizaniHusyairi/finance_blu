import zipfile
import xml.etree.ElementTree as ET
import sys
import re

def extract_text(docx_path):
    try:
        with zipfile.ZipFile(docx_path) as docx:
            xml_content = docx.read('word/document.xml')
            
        tree = ET.fromstring(xml_content)
        
        # Define the namespace for WordprocessingML
        ns = {'w': 'http://schemas.openxmlformats.org/wordprocessingml/2006/main'}
        
        paragraphs = []
        for p in tree.findall('.//w:p', ns):
            texts = p.findall('.//w:t', ns)
            if texts:
                paragraphs.append(''.join([t.text for t in texts if t.text]))
                
        return '\n'.join(paragraphs)
    except Exception as e:
        return str(e)

if __name__ == '__main__':
    text = extract_text(sys.argv[1])
    # Print the first 5000 characters to get an idea of the structure
    print(text[:5000])
