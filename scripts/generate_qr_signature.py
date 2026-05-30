#!/usr/bin/env python3
"""Generate QR code as SVG with centered logo overlay."""

import base64
import io
import re
import sys
from pathlib import Path

import segno


def generate_qr_svg(data: str, logo_path: str, output_path: str, dark: str = "#000000") -> None:
    qr = segno.make(data, error="h", micro=False)

    buffer = io.BytesIO()
    qr.save(buffer, kind="svg", scale=6, dark=dark, light="#ffffff", border=1)
    svg = buffer.getvalue().decode("utf-8")

    logo_bytes = Path(logo_path).read_bytes()
    logo_b64 = base64.b64encode(logo_bytes).decode("ascii")
    ext = Path(logo_path).suffix.lower().lstrip(".")
    mime = "png" if ext == "png" else ext

    match = re.search(r'viewBox="0 0 (\d+) (\d+)"', svg)
    if not match:
        match = re.search(r'width="(\d+)" height="(\d+)"', svg)

    if match:
        width = int(match.group(1))
        height = int(match.group(2))
        logo_size = int(min(width, height) * 0.22)
        x = (width - logo_size) // 2
        y = (height - logo_size) // 2
        pad = 4

        overlay = (
            f'<rect x="{x - pad}" y="{y - pad}" '
            f'width="{logo_size + pad * 2}" height="{logo_size + pad * 2}" '
            f'fill="#ffffff" rx="2"/>'
            f'<image x="{x}" y="{y}" width="{logo_size}" height="{logo_size}" '
            f'href="data:image/{mime};base64,{logo_b64}" '
            f'preserveAspectRatio="xMidYMid meet"/>'
        )
        svg = svg.replace("</svg>", overlay + "</svg>")

    out = Path(output_path)
    out.parent.mkdir(parents=True, exist_ok=True)
    out.write_text(svg, encoding="utf-8")


if __name__ == "__main__":
    if len(sys.argv) < 4:
        print("Usage: generate_qr_signature.py <data> <logo_path> <output_path>", file=sys.stderr)
        sys.exit(1)

    generate_qr_svg(sys.argv[1], sys.argv[2], sys.argv[3])
    print(sys.argv[3])
