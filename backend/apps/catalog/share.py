"""Image de partage d'un produit (1080×1080) pour les statuts WhatsApp, Facebook, TikTok."""

from io import BytesIO

from django.core.cache import cache
from PIL import Image, ImageDraw, ImageFont, ImageOps

from apps.core.formatting import gnf

SIZE = 1080
YELLOW = (255, 193, 7)
INK = (33, 37, 41)


def _font(size, bold=True):
    name = "DejaVuSans-Bold.ttf" if bold else "DejaVuSans.ttf"
    try:
        return ImageFont.truetype(name, size)
    except OSError:
        return ImageFont.load_default()


def _wrap(draw, text, font, width, max_lines=2):
    lines, line = [], ""
    for word in text.split():
        candidate = f"{line} {word}".strip()
        if draw.textlength(candidate, font=font) <= width:
            line = candidate
        else:
            lines.append(line)
            line = word
    lines.append(line)
    if len(lines) > max_lines:
        lines = lines[:max_lines]
        lines[-1] = lines[-1].rstrip(".") + "…"
    return lines


def build_share_image(product):
    key = f"share-image:{product.pk}:{product.updated_at.timestamp()}"
    cached = cache.get(key)
    if cached:
        return cached

    canvas = Image.new("RGB", (SIZE, SIZE), "white")
    photo = product.images.first()
    if photo:
        with Image.open(photo.image) as img:
            canvas.paste(ImageOps.fit(img.convert("RGB"), (SIZE, 780)), (0, 0))
    else:
        ImageDraw.Draw(canvas).rectangle([0, 0, SIZE, 780], fill=YELLOW)

    draw = ImageDraw.Draw(canvas)
    draw.rectangle([0, 780, SIZE, SIZE], fill=INK)
    y = 805
    for line in _wrap(draw, product.name, _font(46), SIZE - 80):
        draw.text((40, y), line, fill="white", font=_font(46))
        y += 56
    price = gnf(product.price)
    draw.text((40, SIZE - 95), price, fill=YELLOW, font=_font(60))
    brand = "GuinéeMall"
    w = draw.textlength(brand, font=_font(38))
    draw.rounded_rectangle([SIZE - w - 80, SIZE - 100, SIZE - 30, SIZE - 35], radius=18, fill=YELLOW)
    draw.text((SIZE - w - 55, SIZE - 92), brand, fill=INK, font=_font(38))
    if product.compare_at_price:
        discount = round((1 - product.price / product.compare_at_price) * 100)
        draw.rounded_rectangle([30, 30, 230, 110], radius=40, fill=(220, 38, 38))
        draw.text((62, 45), f"-{discount}%", fill="white", font=_font(46))

    buffer = BytesIO()
    canvas.save(buffer, format="JPEG", quality=85)
    data = buffer.getvalue()
    cache.set(key, data, 3600)
    return data
