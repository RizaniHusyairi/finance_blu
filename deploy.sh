#!/usr/bin/env bash
#
# Deploy / update SIKEREN di server produksi.
#
# Pemakaian (sebagai root, dari folder mana saja):
#   bash /var/www/aptpairport.id/sikeren/deploy.sh
#
# Opsi:
#   --force-build   selalu jalankan composer install & npm build walau tidak ada perubahan
#   --no-maintenance  jangan aktifkan maintenance mode selama deploy
#
# Git pull memakai SSH key default root (~/.ssh/id_ed25519) yang terdaftar
# sebagai deploy key read-only di repo GitHub.

set -euo pipefail

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
APP_USER="www-data"
BRANCH="main"

FORCE_BUILD=0
USE_MAINTENANCE=1
for arg in "$@"; do
    case "$arg" in
        --force-build) FORCE_BUILD=1 ;;
        --no-maintenance) USE_MAINTENANCE=0 ;;
        *) echo "Opsi tidak dikenal: $arg" >&2; exit 1 ;;
    esac
done

log() { printf '\n\033[1;34m==> %s\033[0m\n' "$*"; }
warn() { printf '\033[1;33m[!] %s\033[0m\n' "$*"; }

# Artisan dijalankan sebagai www-data supaya file cache/log tidak jadi milik root.
artisan() { sudo -u "$APP_USER" php "$APP_DIR/artisan" "$@"; }

if [[ $EUID -ne 0 ]]; then
    echo "Jalankan sebagai root: sudo bash $0" >&2
    exit 1
fi

cd "$APP_DIR"
export COMPOSER_ALLOW_SUPERUSER=1

# Pastikan aplikasi keluar dari maintenance mode apa pun yang terjadi.
IN_MAINTENANCE=0
on_exit() {
    local code=$?
    if [[ $IN_MAINTENANCE -eq 1 ]]; then
        artisan up || true
    fi
    if [[ $code -ne 0 ]]; then
        printf '\n\033[1;31m[x] Deploy GAGAL (exit %s). Periksa pesan di atas.\033[0m\n' "$code"
    fi
}
trap on_exit EXIT

log "Cek perubahan lokal di server"
if ! git diff --quiet || ! git diff --cached --quiet; then
    warn "Ada file yang diubah langsung di server:"
    git status --short --untracked-files=no
    echo "Simpan/buang perubahan itu dulu (git stash / git checkout -- <file>), lalu ulangi." >&2
    exit 1
fi

OLD_REV="$(git rev-parse HEAD)"

log "Git pull ($BRANCH)"
git fetch origin "$BRANCH"
git merge --ff-only "origin/$BRANCH"
NEW_REV="$(git rev-parse HEAD)"

if [[ "$OLD_REV" == "$NEW_REV" ]]; then
    echo "Tidak ada commit baru ($(git rev-parse --short HEAD))."
else
    git --no-pager log --oneline "$OLD_REV..$NEW_REV"
fi

CHANGED="$(git diff --name-only "$OLD_REV" "$NEW_REV")"
changed() { grep -qE "$1" <<<"$CHANGED"; }

if [[ $USE_MAINTENANCE -eq 1 && "$OLD_REV" != "$NEW_REV" ]]; then
    log "Maintenance mode ON"
    artisan down --retry=15 || true
    IN_MAINTENANCE=1
fi

if [[ $FORCE_BUILD -eq 1 || ! -d vendor ]] || changed '^composer\.(json|lock)$'; then
    log "Composer install"
    composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
else
    echo "Composer: tidak ada perubahan, dilewati."
fi

if [[ $FORCE_BUILD -eq 1 || ! -f public/build/manifest.json ]] \
    || changed '^(resources/(js|css|sass|scss)/|package(-lock)?\.json$|vite\.config\.js$)'; then
    if command -v npm >/dev/null 2>&1; then
        log "Build aset frontend (npm)"
        npm ci --no-audit --no-fund
        npm run build
    else
        warn "npm tidak ditemukan; aset frontend TIDAK di-build."
    fi
else
    echo "Aset frontend: tidak ada perubahan, dilewati."
fi

log "Perbaiki kepemilikan file"
chown -R "$APP_USER:$APP_USER" "$APP_DIR"
chmod -R ug+rwX storage bootstrap/cache

log "Migrasi database"
artisan migrate --force

log "Refresh cache Laravel"
artisan optimize:clear
artisan optimize
[[ -L public/storage ]] || artisan storage:link

log "Restart queue worker"
artisan queue:restart

if [[ $IN_MAINTENANCE -eq 1 ]]; then
    log "Maintenance mode OFF"
    artisan up
    IN_MAINTENANCE=0
fi

log "Selesai: $(git rev-parse --short HEAD) — $(git log -1 --format=%s)"
