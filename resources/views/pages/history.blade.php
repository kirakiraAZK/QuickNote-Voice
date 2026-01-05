@extends('layouts.master')

@section('title', 'Riwayat - QuickNote Voice')

@section('content')
    @include('layouts.navbar')

    <main class="main-content">
        <div class="container">
            <h1><i class="fas fa-history" style="color:#21808d;"></i> Riwayat Transkripsi</h1>

            <!-- Search -->
            <form action="{{ url('/history') }}" method="GET" style="margin-bottom: 25px; position: relative;">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama meeting..." style="width: 100%; padding: 12px 15px 12px 45px; border-radius: 12px; border: 1px solid #e0e0e0;">
                <button type="submit" style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); border:none; background:none; color:#aaa;"><i class="fas fa-search"></i></button>
            </form>

            <div class="history-list">
                @forelse($meetings as $meeting)
                    <div class="history-card">
                        <div class="history-icon"><i class="fas fa-file-audio"></i></div>
                        <div class="history-info">
                            <h3>{{ $meeting->title }}</h3>
                            <div class="history-meta">
                                <span><i class="far fa-clock"></i> {{ $meeting->created_at->format('d M Y, H:i') }}</span>
                                <span class="badge {{ str_contains(strtolower($meeting->language), 'id') ? 'badge-id' : 'badge-en' }}">{{ $meeting->language }}</span>
                            </div>
                            
                            <!-- AUDIO PLAYER & DOWNLOAD -->
                            @if($meeting->audio_path)
                                <div style="margin-bottom: 10px; display: flex; align-items: center; gap: 10px;">
                                    <audio controls preload="metadata" style="flex: 1; height: 35px; border-radius: 5px;">
                                        <source src="{{ asset('storage/' . $meeting->audio_path) }}" type="audio/webm">
                                        Browser tidak support audio.
                                    </audio>
                                    <!-- Tombol Download Audio -->
                                    <a href="{{ asset('storage/' . $meeting->audio_path) }}" download="recording-{{ $meeting->id }}.webm" class="btn-action-small" title="Download Audio" style="text-decoration: none; height: 35px; width: 35px;">
                                        <i class="fas fa-download"></i>
                                    </a>
                                </div>
                            @endif

                            <p class="history-snippet">
                                {{ \Illuminate\Support\Str::limit($meeting->summary ?: $meeting->transcript, 150) }}
                            </p>
                        </div>
                        <div class="history-actions">
                            <button class="btn-action-small" onclick="showDetail(this)" 
                                data-id="{{ $meeting->id }}"
                                data-title="{{ $meeting->title }}"
                                data-date="{{ $meeting->created_at->format('d M Y, H:i') }}"
                                data-lang="{{ $meeting->language }}"
                                data-summary="{{ $meeting->summary ?? '' }}"
                                data-transcript="{{ $meeting->transcript }}"
                                title="Lihat Detail"><i class="fas fa-eye"></i></button>
                            
                            <form action="{{ url('/meetings/' . $meeting->id) }}" method="POST" class="form-delete" onsubmit="return confirm('Hapus permanen?');">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-action-small delete" title="Hapus"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div style="text-align:center; padding:50px; color:#888;">Belum ada riwayat.</div>
                @endforelse
            </div>
        </div>

        <!-- MODAL DETAIL -->
        <div id="detailModal" class="modal-overlay">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 id="modalTitle">Judul</h2>
                    <button class="close-btn" onclick="closeModal()">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="modal-meta-info">
                        <span id="modalDate"></span> • <span id="modalLang"></span>
                    </div>
                    
                    <div class="modal-section">
                        <h4><i class="fas fa-sparkles" style="color: #f0ad4e;"></i> Ringkasan AI</h4>
                        <div id="modalSummary" class="modal-text-box ai-box"></div>
                        
                        <!-- AI GENERATOR PANEL -->
                        <div class="ai-generator-panel">
                            <div class="ai-generator-header" onclick="toggleAiPanel()">
                                <span><i class="fas fa-robot"></i> Buat Ringkasan Baru</span><i class="fas fa-chevron-down"></i>
                            </div>
                            <div id="aiPanelContent" class="ai-panel-content">
                                <input type="password" id="modalApiKey" class="form-control-sm" placeholder="API Key" style="margin-bottom:10px;">
                                <textarea id="modalContext" class="form-control-sm" rows="2" placeholder="Konteks..."></textarea>
                                <button class="btn-generate" onclick="generateHistorySummary()">Generate</button>
                                <div id="modalAiLoading" style="display:none; font-size:12px; margin-top:5px;">Loading...</div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-section">
                        <h4>Transkrip</h4>
                        <div id="modalTranscript" class="modal-text-box"></div>
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection

