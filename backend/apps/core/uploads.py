"""
Contrôle des fichiers envoyés par les utilisateurs.

On ne se fie jamais au nom ni au type annoncés par le navigateur (faciles à falsifier) :
une page HTML ou un SVG déguisé en « vidéo » ou en « photo » est refusé, car servi depuis
le site il pourrait voler des comptes (XSS). Les fichiers acceptés sont renommés au hasard.

Images : toute vraie image lisible par Pillow est acceptée (JPEG, PNG, WebP, GIF, BMP,
AVIF, HEIC des iPhone…), puis réécrite : orientation corrigée, 2 048 px maximum,
métadonnées supprimées (dont la position GPS des photos de téléphone), format JPEG,
ou WebP si l'image a de la transparence.
"""

import uuid
from io import BytesIO

from django.core.exceptions import ValidationError
from django.core.files.uploadedfile import UploadedFile
from PIL import Image, ImageOps

MB = 1024 * 1024

# Signature → (famille, extension)
IMAGE_FORMATS = {"JPEG", "MPO", "PNG", "WEBP", "GIF", "BMP", "AVIF", "HEIF", "TIFF"}
MAX_IMAGE_SIDE = 2048
ACCEPTED_TEXT = "photo (JPEG, PNG, WebP, HEIC, AVIF, GIF)"

SIGNATURES = [
    (lambda b: b[:4] == b"\x1a\x45\xdf\xa3", ("media", "webm")),  # WebM / Matroska (vidéo ou vocal)
    (lambda b: b[:4] == b"OggS", ("audio", "ogg")),
    (lambda b: b[4:8] == b"ftyp" and b[8:11] in (b"M4A",), ("audio", "m4a")),
    (lambda b: b[4:8] == b"ftyp", ("video", "mp4")),  # MP4 / MOV (et vocaux iPhone)
    (lambda b: b.startswith(b"ID3") or b[:2] in (b"\xff\xfb", b"\xff\xf3", b"\xff\xf2"), ("audio", "mp3")),
    (lambda b: b.startswith(b"%PDF-"), ("pdf", "pdf")),
]


def open_image(upload):
    """Ouvre le fichier comme image si c'en est vraiment une (format reconnu par Pillow), sinon None."""
    upload.seek(0)
    try:
        image = Image.open(BytesIO(upload.read()))
        image.load()
    except Exception:
        return None
    finally:
        upload.seek(0)
    return image if image.format in IMAGE_FORMATS else None


def sniff(upload):
    """Renvoie (famille, extension) d'après le contenu réel, ou (None, None)."""
    position = upload.tell() if hasattr(upload, "tell") else 0
    upload.seek(0)
    head = upload.read(32)
    upload.seek(position)
    # HEIC / AVIF commencent aussi par « ftyp » : ce sont des images, pas des vidéos
    if head[4:8] == b"ftyp" and head[8:12] in (b"heic", b"heix", b"mif1", b"msf1", b"avif", b"avis", b"hevc"):
        return "image", None
    for test, result in SIGNATURES:
        if test(head):
            return result
    return None, None


def normalize_image(upload, image):
    """Réécrit l'image : orientation, taille, sans métadonnées, format lisible partout."""
    image = ImageOps.exif_transpose(image)
    image.thumbnail((MAX_IMAGE_SIDE, MAX_IMAGE_SIDE))
    transparent = image.mode in ("RGBA", "LA") or (image.mode == "P" and "transparency" in image.info)
    buffer = BytesIO()
    if transparent:
        image.convert("RGBA").save(buffer, format="WEBP", quality=90)
        ext = "webp"
    else:
        image.convert("RGB").save(buffer, format="JPEG", quality=85, optimize=True)
        ext = "jpg"
    data = buffer.getvalue()
    # Gros fichiers : Django les garde dans un fichier temporaire qu'il déplace tel quel → on le réécrit
    if hasattr(upload, "temporary_file_path"):
        upload.file.seek(0)
        upload.file.truncate()
        upload.file.write(data)
        upload.file.flush()
        upload.file.seek(0)
    else:
        upload.file = BytesIO(data)
    upload.size = len(data)
    upload.content_type = "image/webp" if transparent else "image/jpeg"
    return ext


def check_upload(upload, allowed, max_mb):
    """
    Vérifie un fichier envoyé. `allowed` : familles acceptées parmi image, video, audio, media, pdf
    (« media » = WebM, qui peut être une vidéo ou un message vocal). Renomme le fichier au hasard
    et réécrit les images. Renvoie la famille reconnue.
    """
    if upload.size > max_mb * MB:
        raise ValidationError(f"Fichier trop lourd ({max_mb} Mo maximum).")
    if "image" in allowed:
        image = open_image(upload)
        if image is not None:
            ext = normalize_image(upload, image)
            upload.name = f"{uuid.uuid4().hex}.{ext}"
            return "image"
    family, ext = sniff(upload)
    accepted = family != "image" and (family in allowed or (family == "media" and ({"video", "audio"} & set(allowed))))
    if not accepted:
        kinds = [k for k in (("image", ACCEPTED_TEXT), ("video", "vidéo (MP4, WebM)"), ("audio", "message vocal"), ("pdf", "PDF")) if k[0] in allowed]
        raise ValidationError("Format non accepté. Envoyez : " + ", ".join(label for _, label in kinds) + ".")
    upload.name = f"{uuid.uuid4().hex}.{ext}"
    return family


def validate_image_5mb(upload):
    """
    Validateur des ImageField des modèles : contrôle (et réécrit) seulement les fichiers qui
    viennent d'être envoyés. Jusqu'à 15 Mo en entrée : la photo enregistrée est bien plus légère.
    """
    if isinstance(upload, UploadedFile):
        check_upload(upload, {"image"}, 15)
