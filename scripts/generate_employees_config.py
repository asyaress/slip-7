#!/usr/bin/env python3
"""Generate config/employees.php entries from Excel NIP file."""

import openpyxl
from pathlib import Path

EXCEL = Path(__file__).resolve().parents[2] / "NOMOR INDUK KARYAWAN TOEDJOE SINAR GROUP (2).xlsx"

CONFIG_NAMES = {
    "SUGIANTO": ("sugynuansafx@gmail.com", "Kepala Maintenance Operator"),
    "MUHAMMAD NOR DIANSYAH": ("Nurdinzds@gmail.com", "Kepala Operator Indoor"),
    "SRI WAHYUNI": ("uyung109@gmail.com", "Kepala Desainer"),
    "FELIX OCTAVIAN MAHENDRA": ("mahendrafelix@gmail.com", "Kepala Admin & Personalia"),
    "SUTIKNO": ("sutiknokosong7@gmail.com", "Kepala Finishing"),
    "DEKI SAPUTRA": ("pandabakpao10@gmail.com", "Kepala TEXTILE/DTF"),
    "ISMAIL": ("mail87.ismail@gmail.com", "Asisten Operator Indoor"),
    "MUHAJIR": ("ajirgalaxy@gmail.com", "Kepala Laser/UV/DTF"),
    "MOHAMMAD NADZORI": ("m.nadzory@gmail.com", "Asisten Operator Indoor"),
    "SANDRA WIDIYA": ("sandrawdy40@gmail.com", "Design & Kepala Kasir"),
    "MUHAMMAD IHSANUL AKMAL": ("muhammadihsanulakmal.18@gmail.com", "Kepala Operator Digital A3"),
    "AGUNG DWI RAHMADI": ("agungdr463@gmail.com", "Asisten Operator Indoor"),
    "ANJASMARA RAMADHAN": ("anjasoedinson@gmail.com", "Asisten Digital A3"),
    "FANI NOR HAMDANI": ("faninh41@gmail.com", "Asisten Desainer"),
    "IKE TRIWAHYUNI": ("triwahyuniike50@gmail.com", "Asisten Desainer & Kasir"),
    "AHMAD RIDHANI": ("ahmadridhani87@gmail.com", "Asisten Desainer"),
    "ZUASA RAZZAQ GHANI": ("zuasarazak1@gmail.com", "Asisten Desainer"),
    "MUHAMMAD FERDIAN": ("muhammadferdian0451@gmail.com", "Asisten Desainer"),
    "ARYA GYMNAS FATAHILAH": ("aryagymnasf25@gmail.com", "Asisten Textile/DTF"),
    "RISNUR RAMADHAN": ("ramadhanrisnur@gmail.com", "Asisten Desainer"),
    "MAULANA RUMI DAENG RINGGI": ("itsmeschweigen@gmail.com", "Asisten Operator Outdoor"),
    "MUHAMMAD FAUZAN": ("m.f4uzan12477@gmail.com", "Asisten Operator Outdoor"),
    "NUR RINO AIDUL FADLI": ("rinofadlipratama04@gmail.com", "Kepala Konten & Marketing"),
    "RUSMONO HADI": ("Rusmonohady@gmail.com", "Asisten Operator Outdoor"),
    "MUHAMMAD YUSRIL ARIMULIA": ("sayangyusril22@gmail.com", "Kepala Pengambilan"),
    "MUHAMMAD ZIQI FARHAN": ("ziqigustinaldi@gmail.com", "Asisten Operator Outdoor"),
    "RISKI NUR HALIS": ("riskinurhalis11@gmail.com", "Asisten Laser/UV/DTF"),
    "AHMAT CAHYADI": ("cahyadiahmat99@gmail.com", "Asisten Pengambilan"),
    "MUHAMMAD RAWINDRA FADILAH": ("Fadilfira31@gmail.com", "Asisten Digital A3"),
    "MUHAMMAD KAMAL": ("raeelyy21@gmail.com", "Asisten Merchandise"),
    "MUHAMMAD ILHAM NAZHIF": ("m.ilhamnazhif23@gmail.com", "Asisten Operator Outdoor"),
    "ELGI SAMSUDDIN": ("elgi.samsuddin@toedjoesinargroup.com", "Asisten Operator Outdoor"),
    "AYU NANDA WULANDARI": ("ayunandawul15@gmail.com", "Desainer Admin Online"),
    "DAVI JUNIAR": ("juniardavi5@gmail.com", "Asisten Digital A3"),
    "MUHAMMAD ZIKRI FARHAN": ("zikrifarhan05@gmail.com", "Asisten Indoor Cutting"),
    "MUHAMMAD NAUFAL PRIMANANDA": ("az5733356@gmail.com", "Asisten Pengambilan"),
    "MUHAMMAD ZAKI MUBARAK": ("zm0700910@gmail.com", "Asisten Pengambilan"),
    "ATHASYAHRI SYAWAL FAHREZY": ("athafarez2412@gmail.com", "Website"),
    "MUHAMMAD RAFI'I": ("rfyxyz@gmail.com", "Asisten Desainer"),
    "EFIN QOID AMALLUDIN": ("efinqoidamalludin@gmail.com", "Asisten Desainer"),
    "IMROATUS SHOLIKHA": ("imroatussholikha8597@gmail.com", "Asisten Desainer"),
}


def php_str(value: str) -> str:
    return "'" + value.replace("\\", "\\\\").replace("'", "\\'") + "'"


def main() -> None:
    wb = openpyxl.load_workbook(EXCEL, data_only=True)
    ws = wb.active
    lines = []

    for r in range(3, ws.max_row + 1):
        no = ws.cell(r, 1).value
        if no is None:
            continue

        no = int(no)
        name = str(ws.cell(r, 3).value).strip().upper()
        birth_year = int(ws.cell(r, 4).value)
        birth_month = str(ws.cell(r, 5).value).zfill(2)
        birth_day = str(ws.cell(r, 6).value).zfill(2)
        hire_year = int(ws.cell(r, 7).value)
        hire_month = str(ws.cell(r, 8).value).zfill(2)
        gender = str(ws.cell(r, 9).value).strip()
        address = ws.cell(r, 11).value
        nip = f"TSG-15-{hire_year}-{birth_day}-{birth_month}-{birth_year}-{no:02d}"

        email, jabatan = CONFIG_NAMES.get(name, (f"employee{no}@toedjoesinargroup.com", "Karyawan"))
        alamat = (address or "Samarinda").strip()

        lines.append(
            f"        {no} => ['nip' => {php_str(nip)}, 'name' => {php_str(name)}, "
            f"'email' => {php_str(email)}, 'jabatan' => {php_str(jabatan)}, "
            f"'alamat' => {php_str(alamat)}, 'tgl_lahir' => '{birth_year}-{birth_month}-{birth_day}', "
            f"'tgl_masuk' => '{hire_year}-{hire_month}-01', 'jenis_kelamin' => {php_str(gender)}],"
        )

    print("\n".join(lines))


if __name__ == "__main__":
    main()
