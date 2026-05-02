file_path = "resources/views/layouts/sidebar-app.blade.php"
with open(file_path, "r") as f:
    content = f.read()

# 1. Kasubbag Block
kasubbag_block = """        {{-- 1. Verifikasi SPP --}}
        <li>
          <a href="javascript:;" class="has-arrow">
            <div class="parent-icon"><i class="material-icons-outlined">history_edu</i></div>
            <div class="menu-title">Verifikasi SPP</div>
          </a>
          <ul>
            <li>
              <a href="{{ route('verifikasi-kasubag.spp.index') }}">
                <i class="material-icons-outlined">arrow_right</i>Kontrak
              </a>
            </li>
            <li>
              <a href="{{ route('verifikasi-kasubag.spp-perjaldin.index') }}">
                <i class="material-icons-outlined">arrow_right</i>Perjaldin
              </a>
            </li>
            <li>
              <a href="{{ route('verifikasi-spp.honor.index') }}">
                <i class="material-icons-outlined">arrow_right</i>Honor
              </a>
            </li>
          </ul>
        </li>"""

content = content.replace(kasubbag_block, "")

# 2. Koordinator Block
koordinator_block = """        {{-- Verifikasi SPP — hanya Koordinator Keuangan --}}
        <li>
          <a href="javascript:;" class="has-arrow">
            <div class="parent-icon"><i class="material-icons-outlined">history_edu</i></div>
            <div class="menu-title">Verifikasi SPP</div>
          </a>
          <ul>
            <li><a href="{{ route('verifikasi-koordinator.spp.index') }}"><i class="material-icons-outlined">arrow_right</i>Kontrak</a></li>
            <li><a href="{{ route('verifikasi-koordinator.spp-perjaldin.index') }}"><i class="material-icons-outlined">arrow_right</i>Perjaldin</a></li>
            <li><a href="{{ route('verifikasi-spp.honor.index') }}"><i class="material-icons-outlined">arrow_right</i>Honor</a></li>
          </ul>
        </li>"""

content = content.replace(koordinator_block, "")

# 3. PPK Block
ppk_block = """        {{-- 3. Verifikasi SPP --}}
        <li>
          <a href="javascript:;" class="has-arrow">
            <div class="parent-icon"><i class="material-icons-outlined">history_edu</i></div>
            <div class="menu-title">Verifikasi SPP</div>
          </a>
          <ul>
            <li><a href="{{ route('verifikasi-ppk.spp.index') }}"><i class="material-icons-outlined">arrow_right</i>Kontrak</a></li>
            <li><a href="{{ route('verifikasi-ppk.spp-perjaldin.index') }}"><i class="material-icons-outlined">arrow_right</i>Perjaldin</a></li>
            <li><a href="{{ route('verifikasi-spp.honor.index') }}"><i class="material-icons-outlined">arrow_right</i>Honor</a></li>
          </ul>
        </li>"""

shared_block = """        @endhasrole

        @hasanyrole('PPK|Kepala Subbagian Keuangan dan Tata Usaha|Koordinator Keuangan')
        {{-- Verifikasi SPP (Terpadu 3 Role) --}}
        <li>
          <a href="javascript:;" class="has-arrow">
            <div class="parent-icon"><i class="material-icons-outlined">history_edu</i></div>
            <div class="menu-title">Verifikasi SPP</div>
          </a>
          <ul>
            <li><a href="{{ route('verifikasi-spp.kontrak.index') }}"><i class="material-icons-outlined">arrow_right</i>Kontrak</a></li>
            <li><a href="{{ route('verifikasi-spp.perjaldin.index') }}"><i class="material-icons-outlined">arrow_right</i>Perjaldin</a></li>
            <li><a href="{{ route('verifikasi-spp.honor.index') }}"><i class="material-icons-outlined">arrow_right</i>Honor</a></li>
          </ul>
        </li>
        @endhasanyrole

        @hasrole('PPK')"""

if ppk_block in content:
    content = content.replace(ppk_block, shared_block)
else:
    print("Could not find ppk block!")

with open(file_path, "w") as fw:
    fw.write(content)
print("Updated sidebar-app.blade.php successfully")
