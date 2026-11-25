import docx
from docx.shared import Inches, Pt, RGBColor
from docx.enum.text import WD_ALIGN_PARAGRAPH
from docx.enum.table import WD_TABLE_ALIGNMENT, WD_ALIGN_VERTICAL
from docx.oxml import OxmlElement, parse_xml
from docx.oxml.ns import nsdecls, qn

def set_cell_background(cell, fill_hex):
    tcPr = cell._tc.get_or_add_tcPr()
    tcPr.append(parse_xml(f'<w:shd {nsdecls("w")} w:fill="{fill_hex}"/>'))

def set_cell_margins(cell, top=100, bottom=100, left=150, right=150):
    tcPr = cell._tc.get_or_add_tcPr()
    tcMar = OxmlElement('w:tcMar')
    for m, val in [('top', top), ('bottom', bottom), ('left', left), ('right', right)]:
        node = OxmlElement(f'w:{m}')
        node.set(qn('w:w'), str(val))
        node.set(qn('w:type'), 'dxa')
        tcMar.append(node)
    tcPr.append(tcMar)

def create_document():
    doc = docx.Document()

    # Page Margins
    for section in doc.sections:
        section.top_margin = Inches(1)
        section.bottom_margin = Inches(1)
        section.left_margin = Inches(1)
        section.right_margin = Inches(1)

    # Styles
    styles = doc.styles
    normal_style = styles['Normal']
    normal_style.font.name = 'Calibri'
    normal_style.font.size = Pt(11)
    normal_style.font.color.rgb = RGBColor(0x1F, 0x29, 0x37) # #1f2937
    normal_style.paragraph_format.line_spacing = 1.15
    normal_style.paragraph_format.space_after = Pt(6)

    # ==================== COVER / HEADER BANNER ====================
    title_p = doc.add_paragraph()
    title_p.paragraph_format.space_before = Pt(10)
    title_p.paragraph_format.space_after = Pt(2)
    run_brand = title_p.add_run("spbu.hub — DOKUMEN DESKRIPSI PROYEK")
    run_brand.font.name = 'Calibri'
    run_brand.font.size = Pt(12)
    run_brand.font.bold = True
    run_brand.font.color.rgb = RGBColor(0x05, 0x96, 0x69) # Emerald

    h1_p = doc.add_paragraph()
    h1_p.paragraph_format.space_before = Pt(2)
    h1_p.paragraph_format.space_after = Pt(6)
    run_h1 = h1_p.add_run("Sistem Monitoring Distribusi Logistik BBM & Portal Layanan SPBU Warga")
    run_h1.font.name = 'Calibri'
    run_h1.font.size = Pt(22)
    run_h1.font.bold = True
    run_h1.font.color.rgb = RGBColor(0x0F, 0x17, 0x2A) # Dark Navy

    sub_p = doc.add_paragraph()
    sub_p.paragraph_format.space_after = Pt(18)
    run_sub = sub_p.add_run("Dokumen Spesifikasi Fungsional, Latar Belakang Masalah, Skenario Aktor, dan Arsitektur Sistem untuk Tugas Besar (Tubes)")
    run_sub.font.italic = True
    run_sub.font.size = Pt(11)
    run_sub.font.color.rgb = RGBColor(0x64, 0x74, 0x8B)

    # Divider line
    div_p = doc.add_paragraph()
    div_p.paragraph_format.space_after = Pt(14)
    run_div = div_p.add_run("―" * 55)
    run_div.font.color.rgb = RGBColor(0xCB, 0xD5, 0xE1)

    # ==================== 1. RINGKASAN EKSEKUTIF ====================
    h1 = doc.add_heading(level=1)
    r = h1.add_run("1. Ringkasan Eksekutif (Executive Summary)")
    r.font.color.rgb = RGBColor(0x0F, 0x17, 0x2A)
    r.font.size = Pt(16)
    r.font.bold = True

    p = doc.add_paragraph()
    p.add_run(
        "Proyek spbu.hub adalah aplikasi berbasis web yang dikembangkan untuk memecahkan persoalan ketimpangan informasi "
        "dan ketidakefisienan dalam rantai distribusi Bahan Bakar Minyak (BBM). Sistem ini mengintegrasikan dua sisi penting: "
    )
    b1 = doc.add_paragraph(style='List Bullet')
    r1 = b1.add_run("Sisi Hulu (Admin Logistik Pusat / Dispatcher): ")
    r1.bold = True
    b1.add_run("Platform dashboard analitik spasial untuk memantau sisa volume tangki pendam SPBU di seluruh wilayah kota, memantau posisi pergerakan armada truk tangki BBM via GPS secara real-time, dan menjadwalkan suplai BBM otomatis.")

    b2 = doc.add_paragraph(style='List Bullet')
    r2 = b2.add_run("Sisi Hilir (Portal Warga / Pengendara Kendaraan): ")
    r2.bold = True
    b2.add_run("Antarmuka mobile-first bagi masyarakat untuk mengecek SPBU terdekat yang stok bahan bakarnya (Pertalite, Pertamax, Solar) tersedia, mengecek estimasi waktu tunggu antrean, membuka navigasi arah, dan melaporkan kendala di lapangan.")

    # Callout Box: Elevator Pitch
    table = doc.add_table(rows=1, cols=1)
    table.alignment = WD_TABLE_ALIGNMENT.CENTER
    cell = table.cell(0, 0)
    set_cell_background(cell, "F0FDF4") # Light emerald
    set_cell_margins(cell, top=140, bottom=140, left=200, right=200)
    cp = cell.paragraphs[0]
    cp.paragraph_format.space_after = Pt(2)
    cr_title = cp.add_run("💡 Intisari Proyek (Elevator Pitch):\n")
    cr_title.bold = True
    cr_title.font.color.rgb = RGBColor(0x06, 0x5F, 0x46)
    cp.add_run(
        "\"spbu.hub mentransformasi distribusi BBM menjadi transparan dan otomatis: Admin logistik dapat mencegah kelangkaan tangki "
        "dengan penjadwalan armada truk terpantau GPS, sementara warga pengendara tidak lagi membuang waktu mengantre di SPBU yang bensinnya ternyata habis.\""
    )
    doc.add_paragraph().paragraph_format.space_after = Pt(10)

    # ==================== 2. LATAR BELAKANG & RUMUSAN MASALAH ====================
    h2 = doc.add_heading(level=1)
    r = h2.add_run("2. Latar Belakang & Masalah Riil Lapangan")
    r.font.color.rgb = RGBColor(0x0F, 0x17, 0x2A)
    r.font.size = Pt(16)
    r.font.bold = True

    doc.add_paragraph(
        "Kebutuhan bahan bakar minyak merupakan urat nadi mobilitas dan logistik perkotaan. Kendati demikian, "
        "pengelolaan distribusi BBM dari depo induk (TBBM) menuju SPBU hingga ke konsumen akhir masih menghadapi "
        "sejumlah kendala mendasar:"
    )

    p_prob1 = doc.add_paragraph()
    p_prob1.add_run("a. Asimetri Informasi bagi Pengendara Umum:\n").bold = True
    p_prob1.add_run(
        "Masyarakat sering kali baru mengetahui bahwa suatu jenis BBM (terutama Pertalite atau Solar) habis setelah tiba atau "
        "mengantre puluhan menit di lokasi SPBU. Tidak ada media publik yang menampilkan status ketersediaan bahan bakar secara "
        "langsung per SPBU."
    )

    p_prob2 = doc.add_paragraph()
    p_prob2.add_run("b. Titik Buta (Blind Spot) Pemantauan Armada Truk Tangki:\n").bold = True
    p_prob2.add_run(
        "Pusat kendali distribusi BBM membutuhkan visibilitas pergerakan armada truk tangki di perjalanan (in-transit). "
        "Tanpa pemetaan spasial GPS, estimasi waktu tiba (ETA) sering tidak akurat dan rentan terjadi kekosongan stok tangki pendam."
    )

    p_prob3 = doc.add_paragraph()
    p_prob3.add_run("c. Kurangnya Partisipasi & Saluran Pengaduan Warga:\n").bold = True
    p_prob3.add_run(
        "Keluhan antrean mengular, dispenser rusak, atau pelayanan SPBU yang tidak sesuai spesifikasi jarang terdokumentasi "
        "dengan bukti visual dan sering lambat ditanggapi oleh pihak pengelola."
    )

    # ==================== 3. TUJUAN & MANFAAT SISTEM ====================
    h3 = doc.add_heading(level=1)
    r = h3.add_run("3. Tujuan & Manfaat Pengembangan Sistem")
    r.font.color.rgb = RGBColor(0x0F, 0x17, 0x2A)
    r.font.size = Pt(16)
    r.font.bold = True

    tbl_goal = doc.add_table(rows=4, cols=2)
    tbl_goal.alignment = WD_TABLE_ALIGNMENT.CENTER
    headers = ["Kategori", "Tujuan & Nilai Manfaat yang Dicapai"]
    for i, h in enumerate(headers):
        c = tbl_goal.cell(0, i)
        set_cell_background(c, "0F172A")
        set_cell_margins(c, top=120, bottom=120, left=150, right=150)
        p = c.paragraphs[0]
        r = p.add_run(h)
        r.bold = True
        r.font.color.rgb = RGBColor(0xFF, 0xFF, 0xFF)

    data_goal = [
        ("Efisiensi Logistik", "Mendeteksi tangki SPBU yang berada di bawah ambang batas kritis (<30%) secara otomatis dan memfasilitasi dispatching truk tangki secara cepat dan terdata."),
        ("Kepastian bagi Warga", "Menyediakan informasi peta interaktif mengenai SPBU mana yang buka 24 jam, memiliki stok BBM yang dicari, dan kondisi antrean yang lancar (<10 menit)."),
        ("Transparansi Aduan", "Membangun saluran aduan berbasis crowdsourcing dengan foto bukti dan sistem tracking progres berjenjang (Menunggu → Diverifikasi → Ditindaklanjuti → Selesai).")
    ]
    for row_idx, (cat, desc) in enumerate(data_goal, start=1):
        c0 = tbl_goal.cell(row_idx, 0)
        c1 = tbl_goal.cell(row_idx, 1)
        bg = "F8FAFC" if row_idx % 2 == 1 else "FFFFFF"
        set_cell_background(c0, bg)
        set_cell_background(c1, bg)
        set_cell_margins(c0, top=100, bottom=100, left=150, right=150)
        set_cell_margins(c1, top=100, bottom=100, left=150, right=150)
        p0 = c0.paragraphs[0]
        p0.add_run(cat).bold = True
        p1 = c1.paragraphs[0]
        p1.add_run(desc)

    doc.add_paragraph().paragraph_format.space_after = Pt(12)

    # ==================== 4. DESKRIPSI AKTOR & USE CASE ====================
    h4 = doc.add_heading(level=1)
    r = h4.add_run("4. Pembagian Peran (Aktor) & Use Case")
    r.font.color.rgb = RGBColor(0x0F, 0x17, 0x2A)
    r.font.size = Pt(16)
    r.font.bold = True

    doc.add_paragraph(
        "Sistem dirancang dengan memisahkan wewenang antara pengguna internal pengelola rantai pasok (Admin Logistik) "
        "dan pengguna publik (Warga Pengendara), sebagaimana direpresentasikan pada diagram Use Case Draw.io proyek:"
    )

    # Aktor 1
    doc.add_heading(level=2).add_run("A. Aktor 1: Admin Logistik Pusat (Central Dispatcher)").font.color.rgb = RGBColor(0x02, 0x84, 0xC7)
    doc.add_paragraph(
        "Admin Logistik bertugas di pusat kendali distribusi bahan bakar. Peran ini mengelola rantai pasokan dari Depo TBBM "
        "(Terminal Bahan Bakar Minyak) hingga ke tangki pendam SPBU. Fungsi-fungsi yang dimiliki meliputi:"
    )
    admin_features = [
        ("Dashboard Metrik Kesehatan Logistik", "Menampilkan visualisasi gauge arc indikator kesehatan distribusi (misal: 76% Normal), persentase tangki SPBU, dan peringatan dini stok menipis."),
        ("Peta Monitoring GPS Truk Live", "Melacak pergerakan truk tangki secara langsung di peta interaktif gelap (Dark Map), lengkap dengan informasi sopir, nomor polisi kendaraan, volume muatan, dan estimasi waktu tiba (ETA)."),
        ("Penjadwalan Suplai Armada Baru", "Menerbitkan penugasan armada truk tangki BBM (misal: Hino 500 Tangki 16KL kapasitas 16.000 Liter) ke SPBU yang tangkinya kritis."),
        ("Otomasi Restock Tangki BBM", "Sistem secara otomatis menambah volume liter BBM di tangki SPBU begitu status pengiriman truk dinyatakan 'Selesai' (Delivered)."),
        ("Manajemen Master Data SPBU & Harga", "Mengatur jam operasional SPBU, kapasitas maksimum tangki, dan pembaruan harga BBM resmi per liter."),
        ("Verifikasi & Tindak Lanjut Laporan Warga", "Memeriksa tiket aduan masyarakat, memvalidasi bukti foto antrean/kendala, dan memberikan respon resmi petugas.")
    ]
    for title, desc in admin_features:
        bp = doc.add_paragraph(style='List Bullet')
        bp.add_run(f"{title}: ").bold = True
        bp.add_run(desc)

    doc.add_paragraph().paragraph_format.space_after = Pt(8)

    # Aktor 2
    doc.add_heading(level=2).add_run("B. Aktor 2: Warga / Pengendara Umum (Citizen Portal)").font.color.rgb = RGBColor(0x05, 0x96, 0x69)
    doc.add_paragraph(
        "Pengendara motor atau mobil yang mengakses portal mobile melalui peramban ponsel pintar saat beraktivitas. "
        "Fitur-fitur yang disediakan mencakup:"
    )
    warga_features = [
        ("Pencarian & Filter Cepat Stok BBM", "Penyaringan instan SPBU terdekat berdasarkan kriteria: 'Semua SPBU', 'Pertalite Ready', 'Pertamax Ready', 'Antrean Lancar (<10 mnt)', dan 'Buka 24 Jam'."),
        ("Kartu Informasi SPBU & Waktu Tunggu", "Menampilkan jarak terdekat (misal: 0.8 km dari posisi Anda), estimasi menit antrean, status operasional buka/tutup, dan fasilitas penunjang (Musholla, Toilet, ATM, Nitrogen)."),
        ("Navigasi Rute Langsung (Google Maps)", "Tautan satu-klik untuk langsung membuka aplikasi Google Maps yang memandu arah berkendara ke titik koordinat SPBU terpilih."),
        ("Formulir Laporan Pengaduan Kendala", "Memungkinkan warga mengadukan masalah lapangan (BBM habis, antrean panjang mengular, dispenser rusak) lengkap dengan unggah foto bukti langsung dari kamera ponsel."),
        ("Live Stepper Pelacakan Progres Laporan", "Memantau status penanganan aduan secara bertahap (Menunggu → Diverifikasi → Ditindaklanjuti → Selesai) disertai catatan resmi dari petugas pengawas SPBU.")
    ]
    for title, desc in warga_features:
        bp = doc.add_paragraph(style='List Bullet')
        bp.add_run(f"{title}: ").bold = True
        bp.add_run(desc)

    # ==================== 5. ALUR KERJA SISTEM (END-TO-END WORKFLOW) ====================
    h5 = doc.add_heading(level=1)
    r = h5.add_run("5. Alur Kerja Sistem (End-to-End Workflow)")
    r.font.color.rgb = RGBColor(0x0F, 0x17, 0x2A)
    r.font.size = Pt(16)
    r.font.bold = True

    doc.add_paragraph(
        "Hubungan timbal balik antara aksi Admin Logistik dan informasi yang dinikmati Warga dapat digambarkan "
        "dalam tahapan operasional berikut:"
    )

    steps = [
        ("Tahap 1 — Deteksi Dini:", "Sensor ATG (Automatic Tank Gauge) mendeteksi stok Pertalite di SPBU Cilandak tersisa di bawah 20%. Status tangki otomatis berubah warna menjadi kuning/merah di dashboard admin."),
        ("Tahap 2 — Dispatching Truk Tangki:", "Admin Logistik membuat jadwal pengiriman baru: Truk Hino 16KL berangkat dari Depo TBBM Plumpang menuju SPBU Cilandak."),
        ("Tahap 3 — Pelacakan GPS Real-time:", "Marker truk tangki bergerak di peta secara dinamis. Waktu estimasi tiba (ETA) dihitung otomatis berdasarkan laju perjalanan armada."),
        ("Tahap 4 — Penerimaan & Auto-Restock:", "Setelah truk tiba di SPBU, status pengiriman beralih ke 'Selesai'. Sistem otomatis menginjeksi +16.000 Liter ke stok tangki SPBU terkait tanpa perlu input manual berulang."),
        ("Tahap 5 — Sinkronisasi Instan ke Portal Warga:", "Stok baru langsung terefleksi di portal ponsel warga. Pengendara di sekitar Cilandak kini melihat indikator Pertalite berstatus 'Ready' dan antrean kembali hijau (Lancar)."),
        ("Tahap 6 — Feedback Loop (Laporan Warga):", "Bila di lapangan terdapat kendala pompa mati, warga melapor via form + foto. Laporan masuk ke Admin Logistik untuk segera diverifikasi dan ditindaklanjuti.")
    ]
    for st_title, st_desc in steps:
        p_step = doc.add_paragraph()
        p_step.add_run(f"{st_title} ").bold = True
        p_step.add_run(st_desc)

    doc.add_paragraph().paragraph_format.space_after = Pt(10)

    # ==================== 6. ARSITEKTUR & SPESIFIKASI TEKNIS ====================
    h6 = doc.add_heading(level=1)
    r = h6.add_run("6. Arsitektur Perangkat Lunak & Spesifikasi Teknis")
    r.font.color.rgb = RGBColor(0x0F, 0x17, 0x2A)
    r.font.size = Pt(16)
    r.font.bold = True

    tbl_tech = doc.add_table(rows=6, cols=3)
    tbl_tech.alignment = WD_TABLE_ALIGNMENT.CENTER
    th = ["Layer / Komponen", "Teknologi yang Dipakai", "Fungsi & Peranan Utama"]
    for i, h in enumerate(th):
        c = tbl_tech.cell(0, i)
        set_cell_background(c, "0F172A")
        set_cell_margins(c, top=120, bottom=120, left=150, right=150)
        p = c.paragraphs[0]
        r = p.add_run(h)
        r.bold = True
        r.font.color.rgb = RGBColor(0xFF, 0xFF, 0xFF)

    tech_data = [
        ("Backend Framework", "Laravel 11.x (PHP 8.3)", "Menangani routing, autentikasi multi-peran, API endpoints, validasi formulir, dan business logic peremajaan stok."),
        ("Frontend UI / UX", "Tailwind CSS + Blade Template", "Desain antarmuka bertema Bento Grid Obsidian Dark (#08090b) dengan aksen hijau emerald, responsif desktop dan mobile."),
        ("Peta Spasial (Mapping)", "Leaflet.js + OpenStreetMap", "Render peta interaktif dark tile tanpa watermark API key, serta mendukung tile resolusi tinggi Mapbox Dark v11 via token opsional."),
        ("Basis Data Relasional", "MySQL 8.x (XAMPP) & SQLite", "Penyimpanan data persisten: stasiun SPBU, jenis BBM, logistik armada pengiriman, antrean, dan tiket laporan aduan warga."),
        ("Deployment & CI/CD", "GitHub & Vercel Cloud Platform", "Hosting berbasis serverless production di https://spbu3-monitor.vercel.app terhubung otomatis ke repository GitHub master.")
    ]
    for row_idx, (layer, tech, role) in enumerate(tech_data, start=1):
        for col_idx, text in enumerate([layer, tech, role]):
            cell = tbl_tech.cell(row_idx, col_idx)
            bg = "F8FAFC" if row_idx % 2 == 1 else "FFFFFF"
            set_cell_background(cell, bg)
            set_cell_margins(cell, top=100, bottom=100, left=150, right=150)
            cell.paragraphs[0].add_run(text)

    doc.add_paragraph().paragraph_format.space_after = Pt(14)

    # ==================== 7. PANDUAN DEMO & AKUN PENGUJIAN ====================
    h7 = doc.add_heading(level=1)
    r = h7.add_run("7. Informasi Pengujian & Akun Demo Aplikasi")
    r.font.color.rgb = RGBColor(0x0F, 0x17, 0x2A)
    r.font.size = Pt(16)
    r.font.bold = True

    doc.add_paragraph("Aplikasi telah siap didemokan langsung secara daring maupun lokal dengan rincian akses berikut:")
    
    b_url = doc.add_paragraph(style='List Bullet')
    b_url.add_run("Tautan URL Production: ").bold = True
    b_url.add_run("https://spbu3-monitor.vercel.app")

    b_acc1 = doc.add_paragraph(style='List Bullet')
    b_acc1.add_run("Akun Admin Logistik Pusat: ").bold = True
    b_acc1.add_run("admin@spbumonitor.test  |  Password: password")

    b_acc2 = doc.add_paragraph(style='List Bullet')
    b_acc2.add_run("Akun Warga / Pengendara: ").bold = True
    b_acc2.add_run("warga@spbumonitor.test  |  Password: password (atau langsung akses /warga)")

    b_draw = doc.add_paragraph(style='List Bullet')
    b_draw.add_run("Diagram Use Case Draw.io: ").bold = True
    b_draw.add_run("Tersedia di file repositori: use_case_diagram.drawio (dapat dibuka di app.diagrams.net)")

    # Footer note
    doc.add_paragraph().paragraph_format.space_after = Pt(10)
    p_foot = doc.add_paragraph()
    p_foot.alignment = WD_ALIGN_PARAGRAPH.RIGHT
    r_foot = p_foot.add_run("Disusun untuk Keperluan Dokumentasi Tugas Besar (Tubes) — November 2026")
    r_foot.font.italic = True
    r_foot.font.size = Pt(9)
    r_foot.font.color.rgb = RGBColor(0x94, 0xA3, 0xB8)

    # Save
    out_path = "E:/TUBES/SPBU3/Deskripsi_Project_SPBU_Logistics_Monitoring.docx"
    doc.save(out_path)
    print(f"File saved successfully to {out_path}")

if __name__ == '__main__':
    create_document()
