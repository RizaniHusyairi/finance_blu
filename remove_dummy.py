import sys

with open('c:/laragon/www/template_maxton/finance_aptp/resources/views/layouts/sidebar-app.blade.php', 'r', encoding='utf-8') as f:
    lines = f.readlines()

new_lines = []
skip = False
for i, line in enumerate(lines):
    if '{{-- Dummy forelse --}}' in line:
        skip = True
    if not skip:
        new_lines.append(line)
    if skip and '@endforelse' in line and i > 170 and i < 190:
        skip = False

with open('c:/laragon/www/template_maxton/finance_aptp/resources/views/layouts/sidebar-app.blade.php', 'w', encoding='utf-8') as f:
    f.writelines(new_lines)
print("Done")
