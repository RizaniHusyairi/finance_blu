"""
Generate database/data/layanan-jasa-seed.json dari jasa_apt.xlsx.
- kdlevel → level
- nmitem → nama (whitespace cleaned)
- kdmak → kode_mak
- kdakunplus → kode_akun
- satuan → satuan
- tarif → tarif (integer; jika formula/text → 0)
- kode → slug mnemonic hierarkis
- parent_kode → ditelusuri via stack berdasarkan level
- is_leaf → auto-computed
- mendukung_konsesi → true untuk subtree "Konsesi"
- wajib_terpisah + jth_tempo=7 → untuk subtree PJP2U / Pelayanan Penumpang
"""

import json
import re
import openpyxl

WB_PATH = 'jasa_apt.xlsx'
OUT_PATH = 'database/data/layanan-jasa-seed.json'

L1_MAP = {
    'A': 'KEBANDARUDARAAN-DOM',
    'B': 'JASA-TERKAIT-DOM',
    'C': 'IZIN-DKT',
    'D': 'KEBANDARUDARAAN-INT',
}

STOP_WORDS = {
    'DI', 'DAN', 'ATAU', 'UNTUK', 'DARI', 'PADA', 'YANG', 'KE', 'PER',
    'OLEH', 'JASA', 'LAYANAN', 'BANDAR', 'UDARA', 'PESAWAT', 'TARIF',
    'PEMAKAIAN', 'PEMASANGAN', 'PENGGUNAAN', 'PENYEDIAAN', 'TEMPAT',
    'SERTA', 'DALAM', 'LUAR', 'JAM', 'OPERASI', 'IZIN', 'KEAMANAN',
    'TERBATAS', 'KEBANDARUDARAAN', 'BERUPA', 'KEPADA', 'ANTAR',
}


def clean_text(s):
    if s is None:
        return ''
    return str(s).replace('\xa0', ' ').strip()


def strip_prefix(name):
    s = clean_text(name)
    s = re.sub(r'^\s*(?:[A-Z]\.|[a-z]\.|\d+\)|\(\d+\)|[a-z]\))\s+', '', s)
    return s.strip()


def slugify(text):
    s = strip_prefix(text)
    s = re.sub(r"['/\(\)\.,]", ' ', s)
    s = re.sub(r'[^\w\s-]', '', s)
    s = re.sub(r'[\s_-]+', '-', s).strip('-').upper()
    return s


def short_slug(text, max_tokens=3, max_len=24):
    full = slugify(text)
    if not full:
        return ''
    tokens = [t for t in full.split('-') if t]
    meaningful = [t for t in tokens if t not in STOP_WORDS]
    if not meaningful:
        meaningful = tokens
    out = '-'.join(meaningful[:max_tokens])
    if len(out) > max_len:
        out = out[:max_len].rstrip('-')
    return out or 'X'


def get_l1_letter(name):
    m = re.match(r'^\s*([A-Z])\.\s', clean_text(name))
    return m.group(1) if m else None


def normalize_nama(name):
    """Keep the original prefix (a./b./1)/(1)) tetap, hanya rapikan whitespace."""
    s = clean_text(name)
    s = re.sub(r'[\xa0\s]+', ' ', s).strip()
    return s


def parse_tarif(raw):
    if raw is None:
        return 0
    if isinstance(raw, (int, float)):
        return int(round(raw))
    return 0


