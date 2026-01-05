@extends('layouts.master')

@section('title', 'QuickNote Voice + Gemini AI')

@section('content')

<!-- Include Navbar -->
@include('layouts.navbar')

<main class="main-content">
    <div class="container">
        <h1><i class="fas fa-microphone-lines" style="color:#21808d;"></i> QuickNote Voice</h1>

        <!-- INPUT FORM UTAMA -->
        <div style="margin-bottom: 20px;">
            <label for="meeting-name">Nama Meeting</label>
            <input type="text" id="meeting-name" placeholder="Contoh: Interview Kandidat Backend Developer" />
        </div>

        <div style="display: flex; gap: 20px; flex-wrap: wrap; margin-bottom: 20px;">
            <div style="flex: 1; min-width: 150px;">
                <label for="language">Pilih Bahasa</label>
                <select id="language">
                    <option value="id-ID">Bahasa Indonesia</option>
                    <option value="en-US">English (US)</option>
                    <option value="en-GB">English (UK)</option>
                    <option value="es-ES">Español</option>
                    <option value="fr-FR">Français</option>
                    <option value="de-DE">Deutsch</option>
                    <option value="ja-JP">日本語</option>
                    <option value="zh-CN">中文 (简体)</option>
                </select>
            </div>

            <div style="flex: 1; min-width: 150px;">
                <label for="mic-select">Pilih Mikrofon</label>
                <select id="mic-select">
                    <option value="default">Default System</option>
                </select>
                <div style="font-size: 11px; color: #888; margin-top: 4px;">*Refresh jika mic tidak muncul.</div>
            </div>
        </div>
        
        <!-- UI PLAYER CONTROL -->
        <div class="player-wrapper">
            <div class="controls">
                <button id="btn-stop" class="btn-player btn-stop" disabled title="Stop Recording"><i class="fas fa-stop"></i></button>
                <button id="btn-pause" class="btn-player btn-pause" disabled title="Pause"><i class="fas fa-pause"></i></button>
                <button id="btn-start" class="btn-player btn-start" title="Mulai Rekam"><i class="fas fa-microphone"></i></button>
                <button id="btn-resume" class="btn-player btn-resume" disabled title="Resume"><i class="fas fa-play"></i></button>
            </div>
            <div class="status-display">
                <span id="status-icon">⚪</span> <span id="status">Ready to Record</span>
                <span id="timer" style="margin-left:10px; font-family: monospace;">00:00</span>
            </div>

            <!-- AUDIO PREVIEW PLAYER (Muncul setelah stop) -->
            <div id="audio-preview-container" style="display:none; margin-top: 20px; width: 100%; text-align: center;">
                <label style="font-size: 12px; display: block; margin-bottom: 5px;">Review Rekaman Suara:</label>
                <audio id="audio-playback" controls style="width: 100%; max-width: 400px; height: 35px;"></audio>
            </div>
        </div>

        <label>Hasil Transkripsi (Mentah)</label>
        <div id="metadata-display" class="metadata-box"></div>
        <div class="interim-preview" id="interim-preview">
            <i class="fas fa-wave-square" style="margin-right:8px; opacity:0.5;"></i> Menunggu suara...
        </div>
        <textarea id="transcript" placeholder="Teks dari suara akan muncul di sini..."></textarea>
        
        <!-- KONTEKS INPUT -->
        <div style="margin-top: 20px; background: #fff8e1; padding: 15px; border-radius: 12px; border: 1px solid #ffe082;">
            <label for="context-input" style="color: #f57f17;"><i class="fas fa-lightbulb"></i> Konteks & Koreksi (Penting untuk AI)</label>
            <textarea id="context-input" rows="2" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #ddd; font-size: 13px;" 
            placeholder="Contoh: Pembicara adalah Pak Budi dan Bu Sari. Topik tentang budget Q4."></textarea>
        </div>

        <!-- AI SMART ACTIONS & API KEY -->
        <div class="ai-section">
            <div class="ai-title"><i class="fas fa-sparkles" style="color: #f0ad4e;"></i> AI Smart Actions (Powered by Gemini)</div>
            
            <!-- API KEY DIPINDAH KE SINI -->
            <div class="api-key-container" style="margin-bottom: 20px; background: #f8f9fa; padding: 15px; border-radius: 8px; border: 1px dashed #ccc;">
                <label for="api-key" style="font-size: 12px; color: #666;"><i class="fas fa-key"></i> Gemini API Key</label>
                <input type="password" id="api-key" placeholder="Paste API Key di sini..." style="margin-bottom: 5px;" />
                <div class="api-key-helper" style="font-size: 11px; color: #888;">
                    Key hanya disimpan di browser Anda. <a href="https://aistudio.google.com/app/apikey" target="_blank" style="color: #21808d; text-decoration: underline;">Dapatkan API Key di sini</a>.
                </div>
            </div>

            <div class="ai-buttons-container">
                <button class="btn-ai" onclick="askGemini('summary')">✨ Perbaiki & Ringkas Meeting</button>
            </div>
            
            <div id="ai-loading" style="display:none; color: #666; font-size: 13px; margin-bottom: 10px;">
                <span class="spinner"></span> Sedang memproses dengan Gemini AI...
            </div>

            <div id="ai-result" class="ai-output-box">
                <h3 id="ai-result-title">Hasil AI</h3>
                <div id="ai-result-content" class="ai-output-content"></div>
            </div>
        </div>

        <div class="controls-save">
            <button id="btn-save" class="btn-action btn-save" disabled><i class="fas fa-save"></i> Simpan</button>
            <button id="btn-clear" class="btn-action btn-clear"><i class="fas fa-trash"></i> Hapus</button>
        </div>
    </div>
