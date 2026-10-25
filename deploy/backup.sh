#!/usr/bin/env bash
# Sauvegarde quotidienne de GuinéeMall : base PostgreSQL + fichiers envoyés + documents privés.
# Cron (tous les jours à 3 h) :  0 3 * * *  /srv/guineemall/deploy/backup.sh >> /var/log/guineemall-backup.log 2>&1
#
# Les sauvegardes sont chiffrées (elles contiennent des données personnelles et des pièces d'identité).
# BACKUP_PASSPHRASE doit être défini dans l'environnement et conservé hors du serveur.
# Pensez à copier aussi les sauvegardes HORS du serveur (autre machine, stockage externe).

set -euo pipefail

APP_DIR="${APP_DIR:-/srv/guineemall/backend}"
DEST="${BACKUP_DIR:-/var/backups/guineemall}"
KEEP_DAYS="${KEEP_DAYS:-14}"
DB_NAME="${DB_NAME:-guineemall}"
: "${BACKUP_PASSPHRASE:?Définir BACKUP_PASSPHRASE}"

STAMP="$(date +%Y-%m-%d_%H%M)"
mkdir -p "$DEST"
umask 077

encrypt() { gpg --batch --yes --symmetric --cipher-algo AES256 --passphrase "$BACKUP_PASSPHRASE" -o "$1.gpg" "$1" && rm -f "$1"; }

pg_dump --format=custom --no-owner "$DB_NAME" > "$DEST/db_$STAMP.dump"
encrypt "$DEST/db_$STAMP.dump"

DIRS=()
for d in media private_media; do [ -d "$APP_DIR/$d" ] && DIRS+=("$d"); done
tar -czf "$DEST/media_$STAMP.tar.gz" -C "$APP_DIR" "${DIRS[@]}"
encrypt "$DEST/media_$STAMP.tar.gz"

find "$DEST" -name "*.gpg" -mtime +"$KEEP_DAYS" -delete
echo "$(date -Is) sauvegarde OK : $STAMP"

# Restauration :
#   gpg -d db_XXXX.dump.gpg > db.dump && pg_restore --clean --no-owner -d guineemall db.dump
#   gpg -d media_XXXX.tar.gz.gpg | tar -xz -C /srv/guineemall/backend
