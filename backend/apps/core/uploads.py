"""
Contrôle des fichiers envoyés par les utilisateurs.

On ne se fie jamais au nom ni au type annoncés par le navigateur (faciles à falsifier) :
le vrai format est reconnu à partir des premiers octets du fichier. Une page HTML ou un SVG
déguisé en « vidéo » est refusé, car servi depuis le site il pourrait voler des comptes (XSS).
Les fichiers acceptés sont renommés au hasard avec l'extension de leur vrai format.
"""

import uuid
from io import BytesIO

from django.core.exceptions import ValidationError
from django.core.files.uploadedfile import UploadedFile
from PIL import Image

MB = 1024 * 1024

# Signature → (famille, extension)
SIGNATURES = [
    (lambda b: b.startswith(b"\xff\xd8\xff"), ("image", "jpg")),
    (lambda b: b.startswith(b"\x89PNG\r\n\x1a\n"), ("image", "png")),
    (lambda b: b[:4] == b"RIFF" and b[8:12] == b"WEBP", ("image", "webp")),
    (lambda b: b[:4] == b"\x1a\x45\xdf\xa3", ("media", "webm")),  # WebM / Matroska (vidéo ou vocal)
    (lambda b: b[:4] == b"OggS", ("audio", "ogg")),
    (lambda b: b[4:8] == b"ftyp" and b[8:11] in (b"M4A",), ("audio", "m4a")),
    (lambda b: b[4:8] == b"ftyp", ("video", "mp4")),  # MP4 / MOV (et vocaux iPhone)
    (lambda b: b.startswith(b"ID3") or b[:2] in (b"\xff\xfb", b"\xff\xf3", b"\xff\xf2"), ("audio", "mp3")),
    (lambda b: b.startswith(b"%PDF-"), ("pdf", "pdf")),
]


def sniff(upload):
    """Renvoie (famille, extension) d'après le contenu réel, ou (None, None)."""
    position = upload.tell() if hasattr(upload, "tell") else 0
    upload.seek(0)
    head = upload.read(32)
    upload.seek(position)
    for test, result in SIGNATURES:
        if test(head):
            return result
    return None, None


def check_upload(upload, allowed, max_mb):
    """
    Vérifie un fichier envoyé. `allowed` : familles acceptées parmi image, video, audio, media, pdf
    (« media » = WebM, qui peut être une vidéo ou un message vocal). Renomme le fichier au hasard.
    Renvoie la famille reconnue.
    """
    if upload.size > max_mb * MB:
        raise ValidationError(f"Fichier trop lourd ({max_mb} Mo maximum).")
    family, ext = sniff(upload)
    accepted = family in allowed or (family == "media" and ({"video", "audio"} & set(allowed)))
    if not accepted:
        raise ValidationError("Format de fichier non accepté.")
    if family == "image":
        try:
            upload.seek(0)
            Image.open(BytesIO(upload.read())).verify()  # image réellement décodable
        except Exception as e:
            raise ValidationError("Image illisible ou corrompue.") from e
        finally:
            upload.seek(0)
    upload.name = f"{uuid.uuid4().hex}.{ext}"
    return family


def validate_image_5mb(upload):
    """Validateur des ImageField des modèles : contrôle seulement les fichiers qui viennent d'être envoyés."""
    if isinstance(upload, UploadedFile):
        check_upload(upload, {"image"}, 5)