</main>
@endsection

@push('scripts')
<script>
    const meetingInput = document.getElementById('meeting-name');
    const languageSelect = document.getElementById('language');
    const micSelect = document.getElementById('mic-select');
    const btnStart = document.getElementById('btn-start');
    const btnPause = document.getElementById('btn-pause');
    const btnResume = document.getElementById('btn-resume');
    const btnStop = document.getElementById('btn-stop');
    const btnSave = document.getElementById('btn-save');
    const btnClear = document.getElementById('btn-clear');
    const statusEl = document.getElementById('status');
    const statusIcon = document.getElementById('status-icon');
    const transcriptEl = document.getElementById('transcript');
    const contextInput = document.getElementById('context-input');
    const timerEl = document.getElementById('timer');
    const metadataEl = document.getElementById('metadata-display');
    const apiKeyInput = document.getElementById('api-key');
    const audioPreviewContainer = document.getElementById('audio-preview-container');
    const audioPlayback = document.getElementById('audio-playback');

    let latestAiSummary = ''; 
    let mediaRecorder = null;
    let audioChunks = [];
    let audioBlob = null;

    // --- MIC DETECTION ---
    async function loadMicrophones() {
        try {
            await navigator.mediaDevices.getUserMedia({ audio: true });
            const devices = await navigator.mediaDevices.enumerateDevices();
            const audioInputs = devices.filter(device => device.kind === 'audioinput');
            micSelect.innerHTML = '';
            audioInputs.forEach(device => {
                const option = document.createElement('option');
                option.value = device.deviceId;
                option.text = device.label || `Mikrofon ${micSelect.length + 1}`;
                micSelect.appendChild(option);
            });
        } catch (err) {
            console.error("Mic error:", err);
            micSelect.innerHTML = '<option value="default">Default Device</option>';
        }
    }
    window.addEventListener('load', loadMicrophones);
    navigator.mediaDevices.ondevicechange = loadMicrophones;

    // --- GEMINI AI ---
    async function askGemini(type) {
        const apiKey = apiKeyInput.value.trim();
        if (!apiKey) { alert("⚠️ API Key belum diisi!"); apiKeyInput.focus(); return; }
        const text = transcriptEl.value.trim();
        const context = contextInput.value.trim();
        if (!text || text.length < 10) { alert("Transkrip terlalu pendek."); return; }

        document.getElementById('ai-loading').style.display = 'block';
        document.getElementById('ai-result').classList.remove('active');
        
        let prompt = "";
        if (type === 'summary') {
            prompt = `Bertindaklah sebagai asisten notulen rapat. Analisis [TRANSKRIP MENTAH] berikut. Gunakan [KONTEKS] (jika ada) untuk memperbaiki kesalahan ejaan nama/istilah. Buatkan ringkasan poin penting dalam Bahasa Indonesia.\n\n[KONTEKS]: "${context}"\n\n[TRANSKRIP MENTAH]: "${text}"`;
        } 

        try {
            const response = await fetch(`https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-preview-09-2025:generateContent?key=${apiKey}`, {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ contents: [{ parts: [{ text: prompt }] }] })
            });
            if (!response.ok) throw new Error('API Error');
            const data = await response.json();
            const rawResult = data.candidates[0].content.parts[0].text;
            latestAiSummary = rawResult;
            document.getElementById('ai-result-title').textContent = "✨ Ringkasan (Dikoreksi Konteks)";
            document.getElementById('ai-result-content').innerHTML = rawResult.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>').replace(/\n/g, '<br>');
            document.getElementById('ai-result').classList.add('active');
        } catch (error) {
            alert(`Gagal memproses AI: ${error.message}`);
        } finally {
            document.getElementById('ai-loading').style.display = 'none';
        }
    }

    // --- WEB SPEECH ---
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    let recognition = null;
    let isRecording = false;
    let isPaused = false;
    let silenceTimeout = null;
    let recordingStartTime = null;
    let timerInterval = null;
    const SILENCE_DURATION = 8000; 

    if (SpeechRecognition) {
        recognition = new SpeechRecognition();
        recognition.lang = 'id-ID';
        recognition.continuous = true;
        recognition.interimResults = true;
        recognition.onresult = (event) => {
            resetSilenceTimer();
            let final = '', interim = '';
            for (let i = event.resultIndex; i < event.results.length; i++) {
                if (event.results[i].isFinal) final += event.results[i][0].transcript + ' ';
                else interim += event.results[i][0].transcript;
            }
            if (interim.trim()) document.getElementById('interim-preview').innerText = '🎤 ' + interim;
            if (final.trim()) {
                transcriptEl.value += final;
                transcriptEl.scrollTop = transcriptEl.scrollHeight;
                btnSave.disabled = false;
            }
        };
        recognition.onend = () => { if (isRecording && !isPaused) setTimeout(() => recognition.start(), 200); };
    } else {
        alert("Browser tidak support Web Speech API");
        btnStart.disabled = true;
    }

    languageSelect.addEventListener('change', () => { if (recognition) recognition.lang = languageSelect.value; });

    // --- AUDIO RECORDER & PLAYER LOGIC ---
    async function startAudioRecording() {
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ audio: { deviceId: micSelect.value !== 'default' ? { exact: micSelect.value } : undefined } });
            mediaRecorder = new MediaRecorder(stream);
            audioChunks = [];
            mediaRecorder.ondataavailable = e => audioChunks.push(e.data);
            mediaRecorder.onstop = () => {
                audioBlob = new Blob(audioChunks, { type: 'audio/webm' });
                // Setup Player Preview
                const audioUrl = URL.createObjectURL(audioBlob);
                audioPlayback.src = audioUrl;
            };
            mediaRecorder.start();
        } catch (e) { console.error("Mic error", e); }
    }

    // --- CONTROLS ---
    function updateTimer() {
        if (!recordingStartTime || isPaused) return;
        const diff = Math.floor((Date.now() - recordingStartTime) / 1000);
        const mins = String(Math.floor(diff / 60)).padStart(2, '0');
        const secs = String(diff % 60).padStart(2, '0');
        timerEl.textContent = `${mins}:${secs}`;
    }

    function toggleButtons(rec, paused) {
        isRecording = rec; isPaused = paused;
        btnStart.disabled = rec; 
        btnPause.disabled = !rec || paused; 
        btnResume.disabled = !paused; 
        btnStop.disabled = !rec;
    }

    btnStart.addEventListener('click', () => {
        if (!meetingInput.value.trim()) { alert('Isi nama meeting dulu.'); return; }
        transcriptEl.value = ''; 
        latestAiSummary = '';
        audioPreviewContainer.style.display = 'none'; // Sembunyikan player lama
        
        metadataEl.style.display = 'block';
        metadataEl.innerHTML = `<strong>${meetingInput.value}</strong>`;
        
        recordingStartTime = Date.now();
        timerInterval = setInterval(updateTimer, 1000);
        toggleButtons(true, false);
        statusEl.innerText = "Merekam..."; statusIcon.innerText = "🔴"; btnStart.classList.add('recording');
        
        try { recognition.start(); } catch(e){}
        startAudioRecording();
        resetSilenceTimer();
    });

    btnPause.addEventListener('click', () => {
        recognition.stop(); 
        if(mediaRecorder && mediaRecorder.state === "recording") mediaRecorder.pause();
        toggleButtons(true, true);
        statusEl.innerText = "Dijeda"; statusIcon.innerText = "⏸"; btnStart.classList.remove('recording');
    });

    btnResume.addEventListener('click', () => {
        try { recognition.start(); } catch(e){}
        if(mediaRecorder && mediaRecorder.state === "paused") mediaRecorder.resume();
        toggleButtons(true, false);
        statusEl.innerText = "Merekam..."; statusIcon.innerText = "🔴"; btnStart.classList.add('recording');
    });

    btnStop.addEventListener('click', () => {
        clearInterval(timerInterval);
        recognition.stop();
        if(mediaRecorder) {
            mediaRecorder.stop();
            mediaRecorder.stream.getTracks().forEach(t => t.stop());
        }
        toggleButtons(false, false);
        statusEl.innerText = "Selesai"; statusIcon.innerText = "⚪"; btnStart.classList.remove('recording');
        
        // Tampilkan Player
        audioPreviewContainer.style.display = 'block';
    });

    function resetSilenceTimer() {
        if (silenceTimeout) clearTimeout(silenceTimeout);
        if (!isRecording || isPaused) return;
        silenceTimeout = setTimeout(() => { if(isRecording && !isPaused) { try{recognition.stop()}catch(e){} } }, 8000);
    }

    // --- SAVE ---
    btnSave.addEventListener('click', async () => {
        const formData = new FormData();
        formData.append('title', meetingInput.value);
        formData.append('language', languageSelect.options[languageSelect.selectedIndex].text);
        formData.append('transcript', transcriptEl.value);
        formData.append('summary', latestAiSummary);
        if (audioBlob) formData.append('audio_file', audioBlob, 'recording.webm');

        const originalText = btnSave.innerHTML;
        btnSave.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';
        btnSave.disabled = true;

        try {
            const res = await fetch('/meetings/store', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                body: formData
            });
            const json = await res.json();
            if (!res.ok) throw new Error(json.message);
            alert("Berhasil disimpan!");
        } catch (e) {
            alert("Gagal menyimpan: " + e.message);
        } finally {
            btnSave.innerHTML = originalText;
            btnSave.disabled = false;
        }
    });

    btnClear.addEventListener('click', () => {
        if(isRecording) { alert("Stop dulu."); return; }
        transcriptEl.value = '';
        audioPreviewContainer.style.display = 'none';
        metadataEl.style.display = 'none';
        timerEl.innerText = "00:00";
    });
</script>
@endpush