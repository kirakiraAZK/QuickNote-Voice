@extends('layouts.master')

@section('title', 'Riwayat - QuickNote Voice')

@section('content')
    <!-- Include Navbar -->
    @include('layouts.navbar')

    <main class="main-content">
        <div class="container">
            <h1><i class="fas fa-history" style="color:#21808d;"></i> Riwayat Transkripsi</h1>

            <!-- Search Filter -->
            <form action="{{ url('/history') }}" method="GET" style="margin-bottom: 25px; position: relative;">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama meeting..." 
                       style="width: 100%; padding: 12px 15px 12px 45px; border-radius: 12px; border: 1px solid #e0e0e0; font-size: 14px;">
                <button type="submit" style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); background:none; border:none; color: #aaa; cursor: pointer;">
                    <i class="fas fa-search"></i>
                </button>
            </form>

            <!-- History List -->
            <div class="history-list">
                
                @forelse($meetings as $meeting)
                    <div class="history-card">
                        <div class="history-icon">
                            <i class="fas fa-file-audio"></i>
                        </div>
                        <div class="history-info">
                            <h3>{{ $meeting->title }}</h3>
                            <div class="history-meta">
                                <span><i class="far fa-clock"></i> {{ $meeting->created_at->format('d M Y, H:i') }}</span>
                                
                                @if(str_contains(strtolower($meeting->language), 'indonesia') || str_contains(strtolower($meeting->language), 'id'))
                                    <span class="badge badge-id">{{ $meeting->language }}</span>
                                @else
                                    <span class="badge badge-en">{{ $meeting->language }}</span>
                                @endif
                            </div>
                            
                            <p class="history-snippet">
                                @if($meeting->summary)
                                    <strong>Ringkasan:</strong> {{ \Illuminate\Support\Str::limit($meeting->summary, 150) }}
                                @else
                                    {{ \Illuminate\Support\Str::limit($meeting->transcript, 150) }}
                                @endif
                            </p>
                        </div>
                        <div class="history-actions">
                            <!-- Tombol Lihat Detail -->
                            <button class="btn-action-small" 
                                    onclick="showDetail(this)"
                                    data-id="{{ $meeting->id }}"
                                    data-title="{{ $meeting->title }}"
                                    data-date="{{ $meeting->created_at->format('d M Y, H:i') }}"
                                    data-lang="{{ $meeting->language }}"
                                    data-summary="{{ $meeting->summary ?? '' }}"
                                    data-transcript="{{ $meeting->transcript }}"
                                    title="Lihat Detail">
                                <i class="fas fa-eye"></i>
                            </button>

                            <!-- Tombol Hapus -->
                            <form action="{{ url('/meetings/' . $meeting->id) }}" method="POST" class="form-delete" onsubmit="return confirm('Hapus permanen?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-action-small delete" title="Hapus">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div style="text-align: center; padding: 50px 20px; color: #888;">
                        <i class="fas fa-microphone-slash" style="font-size: 48px; margin-bottom: 15px; opacity: 0.3;"></i>
                        <p style="font-size: 16px;">Belum ada riwayat meeting.</p>
                        <a href="{{ url('/quicknote') }}" style="display:inline-block; margin-top:10px; color: #21808d; font-weight: 600; text-decoration: none;">
                            <i class="fas fa-plus"></i> Mulai Rekaman Baru
                        </a>
                    </div>
                @endforelse

            </div>
        </div>

        <!-- MODAL DETAIL -->
        <div id="detailModal" class="modal-overlay">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 id="modalTitle">Judul Meeting</h2>
                    <button class="close-btn" onclick="closeModal()">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="modal-meta-info">
                        <span id="modalDate"><i class="far fa-clock"></i> -</span>
                        <span id="modalLang" class="badge badge-en" style="margin-left: 10px;">-</span>
                    </div>
                    
                    <!-- AREA RINGKASAN -->
                    <div class="modal-section">
                        <h4><i class="fas fa-sparkles" style="color: #f0ad4e;"></i> Ringkasan AI</h4>
                        
                        <!-- Tempat Hasil Summary Muncul -->
                        <div id="modalSummary" class="modal-text-box ai-box">
                            <em style="color:#999">Belum ada ringkasan. Gunakan tools di bawah untuk membuat.</em>
                        </div>

                        <!-- PANEL GENERATOR AI BARU DI SINI -->
                        <div class="ai-generator-panel">
                            <div class="ai-generator-header" onclick="toggleAiPanel()">
                                <span><i class="fas fa-robot"></i> Buat / Perbaiki Ringkasan</span>
                                <i class="fas fa-chevron-down"></i>
                            </div>
                            
                            <div id="aiPanelContent" class="ai-panel-content">
                                <!-- Input API Key -->
                                <div style="margin-bottom: 10px;">
                                    <label style="font-size:12px; font-weight:600;">Gemini API Key</label>
                                    <input type="password" id="modalApiKey" class="form-control-sm" placeholder="Paste API Key di sini...">
                                </div>

                                <!-- Input Context -->
                                <div style="margin-bottom: 10px;">
                                    <label style="font-size:12px; font-weight:600;">Konteks (Opsional)</label>
                                    <textarea id="modalContext" class="form-control-sm" rows="2" placeholder="Contoh: Pembicara Pak Andi & Bu Siti. Topik tentang Budget 2025."></textarea>
                                </div>

                                <button class="btn-generate" onclick="generateHistorySummary()">
                                    <i class="fas fa-magic"></i> Generate Ringkasan
                                </button>
                                <div id="modalAiLoading" style="display:none; font-size:12px; color:#666; margin-top:5px;">
                                    <i class="fas fa-spinner fa-spin"></i> Sedang memproses...
                                </div>
                            </div>
                        </div>
                        <!-- END PANEL -->
                    </div>

                    <!-- AREA TRANSKRIP -->
                    <div class="modal-section">
                        <h4><i class="fas fa-align-left" style="color: #21808d;"></i> Transkrip Lengkap</h4>
                        <div id="modalTranscript" class="modal-text-box">Loading...</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn-close" onclick="closeModal()">Tutup</button>
                </div>
            </div>
        </div>

    </main>
