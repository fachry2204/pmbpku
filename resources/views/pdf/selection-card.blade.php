<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Kartu Peserta Seleksi {{ $applicant->registration_number }}</title>
    <style>
        @page { size: 105mm 80mm; margin: 0; }
        * { box-sizing: border-box; }
        html, body { width: 105mm; height: 80mm; }
        body { margin: 0; color: #12372f; font-family: DejaVu Sans, sans-serif; background: #f7f5ec; }
        .card { position: relative; width: 105mm; height: 80mm; padding: 11px 13px; overflow: hidden; border: 3px solid #064e3b; background: #fffdf8; }
        .pattern { position: absolute; right: -58px; top: -65px; width: 230px; height: 230px; border: 2px solid #d4af37; border-radius: 50%; opacity: .3; }
        .pattern.two { right: -20px; top: -25px; width: 150px; height: 150px; }
        .header { height: 42px; padding-bottom: 6px; border-bottom: 2px solid #d4af37; }
        .logo { width: 92px; height: 28px; object-fit: contain; object-position: left center; }
        .heading { position: absolute; left: 116px; top: 13px; right: 10px; }
        .eyebrow { margin: 0 0 4px; color: #b18412; font-size: 9px; font-weight: bold; letter-spacing: 2px; }
        h1 { margin: 0; color: #064e3b; font-size: 12px; }
        .subtitle { margin: 5px 0 0; color: #08745a; font-size: 8px; font-weight: bold; }
        .content { margin-top: 9px; }
        .photo-wrap { float: left; width: 58px; height: 76px; margin-right: 9px; padding: 2px; border: 1px solid #d4af37; border-radius: 5px; background: white; }
        .photo { display: block; width: 100%; height: 100%; object-fit: cover; object-position: center; border-radius: 5px; }
        .photo-empty { width: 100%; height: 100%; padding-top: 53px; text-align: center; color: #78928c; font-size: 10px; background: #edf7f3; }
        .identity { float: left; width: 128px; }
        .registration { margin-bottom: 5px; padding: 4px 6px; border-left: 2px solid #d4af37; border-radius: 3px; background: #edf7f3; }
        .label { display: block; margin-bottom: 3px; color: #6b827d; font-size: 7px; font-weight: bold; letter-spacing: 1px; text-transform: uppercase; }
        .registration strong { color: #064e3b; font-size: 9px; letter-spacing: .2px; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 2px 0; vertical-align: top; font-size: 6px; }
        td:first-child { width: 52px; color: #6b827d; }
        td:last-child { font-weight: bold; }
        .schedule { float: right; width: 94px; min-height: 76px; padding: 7px; border: 1px solid #b7d7cb; border-radius: 5px; background: #f4faf7; }
        .schedule h2 { margin: 0 0 5px; color: #064e3b; font-size: 8px; }
        .schedule-item { margin-bottom: 4px; }
        .schedule-item b { display: block; font-size: 6px; line-height: 1.2; }
        .attendance-qr { margin-top: 4px; padding-top: 4px; border-top: 1px solid #cde3da; }
        .attendance-qr img { display: block; float: left; width: 31px; height: 31px; margin-right: 4px; }
        .attendance-qr p { margin: 2px 0 0; color: #55706a; font-size: 5px; line-height: 1.2; }
        .clear { clear: both; }
        .notice { margin-top: 8px; padding: 5px 7px; border: 1px solid #e6c45d; border-radius: 4px; background: #fff8dd; color: #785b09; font-size: 6px; font-weight: bold; text-align: center; }
        .footer { position: absolute; left: 14px; right: 14px; bottom: 8px; color: #6b827d; font-size: 5px; }
        .footer span { float: right; }
    </style>
</head>
<body>
<section class="card">
    <div class="pattern"></div><div class="pattern two"></div>
    <header class="header">
        @if($logoDataUri)<img class="logo" src="{{ $logoDataUri }}" alt="Logo PKU">@endif
        <div class="heading"><p class="eyebrow">DOKUMEN RESMI PMB</p><h1>KARTU PESERTA SELEKSI</h1><p class="subtitle">Pendidikan Kader Ulama MUI Provinsi DKI Jakarta</p></div>
    </header>
    <div class="content">
        <div class="photo-wrap">@if($photoDataUri)<img class="photo" src="{{ $photoDataUri }}" alt="Pas foto">@else<div class="photo-empty">PAS FOTO<br>TIDAK TERSEDIA</div>@endif</div>
        <div class="identity">
            <div class="registration"><span class="label">Nomor Registrasi</span><strong>{{ $applicant->registration_number }}</strong></div>
            <table>
                <tr><td>Nama lengkap</td><td>{{ $applicant->full_name }}</td></tr>
                <tr><td>Tempat, tanggal lahir</td><td>{{ $applicant->birth_place }}, {{ $applicant->birth_date?->format('d/m/Y') }}</td></tr>
                <tr><td>Nomor WhatsApp</td><td>{{ $applicant->whatsapp_display }}</td></tr>
                <tr><td>Email</td><td>{{ $applicant->email }}</td></tr>
            </table>
        </div>
        <aside class="schedule">
            <h2>Jadwal Seleksi</h2>
            <div class="schedule-item"><span class="label">Tanggal</span><b>{{ $session->starts_at->locale('id')->translatedFormat('l, d F Y') }}</b></div>
            <div class="schedule-item"><span class="label">Waktu</span><b>{{ $session->starts_at->format('H:i') }} WIB</b></div>
            <div class="schedule-item"><span class="label">Lokasi</span><b>{{ $session->location ?: 'Lokasi akan diinformasikan oleh panitia' }}</b></div>
            <div class="attendance-qr"><img src="{{ $attendanceQrDataUri }}" alt="QR Absensi"><p><b>QR ABSENSI</b><br>Dipindai petugas saat peserta hadir.</p><div class="clear"></div></div>
        </aside>
        <div class="clear"></div>
        <div class="notice">Kartu ini wajib dicetak dan dibawa saat mengikuti seleksi bersama identitas diri asli.</div>
    </div>
    <footer class="footer">Kontak Panitia: {{ $contactPhone }} <span>Dicetak: {{ now()->format('d/m/Y H:i') }} WIB</span></footer>
</section>
</body>
</html>