def main():
    wb = openpyxl.load_workbook(WB_PATH, data_only=True)
    ws = wb.active

    raw_items = []
    for row in ws.iter_rows(min_row=2, values_only=True):
        kdlevel = row[0]
        nmitem = row[1]
        if kdlevel is None or nmitem is None:
            continue
        raw_items.append({
            'level': int(kdlevel),
            'nama_raw': nmitem,
            'kode_mak': row[2],
            'kode_akun': row[3],
            'satuan': clean_text(row[4]) or None,
            'tarif': parse_tarif(row[7]),
        })

    # Build hierarchy with slug mnemonic
    stack = []  # list of dicts: { level, kode, siblings: dict[slug -> count] }
    result = []

    for it in raw_items:
        lvl = it['level']

        # Pop parents until top.level < lvl
        while stack and stack[-1]['level'] >= lvl:
            stack.pop()

        parent_entry = stack[-1] if stack else None
        parent_kode = parent_entry['kode'] if parent_entry else None

        # Build slug
        if lvl == 1:
            letter = get_l1_letter(it['nama_raw'])
            base_slug = L1_MAP.get(letter) or short_slug(it['nama_raw'], 3)
        else:
            base_slug = short_slug(it['nama_raw'], 3)

        # Disambiguate within parent
        siblings = parent_entry['siblings'] if parent_entry else None
        if siblings is None:
            # Track root-level uniqueness
            siblings = stack[0]['siblings_root'] if (stack and 'siblings_root' in stack[0]) else None
        # Use a global root sibling tracker
        if lvl == 1:
            root_sib = main.root_sib
            count = root_sib.get(base_slug, 0) + 1
            root_sib[base_slug] = count
            slug = base_slug if count == 1 else f'{base_slug}-{count}'
            kode = slug
        else:
            sib = parent_entry['siblings']
            count = sib.get(base_slug, 0) + 1
            sib[base_slug] = count
            slug = base_slug if count == 1 else f'{base_slug}-{count}'
            kode = f'{parent_kode}-{slug}'

        record = {
            'kode': kode,
            'parent_kode': parent_kode,
            'level': lvl,
            'nama': normalize_nama(it['nama_raw']),
            'tarif': it['tarif'],
            'satuan': it['satuan'],
            'is_leaf': False,
            'is_active': True,
            'tipe': 'PNBP',
            'kode_mak': str(it['kode_mak']) if it['kode_mak'] is not None else None,
            'kode_akun': str(it['kode_akun']) if it['kode_akun'] is not None else None,
            'mendukung_konsesi': False,
            'jth_tempo': 30,
            'wajib_terpisah': False,
        }
        result.append(record)

        stack.append({'level': lvl, 'kode': kode, 'siblings': {}})

    # Compute is_leaf
    for i, rec in enumerate(result):
        is_leaf = True
        # Search forward for any item that has this as ancestor
        for j in range(i + 1, len(result)):
            if result[j]['level'] > rec['level']:
                is_leaf = False
                break
            else:
                break
        rec['is_leaf'] = is_leaf

    # Mark mendukung_konsesi for "Konsesi" subtree
    konsesi_kode = None
    for rec in result:
        if rec['level'] == 2 and rec['nama'].strip().lower() == 'konsesi':
            konsesi_kode = rec['kode']
            rec['mendukung_konsesi'] = True
            break
    if konsesi_kode:
        prefix = konsesi_kode + '-'
        for rec in result:
            if rec['kode'].startswith(prefix):
                rec['mendukung_konsesi'] = True

    # Mark wajib_terpisah + jth_tempo=7 for PJP2U
    for rec in result:
        if 'pelayanan jasa penumpang' in rec['nama'].lower():
            rec['wajib_terpisah'] = True
            rec['jth_tempo'] = 7

    with open(OUT_PATH, 'w', encoding='utf-8') as f:
        json.dump(result, f, indent=4, ensure_ascii=False)

    # Stats
    from collections import Counter
    print(f'Total: {len(result)} items')
    print('Level distribution:', dict(Counter(r['level'] for r in result)))
    print('Leaves:', sum(1 for r in result if r['is_leaf']))
    print('Konsesi-flagged:', sum(1 for r in result if r['mendukung_konsesi']))
    print('Wajib terpisah:', sum(1 for r in result if r['wajib_terpisah']))


main.root_sib = {}

if __name__ == '__main__':
    main()