@endsection

@push('styles')
<style>
    /* ... (Style history card lama tetap sama) ... */
    .history-card { background: #fff; border: 1px solid #eee; border-radius: 16px; padding: 20px; margin-bottom: 15px; display: flex; gap: 15px; align-items: flex-start; transition: all 0.2s ease; }
    .history-card:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,0,0,0.06); border-color: #bce0e5; }
    .history-icon { width: 50px; height: 50px; background: #e0f2f4; color: #21808d; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0; }
    .history-info { flex: 1; }
    .history-info h3 { margin: 0 0 8px 0; font-size: 16px; font-weight: 700; color: #333; }
    .history-meta { display: flex; gap: 12px; align-items: center; margin-bottom: 10px; font-size: 12px; color: #888; flex-wrap: wrap; }
    .badge { padding: 3px 8px; border-radius: 6px; font-size: 11px; font-weight: 600; }
    .badge-id { background: #ffebee; color: #c62828; }
    .badge-en { background: #e8eaf6; color: #283593; }
    .history-snippet { font-size: 13px; color: #555; margin: 0; line-height: 1.5; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    .history-actions { display: flex; flex-direction: column; gap: 8px; }
    .form-delete { display: contents; }
    .btn-action-small { background: #f8f9fa; border: 1px solid #eee; width: 36px; height: 36px; border-radius: 8px; color: #555; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s; }
    .btn-action-small:hover { background: #21808d; color: white; border-color: #21808d; }
    .btn-action-small.delete:hover { background: #c0152f; color: white; border-color: #c0152f; }

    /* MODAL STYLES */
    .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; display: none; justify-content: center; align-items: center; padding: 20px; opacity: 0; transition: opacity 0.3s ease; }
    .modal-overlay.show { display: flex; opacity: 1; }
    .modal-content { background: white; width: 100%; max-width: 700px; max-height: 90vh; border-radius: 16px; box-shadow: 0 10px 25px rgba(0,0,0,0.2); display: flex; flex-direction: column; transform: translateY(20px); transition: transform 0.3s ease; }
    .modal-overlay.show .modal-content { transform: translateY(0); }
    .modal-header { padding: 20px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; }
    .modal-header h2 { margin: 0; font-size: 18px; color: #333; }
    .close-btn { background: none; border: none; font-size: 24px; cursor: pointer; color: #888; }
    .modal-body { padding: 20px; overflow-y: auto; }
    .modal-meta-info { margin-bottom: 20px; font-size: 13px; color: #666; }
    .modal-section { margin-bottom: 25px; }
    .modal-section h4 { margin: 0 0 10px 0; font-size: 14px; font-weight: 600; color: #444; }
    .modal-text-box { background: #f9f9f9; padding: 15px; border-radius: 10px; border: 1px solid #eee; font-size: 14px; line-height: 1.6; color: #333; white-space: pre-wrap; }
    .ai-box { background: #fdfdfd; border-color: #f0ad4e; border-style: dashed; }
    .modal-footer { padding: 15px 20px; border-top: 1px solid #eee; text-align: right; }
    .btn-close { background: #eee; color: #555; border: none; padding: 8px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; }

    /* AI GENERATOR PANEL STYLES */
    .ai-generator-panel { margin-top: 15px; border: 1px solid #eee; border-radius: 8px; overflow: hidden; }
    .ai-generator-header { background: #f8f9fa; padding: 10px 15px; font-size: 13px; font-weight: 600; cursor: pointer; display: flex; justify-content: space-between; align-items: center; color: #555; }
    .ai-generator-header:hover { background: #f0f0f0; }
    .ai-panel-content { padding: 15px; display: none; /* Collapsed by default */ border-top: 1px solid #eee; background: #fff; }
    .ai-panel-content.show { display: block; }
    
    .form-control-sm { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 6px; font-size: 13px; box-sizing: border-box; }
    .form-control-sm:focus { border-color: #21808d; outline: none; }
    
    .btn-generate { background: linear-gradient(135deg, #f0ad4e, #ec971f); color: white; border: none; padding: 8px 15px; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer; width: 100%; transition: opacity 0.2s; }
    .btn-generate:hover { opacity: 0.9; }

    @media (max-width: 600px) {
        .history-card { flex-direction: column; }
        .history-icon { width: 40px; height: 40px; font-size: 16px; }
        .history-actions { flex-direction: row; width: 100%; justify-content: flex-end; margin-top: 10px; border-top: 1px dashed #eee; padding-top: 10px; }
        .form-delete { display: inline-block; }
    }
</style>
@endpush

@push('scripts')
<script>
    // Variabel global untuk menyimpan data meeting yang sedang dibuka
    let currentMeetingData = {};

    function showDetail(button) {
        // Ambil data dari atribut tombol
        currentMeetingData = {
            id: button.getAttribute('data-id'),
            transcript: button.getAttribute('data-transcript'),
            summary: button.getAttribute('data-summary')
        };

        // Isi konten modal
        document.getElementById('modalTitle').textContent = button.getAttribute('data-title');
        document.getElementById('modalDate').innerHTML = `<i class="far fa-clock"></i> ${button.getAttribute('data-date')}`;
        document.getElementById('modalLang').textContent = button.getAttribute('data-lang');
        document.getElementById('modalTranscript').textContent = currentMeetingData.transcript;

        // Logika tampilan Summary
        const summaryBox = document.getElementById('modalSummary');
        if (currentMeetingData.summary && currentMeetingData.summary !== "") {
            summaryBox.innerHTML = currentMeetingData.summary.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>').replace(/\n/g, '<br>');
        } else {
            summaryBox.innerHTML = '<em style="color:#999">Belum ada ringkasan. Gunakan tools di bawah untuk membuat.</em>';
        }

        // Reset Panel AI
        document.getElementById('aiPanelContent').classList.remove('show');
        document.getElementById('modalApiKey').value = ''; 
        document.getElementById('modalContext').value = '';

        // Tampilkan modal
        const modal = document.getElementById('detailModal');
        modal.style.display = 'flex';
        setTimeout(() => { modal.classList.add('show'); }, 10);
    }

    function closeModal() {
        const modal = document.getElementById('detailModal');
        modal.classList.remove('show');
        setTimeout(() => { modal.style.display = 'none'; }, 300);
    }

    // Toggle Accordion Panel AI
    function toggleAiPanel() {
        const panel = document.getElementById('aiPanelContent');
        panel.classList.toggle('show');
    }

    // === LOGIKA GENERATE SUMMARY DI MODAL HISTORY ===
    async function generateHistorySummary() {
        const apiKey = document.getElementById('modalApiKey').value.trim();
        const context = document.getElementById('modalContext').value.trim();
        const transcript = currentMeetingData.transcript;

        if (!apiKey) {
            alert("⚠️ Harap masukkan Gemini API Key terlebih dahulu.");
            return;
        }

        if (!transcript || transcript.length < 10) {
            alert("Transkrip terlalu pendek untuk diringkas.");
            return;
        }

        // UI Loading
        const btnGen = document.querySelector('.btn-generate');
        const loading = document.getElementById('modalAiLoading');
        btnGen.disabled = true;
        btnGen.style.opacity = 0.6;
        loading.style.display = 'block';

        // Prompt Engineering (Sama seperti Main Page)
        const prompt = `
            Bertindaklah sebagai asisten notulen rapat yang cerdas.
            Saya akan memberikan [TRANSKRIP MENTAH].
            Saya juga memberikan [KONTEKS] berisi nama pembicara atau istilah yang benar.

            Tugas Anda:
            1. Analisis transkrip mentah.
            2. Gunakan informasi pada [KONTEKS] untuk memperbaiki kesalahan ejaan nama atau istilah teknis pada transkrip.
            3. Buatkan ringkasan poin-poin penting (bullet points) yang akurat dalam Bahasa Indonesia.

            [KONTEKS]:
            "${context ? context : 'Tidak ada konteks tambahan.'}"

            [TRANSKRIP MENTAH]:
            "${transcript}"
        `;

        try {
            const response = await fetch(`https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-preview-09-2025:generateContent?key=${apiKey}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ contents: [{ parts: [{ text: prompt }] }] })
            });

            if (!response.ok) {
                const errData = await response.json().catch(() => ({}));
                throw new Error(errData.error?.message || 'Unknown error');
            }

            const data = await response.json();
            const rawResult = data.candidates[0].content.parts[0].text;

            // Format dan Tampilkan Hasil
            const formattedResult = rawResult
                .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                .replace(/\n/g, '<br>');

            document.getElementById('modalSummary').innerHTML = formattedResult;
            
            // Tutup panel form setelah sukses
            toggleAiPanel(); 

            // (Opsional) Di sini Anda bisa menambahkan AJAX lagi untuk UPDATE database dengan summary baru
            // Agar jika dibuka nanti, summary-nya sudah tersimpan.

        } catch (error) {
            console.error(error);
            alert(`Gagal memproses AI: ${error.message}`);
        } finally {
            btnGen.disabled = false;
            btnGen.style.opacity = 1;
            loading.style.display = 'none';
        }
    }

    window.onclick = function(event) {
        if (event.target == document.getElementById('detailModal')) closeModal();
    }
</script>
@endpush