<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<style>
@page{margin:9mm 14mm 17mm}*{box-sizing:border-box}body{margin:0;color:#153e34;font-family:DejaVu Sans,sans-serif;font-size:9px}.header{text-align:center}.logo{display:block;width:185px;max-height:55px;object-fit:contain;margin:0 auto 3px}.institution{font-size:13px;line-height:1.35;font-weight:bold;color:#07583f}.rule{height:3px;margin:8px 0 7px;border-top:2px solid #07583f;border-bottom:1px solid #d7ad27}.title{font-size:12px;line-height:1.35;font-weight:bold;letter-spacing:.2px;color:#063d30;text-align:center;white-space:nowrap}table{width:100%;border-collapse:collapse;table-layout:fixed}.date-table td{height:auto;padding:5px 0 0;border:0;text-align:right;font-size:9px;line-height:1.45;white-space:nowrap}.date-table b{margin-left:5px;color:#07583f}thead{display:table-header-group}tr{page-break-inside:avoid}th{padding:8px 5px;border:1px solid #07583f;background:#07583f;color:#fff;font-size:8px;text-align:center}.meta-row th{padding:8px 0;border:0;background:transparent;color:#5e756f;text-align:left;font-size:9px;font-weight:normal}.meta-row strong{color:#07583f}td{height:35px;padding:6px 5px;border:1px solid #b6cac4;vertical-align:middle}.no{width:28px;text-align:center}.registration{width:115px}.phone{width:92px}.signature{width:115px}.footer{position:fixed;bottom:-10mm;left:0;right:0;padding-top:5px;border-top:1px solid #cbd9d5;color:#6a7f79;font-size:7px}.footer-left{float:left}.footer-right{float:right}.page-number:after{content:counter(page)}
</style>
</head>
<body>
<div class="header">
  @if($logoDataUri)<img class="logo" src="{{ $logoDataUri }}" alt="Logo PKU">@endif
  <div class="institution">Pendidikan Kader Ulama<br>MUI Provinsi DKI Jakarta</div>
  <div class="rule"></div>
  <div class="title">ABSENSI CALON MAHASISWA PKU {{ $registrationYear }}</div>
</div>
<table class="date-table"><tr><td>Tanggal Tes:<b>{{ $testDate }}</b></td></tr></table>
<table>
  <thead><tr class="meta-row"><th colspan="5">Jumlah peserta: <strong>{{ $participants->count() }} orang</strong></th></tr><tr><th class="no">No.</th><th class="registration">No. Registrasi</th><th>Nama Peserta</th><th class="phone">No. HP</th><th class="signature">Tanda Tangan</th></tr></thead>
  <tbody>
    @forelse($participants as $index => $participant)
      <tr><td class="no">{{ $index + 1 }}</td><td>{{ $participant['registration_number'] }}</td><td>{{ $participant['full_name'] }}</td><td>{{ $participant['whatsapp'] }}</td><td></td></tr>
    @empty
      <tr><td colspan="5" style="height:55px;text-align:center;color:#71857f">Belum ada peserta yang dijadwalkan.</td></tr>
    @endforelse
  </tbody>
</table>
<footer class="footer"><span class="footer-left">PMB Pendidikan Kader Ulama MUI Provinsi DKI Jakarta</span><span class="footer-right">Dicetak: {{ now()->format('d/m/Y H:i') }} WIB | Halaman <span class="page-number"></span></span></footer>
</body>
</html>