@push('styles')
<style>
    /* Gunakan style CSS yang sama seperti sebelumnya */
    .history-card { background: #fff; border: 1px solid #eee; border-radius: 16px; padding: 20px; margin-bottom: 15px; display: flex; gap: 15px; align-items: flex-start; }
    .history-icon { width: 50px; height: 50px; background: #e0f2f4; color: #21808d; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0; }
    .history-info { flex: 1; }
    .history-info h3 { margin: 0 0 8px 0; font-size: 16px; color: #333; }
    .history-meta { display: flex; gap: 12px; align-items: center; margin-bottom: 10px; font-size: 12px; color: #888; }
    .badge { padding: 3px 8px; border-radius: 6px; font-size: 11px; font-weight: 600; }
    .badge-id { background: #ffebee; color: #c62828; } .badge-en { background: #e8eaf6; color: #283593; }
    .history-actions { display: flex; flex-direction: column; gap: 8px; }
    .btn-action-small { background: #f8f9fa; border: 1px solid #eee; width: 36px; height: 36px; border-radius: 8px; color: #555; cursor: pointer; display: flex; align-items: center; justify-content: center; }
    .btn-action-small:hover { background: #21808d; color: white; border-color: #21808d; }
    .btn-action-small.delete:hover { background: #c0152f; color: white; border-color: #c0152f; }
    
    /* MODAL */
    .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; display: none; justify-content: center; align-items: center; padding: 20px; }
    .modal-overlay.show { display: flex; }
    .modal-content { background: white; width: 100%; max-width: 700px; max-height: 90vh; border-radius: 16px; display: flex; flex-direction: column; }
    .modal-header { padding: 15px 20px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; }
    .modal-body { padding: 20px; overflow-y: auto; }
    .modal-text-box { background: #f9f9f9; padding: 15px; border-radius: 10px; border: 1px solid #eee; font-size: 13px; line-height: 1.6; white-space: pre-wrap; margin-bottom: 20px; }
    .ai-box { background: #fdfdfd; border-color: #f0ad4e; border-style: dashed; }
    
    /* AI PANEL */
    .ai-generator-panel { margin-top: 10px; border: 1px solid #eee; border-radius: 8px; }
    .ai-generator-header { background: #f8f9fa; padding: 10px; font-size: 12px; font-weight: bold; cursor: pointer; display: flex; justify-content: space-between; }
    .ai-panel-content { padding: 15px; display: none; background: #fff; }
    .ai-panel-content.show { display: block; }
    .form-control-sm { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
    .btn-generate { width: 100%; background: #f0ad4e; color: white; border: none; padding: 8px; border-radius: 4px; cursor: pointer; margin-top: 10px; }
</style>
@endpush

@push('scripts')
<script>
    let currentMeetingData = {};

    function showDetail(button) {
        currentMeetingData = {
            id: button.getAttribute('data-id'),
            transcript: button.getAttribute('data-transcript'),
            summary: button.getAttribute('data-summary')
        };
        document.getElementById('modalTitle').textContent = button.getAttribute('data-title');
        document.getElementById('modalDate').textContent = button.getAttribute('data-date');
        document.getElementById('modalLang').textContent = button.getAttribute('data-lang');
        document.getElementById('modalTranscript').textContent = currentMeetingData.transcript;
        
        const summaryBox = document.getElementById('modalSummary');
        summaryBox.innerHTML = currentMeetingData.summary 
            ? currentMeetingData.summary.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>').replace(/\n/g, '<br>')
            : '<em style="color:#999">Belum ada ringkasan.</em>';

        document.getElementById('aiPanelContent').classList.remove('show');
        const modal = document.getElementById('detailModal');
        modal.style.display = 'flex';
        setTimeout(() => modal.classList.add('show'), 10);
    }

    function closeModal() {
        const modal = document.getElementById('detailModal');
        modal.classList.remove('show');
        setTimeout(() => modal.style.display = 'none', 300);
    }

    function toggleAiPanel() {
        document.getElementById('aiPanelContent').classList.toggle('show');
    }

    async function generateHistorySummary() {
        const apiKey = document.getElementById('modalApiKey').value.trim();
        const context = document.getElementById('modalContext').value.trim();
        if(!apiKey) { alert("Isi API Key!"); return; }
        
        document.getElementById('modalAiLoading').style.display = 'block';
        
        try {
            const res = await fetch(`https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-preview-09-2025:generateContent?key=${apiKey}`, {
                method: 'POST', headers: {'Content-Type':'application/json'},
                body: JSON.stringify({ contents: [{ parts: [{ text: `Ringkas transkrip ini (Bahasa Indonesia). Konteks: ${context}. Transkrip: ${currentMeetingData.transcript}` }] }] })
            });
            const data = await res.json();
            const result = data.candidates[0].content.parts[0].text;
            document.getElementById('modalSummary').innerHTML = result.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>').replace(/\n/g, '<br>');
            toggleAiPanel();
        } catch(e) {
            alert("Gagal: " + e.message);
        } finally {
            document.getElementById('modalAiLoading').style.display = 'none';
        }
    }

    window.onclick = function(e) { if(e.target == document.getElementById('detailModal')) closeModal(); }
</script>
@endpush