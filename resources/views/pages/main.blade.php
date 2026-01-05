@extends('layouts.master')

@section('title', 'QuickNote Voice + Gemini AI')

@section('content')

<!-- 1. Include Navbar Manual di sini -->
@include('layouts.navbar')

<!-- 2. Wrapper Main Content -->
<main class="main-content">
    <div class="container">
        <h1><i class="fas fa-microphone-lines" style="color:#21808d;"></i> QuickNote Voice</h1>

        <!-- API KEY INPUT SECTION -->
        <div class="api-key-container">
            <label for="api-key"><i class="fas fa-key"></i> Gemini API Key</label>
            <input type="password" id="api-key" placeholder="Tempel Gemini API Key Anda di sini (dimulai dengan AIza...)" />
            <div class="api-key-helper">
                Key ini diperlukan untuk fitur AI. Key hanya digunakan di browser Anda dan tidak disimpan di server kami.
            </div>
        </div>

        <!-- FORM INPUTS -->
        <div style="margin-bottom: 20px;">
            <label for="meeting-name">Nama Meeting</label>
            <input type="text" id="meeting-name" placeholder="Contoh: Interview Kandidat Backend Developer" />
        </div>

        <div style="display: flex; gap: 20px; flex-wrap: wrap; margin-bottom: 20px;">
            <!-- Language Selector -->
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

            <!-- Microphone Selector -->
            <div style="flex: 1; min-width: 150px;">
                <label for="mic-select">Pilih Mikrofon</label>
                <select id="mic-select">
                    <option value="default">Default System</option>
                </select>
                <div style="font-size: 11px; color: #888; margin-top: 4px;">
                    *Refresh jika mic tidak muncul.
                </div>
            </div>
        </div>
        
        <!-- UI Player -->
        <div class="player-wrapper">
            <div class="controls">
            <button id="btn-stop" class="btn-player btn-stop" disabled title="Stop Recording">
                <i class="fas fa-stop"></i>
            </button>
            <button id="btn-pause" class="btn-player btn-pause" disabled title="Pause">
                <i class="fas fa-pause"></i>
            </button>
            <button id="btn-start" class="btn-player btn-start" title="Mulai Rekam">
                <i class="fas fa-microphone"></i>
            </button>
            <button id="btn-resume" class="btn-player btn-resume" disabled title="Resume">
                <i class="fas fa-play"></i>
            </button>
            </div>
            <div class="status-display">
                <span id="status-icon">⚪</span> <span id="status">Ready to Record</span>
                <span id="timer" style="margin-left:10px; font-family: monospace;">00:00</span>
            </div>
        </div>

        <label>Hasil Transkripsi (Mentah)</label>
        
        <!-- Info Meeting (Header) dipisah kesini -->
        <div id="metadata-display" class="metadata-box"></div>

        <div class="interim-preview" id="interim-preview">
            <i class="fas fa-wave-square" style="margin-right:8px; opacity:0.5;"></i> Menunggu suara...
        </div>
        <textarea id="transcript" placeholder="Teks dari suara akan muncul di sini..."></textarea>
        
        <!-- BAGIAN BARU: KONTEKS INPUT -->
        <div style="margin-top: 20px; background: #fff8e1; padding: 15px; border-radius: 12px; border: 1px solid #ffe082;">
            <label for="context-input" style="color: #f57f17;"><i class="fas fa-lightbulb"></i> Konteks & Koreksi (Penting untuk AI)</label>
            <textarea id="context-input" rows="2" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #ddd; font-size: 13px;" 
            placeholder="Masukkan nama pembicara, istilah teknis, atau topik agar AI bisa memperbaiki typo transkrip.&#10;Contoh: Pembicara adalah Pak Budi dan Bu Sari. Membahas framework Laravel dan VueJS."></textarea>
        </div>

        <!-- AI SMART ACTIONS -->
        <div class="ai-section">
            <div class="ai-title"><i class="fas fa-sparkles" style="color: #f0ad4e;"></i> AI Smart Actions (Powered by Gemini)</div>
            <div class="ai-buttons-container">
                <button class="btn-ai" onclick="askGemini('summary')">
                    ✨ Perbaiki & Ringkas Meeting
                </button>
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
            <button id="btn-save" class="btn-action btn-save" disabled>
                <i class="fas fa-save"></i> Simpan
            </button>
            <button id="btn-clear" class="btn-action btn-clear">
                <i class="fas fa-trash"></i> Hapus
            </button>
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
    const contextInput = document.getElementById('context-input'); // Input Konteks Baru
    const timerEl = document.getElementById('timer');
    const metadataEl = document.getElementById('metadata-display');
    const apiKeyInput = document.getElementById('api-key');

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

    // === GEMINI API INTEGRATION (UPDATED) ===
    async function askGemini(type) {
        const apiKey = apiKeyInput.value.trim();

        if (!apiKey) {
            alert("⚠️ API Key belum diisi!");
            apiKeyInput.focus();
            window.scrollTo(0,0);
            return;
        }

        const text = transcriptEl.value.trim();
        const context = contextInput.value.trim(); // Ambil nilai konteks

        if (!text || text.length < 10) {
            alert("Transkrip masih terlalu pendek untuk diproses AI.");
            return;
        }

        const aiLoading = document.getElementById('ai-loading');
        const aiResult = document.getElementById('ai-result');
        const aiTitle = document.getElementById('ai-result-title');
        const aiContent = document.getElementById('ai-result-content');

        aiLoading.style.display = 'block';
        aiResult.classList.remove('active');
        
        let prompt = "";
        let titleDisplay = "";

        if (type === 'summary') {
            titleDisplay = "✨ Ringkasan (Dikoreksi Konteks)";
            
            // PROMPT ENGINEERING YANG LEBIH CANGGIH
            prompt = `
            Bertindaklah sebagai asisten notulen rapat yang cerdas.
            Saya akan memberikan [TRANSKRIP MENTAH] dari konversi suara-ke-teks yang mungkin mengandung kesalahan ejaan nama, istilah teknis, atau salah dengar.
            Saya juga memberikan [KONTEKS] berisi nama pembicara atau istilah yang benar.

            Tugas Anda:
            1. Analisis transkrip mentah.
            2. Gunakan informasi pada [KONTEKS] untuk memperbaiki kesalahan ejaan nama atau istilah teknis pada transkrip secara mental.
            3. Buatkan ringkasan poin-poin penting (bullet points) yang akurat berdasarkan pemahaman yang sudah dikoreksi tersebut. Gunakan Bahasa Indonesia.

            [KONTEKS]:
            "${context ? context : 'Tidak ada konteks tambahan.'}"

            [TRANSKRIP MENTAH]:
            "${text}"
            `;
        } 

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
            
            latestAiSummary = rawResult;

            let formattedResult = rawResult
                .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                .replace(/\n/g, '<br>');

            aiTitle.textContent = titleDisplay;
            aiContent.innerHTML = formattedResult;
            aiResult.classList.add('active');

        } catch (error) {
            console.error(error);
            alert(`Gagal memproses AI: ${error.message}`);
        } finally {
            aiLoading.style.display = 'none';
        }
    }

    // --- WEB SPEECH & RECORDER (Sama seperti sebelumnya) ---
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    let recognition = null;
    let isRecording = false;
    let isPaused = false;
    let silenceTimeout = null;
    let recordingStartTime = null;
    let timerInterval = null;
    const SILENCE_DURATION = 8000; 

    if (!SpeechRecognition) {
      alert('Browser kamu tidak mendukung Web Speech API.');
      btnStart.disabled = true;
    } else {
      recognition = new SpeechRecognition();
      recognition.lang = 'id-ID';
      recognition.continuous = true;
      recognition.interimResults = true;
    }

    function updateTimer() {
        if (!recordingStartTime || isPaused) return;
        const now = Date.now();
        const diff = Math.floor((now - recordingStartTime) / 1000);
        const mins = Math.floor(diff / 60).toString().padStart(2, '0');
        const secs = (diff % 60).toString().padStart(2, '0');
        timerEl.textContent = `${mins}:${secs}`;
    }

    async function startAudioRecording() {
        try {
            const selectedMicId = micSelect.value;
            const constraints = {
                audio: selectedMicId !== 'default' ? { deviceId: { exact: selectedMicId } } : true
            };
            const stream = await navigator.mediaDevices.getUserMedia(constraints);
            mediaRecorder = new MediaRecorder(stream);
            audioChunks = [];
            mediaRecorder.ondataavailable = event => audioChunks.push(event.data);
            mediaRecorder.onstop = () => audioBlob = new Blob(audioChunks, { type: 'audio/webm' });
            mediaRecorder.start();
        } catch (err) {
            console.error("Gagal merekam audio:", err);
        }
    }

    function stopAudioRecording() {
        if (mediaRecorder && mediaRecorder.state !== 'inactive') {
            mediaRecorder.stop();
            mediaRecorder.stream.getTracks().forEach(track => track.stop());
        }
    }

    function startRecognitionSafe() {
        if (!recognition) return;
        try { recognition.start(); } catch (e) {}
    }

    function restartRecognition() {
        if (recognition && isRecording) startRecognitionSafe();
    }

    function resetSilenceTimer() {
        if (silenceTimeout) clearTimeout(silenceTimeout);
        if (!isRecording || isPaused) return;
        silenceTimeout = setTimeout(() => {
            if (isRecording && !isPaused) {
                try { recognition.stop(); } catch (e) { restartRecognition(); }
            }
        }, SILENCE_DURATION);
    }

    function setStatus(text, type = 'idle') {
        statusEl.textContent = text;
        if (type === 'recording') {
            statusIcon.textContent = '🔴';
            btnStart.classList.add('recording');
        } else if (type === 'paused') {
            statusIcon.textContent = '⏸';
            btnStart.classList.remove('recording');
        } else {
            statusIcon.textContent = '⚪';
            btnStart.classList.remove('recording');
        }
    }

    function toggleButtons(recording, paused = false) {
        isRecording = recording;
        isPaused = paused;
        btnStart.disabled = recording; 
        btnPause.disabled = !recording || paused;
        btnResume.disabled = !paused;
        btnStop.disabled = !recording;
        btnSave.disabled = !transcriptEl.value.trim();
        if (!recording) {
            clearInterval(timerInterval);
            timerEl.textContent = "00:00";
        }
    }

    transcriptEl.addEventListener('input', () => {
        btnSave.disabled = !transcriptEl.value.trim();
    });

    if (recognition) {
        recognition.onstart = () => {
            if (!isPaused) {
                setStatus('Merekam...', 'recording');
                if (!timerInterval) {
                    recordingStartTime = Date.now(); 
                    timerInterval = setInterval(updateTimer, 1000);
                }
                resetSilenceTimer();
            }
        };
        recognition.onend = () => {
            if (isRecording && !isPaused) {
                setTimeout(() => restartRecognition(), 200);
            } else if (!isRecording) {
                setStatus('Berhenti', 'idle');
                clearInterval(timerInterval);
            }
        };
        recognition.onerror = (event) => { resetSilenceTimer(); };
        recognition.onresult = (event) => {
            resetSilenceTimer();
            let finalTranscript = '';
            let interimTranscript = '';
            for (let i = event.resultIndex; i < event.results.length; i++) {
                const transcript = event.results[i][0].transcript;
                if (event.results[i].isFinal) {
                    let sentence = transcript.trim();
                    if (sentence.length > 0) sentence = sentence.charAt(0).toUpperCase() + sentence.slice(1);
                    if (!/[.?!]$/.test(sentence)) sentence += '.';
                    finalTranscript += sentence + ' ';
                } else {
                    interimTranscript += transcript;
                }
            }
            const previewEl = document.getElementById('interim-preview');
            if (interimTranscript.trim()) previewEl.innerHTML = '<i class="fas fa-microphone-lines" style="color:#21808d; margin-right:8px;"></i> ' + interimTranscript;
            if (finalTranscript.trim()) {
                previewEl.innerHTML = '<i class="fas fa-check" style="color:#ccc; margin-right:8px;"></i> Menunggu suara...';
                transcriptEl.value += finalTranscript;
                transcriptEl.dispatchEvent(new Event('input'));
                transcriptEl.scrollTop = transcriptEl.scrollHeight;
            }
        };
    }

    languageSelect.addEventListener('change', () => {
        if (recognition && !isRecording) recognition.lang = languageSelect.value;
    });

    // --- BUTTONS ---
    btnStart.addEventListener('click', () => {
        const meetingName = meetingInput.value.trim();
        if (!meetingName) {
            alert('Isi dulu nama meeting sebelum mulai rekam.');
            meetingInput.focus();
            return;
        }
        if (!recognition || isRecording) return;
        
        transcriptEl.value = '';
        audioBlob = null;
        
        metadataEl.style.display = 'block';
        metadataEl.innerHTML = `
            <div style="font-weight:bold; margin-bottom:5px; font-size:14px;">${meetingName}</div>
            <div>
                <span class="metadata-item"><i class="fas fa-clock"></i> ${new Date().toLocaleString('id-ID')}</span>
                <span class="metadata-item"><i class="fas fa-globe"></i> ${languageSelect.options[languageSelect.selectedIndex].text}</span>
            </div>
        `;
        
        recordingStartTime = Date.now();
        timerInterval = setInterval(updateTimer, 1000);
        recognition.lang = languageSelect.value;
        toggleButtons(true);
        setStatus('Mengakses Mic...', 'idle');
        startRecognitionSafe();
        startAudioRecording(); 
        resetSilenceTimer();
    });

    btnPause.addEventListener('click', () => {
        if (!recognition || !isRecording) return;
        if (silenceTimeout) clearTimeout(silenceTimeout);
        recognition.stop();
        if (mediaRecorder && mediaRecorder.state === "recording") mediaRecorder.pause();
        toggleButtons(true, true);
        setStatus('Dijeda', 'paused');
    });

    btnResume.addEventListener('click', () => {
        if (!recognition || !isRecording) return;
        toggleButtons(true, false);
        setStatus('Merekam...', 'recording');
        startRecognitionSafe();
        if (mediaRecorder && mediaRecorder.state === "paused") mediaRecorder.resume();
        resetSilenceTimer();
    });

    btnStop.addEventListener('click', () => {
        if (!recognition || !isRecording) return;
        if (silenceTimeout) clearTimeout(silenceTimeout);
        clearInterval(timerInterval);
        recognition.stop();
        stopAudioRecording();
        toggleButtons(false);
        setStatus('Selesai', 'idle');
        document.getElementById('interim-preview').innerHTML = '<i class="fas fa-stop-circle" style="color:#ccc; margin-right:8px;"></i> Selesai.';
        recordingStartTime = null;
    });

    btnSave.addEventListener('click', async () => {
        const meetingName = meetingInput.value.trim();
        const transcript = transcriptEl.value.trim();
        if (!meetingName || !transcript) {
            alert('Nama meeting dan transkripsi harus diisi.');
            return;
        }
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const formData = new FormData();
        formData.append('title', meetingName);
        formData.append('language', languageSelect.options[languageSelect.selectedIndex].text);
        formData.append('transcript', transcript);
        formData.append('summary', latestAiSummary || '');
        if (audioBlob) formData.append('audio_file', audioBlob, 'recording.webm');

        const originalBtnText = btnSave.innerHTML;
        btnSave.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';
        btnSave.disabled = true;

        try {
            const response = await fetch('/meetings/store', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken },
                body: formData
            });
            const result = await response.json();
            if (!response.ok) throw new Error(result.message || 'Gagal menyimpan');
            alert(`Berhasil disimpan!`);
        } catch (error) {
            console.error(error);
            alert('Terjadi kesalahan: ' + error.message);
        } finally {
            btnSave.innerHTML = originalBtnText;
            btnSave.disabled = false;
        }
    });

    btnClear.addEventListener('click', () => {
        if (isRecording) {
            alert('Hentikan recording terlebih dahulu.');
            return;
        }
        transcriptEl.value = '';
        metadataEl.style.display = 'none';
        document.getElementById('interim-preview').innerHTML = '<i class="fas fa-wave-square" style="margin-right:8px; opacity:0.5;"></i> Menunggu suara...';
        toggleButtons(false);
        recordingStartTime = null;
        timerEl.textContent = "00:00";
        audioBlob = null;
    });
</script>
@endpush