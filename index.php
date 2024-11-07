<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="/src/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <title>Gravador de Áudio</title>
    <style>
        body.dark-mode {
            background-color: #343a40;
            color: white;
        }
        .toggle-button {
            position: absolute;
            top: 20px;
            right: 20px;
        }
    </style>
</head>

<body class="light-mode">

<div class="toggle-button">
    <button id="toggle-button" class="btn btn-primary">
        <i class="fas fa-moon"></i> Modo Escuro
    </button>
</div>

<div class="container text-center mt-4">
    <div>
        <img src="src/img/logo.png" alt="logo" class="mb-4">
    </div>
    <h2>Gravador de Áudio</h2>
</div>

<div class="container mt-4" style="color: black;">
    <div class="row">
        <?php for ($i = 0; $i < 8; $i++): ?>
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-body">
                        <h3 class="card-title">Canal <?php echo $i + 1; ?></h3>
                        <select id="inputSelect<?php echo $i; ?>" class="form-control mb-2" onchange="updateInputDevice(<?php echo $i; ?>)">
                            <option value="">Selecionar Dispositivo</option>
                        </select>
                        <button id="startButton<?php echo $i; ?>" class="btn btn-primary mb-2" onclick="startRecording(<?php echo $i; ?>)">
                            <i class="fas fa-microphone"></i> Iniciar Gravação
                        </button>
                        <button id="stopButton<?php echo $i; ?>" class="btn btn-danger mb-2" onclick="stopRecording(<?php echo $i; ?>)">
                            <i class="fas fa-stop"></i> Parar Gravação
                        </button>
                        <button class="btn btn-secondary mb-2" onclick="toggleMute(<?php echo $i; ?>)">
                            <i class="fas fa-volume-mute"></i> Silenciar/Desmutar
                        </button>
                        <input type="range" id="volumeControl<?php echo $i; ?>" class="form-control-range mb-2" min="0" max="1" step="0.01" value="0.5" onchange="setVolume(<?php echo $i; ?>, this.value)">
                        <label for="volumeControl<?php echo $i; ?>" style="color:black;"> Volume</label>
                        <audio controls id="audio<?php echo $i + 1; ?>" class="w-100"></audio>
                        <button id="downloadButton<?php echo $i; ?>" class="btn btn-success mb-2" style="display:none;" onclick="downloadRecording(<?php echo $i; ?>)">
                            <i class="fas fa-download"></i> Baixar Gravação
                        </button>
                        <button id="uploadButton<?php echo $i; ?>" class="btn btn-info mb-2" style="display:none;" onclick="uploadRecording(<?php echo $i; ?>)">
                            <i class="fas fa-upload"></i> Upload Gravação
                        </button>

                        <h5 style="color: black;">Equalizador</h5>
                        <label for="bassControl">Graves</label>
                        <input type="range" id="bassControl" class="form-control-range mb-2" min="-24" max="24" step="1" value="0" onchange="setBass(this.value)">
                        <label for="midControl">Médios</label>
                        <input type="range" id="midControl" class="form-control-range mb-2" min="-24" max="24" step="1" value="0" onchange="setMid(this.value)">
                        <label for="trebleControl">Agudos</label>
                        <input type="range" id="trebleControl" class="form-control-range mb-2" min="-24" max="24" step="1" value="0" onchange="setTreble(this.value)">
                    </div>
                </div>
            </div>
        <?php endfor; ?>
    </div>
</div>

<script>
    // Declarações de variáveis
    const bassFilters = [];
    const midFilters = [];
    const trebleFilters = [];
    const mediaRecorders = [];
    const audioChunks = [];
    const isRecording = Array(8).fill(false);
    const isMuted = Array(8).fill(false);
    const audioContexts = [];
    const gainNodes = [];
    const storedVolumes = Array(8).fill(0.5);

    function initEqualizer(channelIndex, audioContext) {
        // Inicializa os filtros de equalizador
        bassFilters[channelIndex] = audioContext.createBiquadFilter();
        bassFilters[channelIndex].type = 'lowshelf';
        bassFilters[channelIndex].frequency.setValueAtTime(200, audioContext.currentTime);
        bassFilters[channelIndex].gain.setValueAtTime(0, audioContext.currentTime);

        midFilters[channelIndex] = audioContext.createBiquadFilter();
        midFilters[channelIndex].type = 'peaking';
        midFilters[channelIndex].frequency.setValueAtTime(1000, audioContext.currentTime);
        midFilters[channelIndex].gain.setValueAtTime(0, audioContext.currentTime);

        trebleFilters[channelIndex] = audioContext.createBiquadFilter();
        trebleFilters[channelIndex].type = 'highshelf';
        trebleFilters[channelIndex].frequency.setValueAtTime(3000, audioContext.currentTime);
        trebleFilters[channelIndex].gain.setValueAtTime(0, audioContext.currentTime);
    }

    function setBass(value) {
        const channelIndex = getCurrentChannelIndex();
        bassFilters[channelIndex].gain.setValueAtTime(value, audioContexts[channelIndex].currentTime);
    }

    function setMid(value) {
        const channelIndex = getCurrentChannelIndex();
        midFilters[channelIndex].gain.setValueAtTime(value, audioContexts[channelIndex].currentTime);
    }

    function setTreble(value) {
        const channelIndex = getCurrentChannelIndex();
        trebleFilters[channelIndex].gain.setValueAtTime(value, audioContexts[channelIndex].currentTime);
    }

    async function fetchInputDevices() {
        const devices = await navigator.mediaDevices.enumerateDevices();
        return devices.filter(device => device.kind === 'audioinput');
    }

    async function populateInputSelects() {
        const devices = await fetchInputDevices();
        devices.forEach((device) => {
            for (let i = 0; i < 8; i++) {
                const select = document.getElementById(`inputSelect${i}`);
                if (select) {
                    const option = document.createElement('option');
                    option.value = device.deviceId;
                    option.textContent = device.label || 'Dispositivo sem nome';
                    select.appendChild(option.cloneNode(true));
                }
            }
        });
        loadSavedSettings();
    }

    function loadSavedSettings() {
        for (let i = 0; i < 8; i++) {
            const savedDeviceId = localStorage.getItem(`inputDeviceChannel${i}`);
            if (savedDeviceId) {
                const select = document.getElementById(`inputSelect${i}`);
                if (select) {
                    select.value = savedDeviceId;
                }
            }
        }
    }

    async function startRecording(channelIndex) {
    // Verifica se uma gravação já está em andamento
    if (isRecording[channelIndex]) {
        alert("Você precisa salvar a gravação atual antes de iniciar uma nova.");
        return;
    }

    const selectedDeviceId = document.getElementById(`inputSelect${channelIndex}`).value;
    if (!selectedDeviceId) {
        alert("Selecione um dispositivo de entrada.");
        return;
    }

    localStorage.setItem(`inputDeviceChannel${channelIndex}`, selectedDeviceId);
    isRecording[channelIndex] = true;

    const stream = await navigator.mediaDevices.getUserMedia({ audio: { deviceId: selectedDeviceId } });
    audioContexts[channelIndex] = new (window.AudioContext || window.webkitAudioContext)();
    gainNodes[channelIndex] = audioContexts[channelIndex].createGain();
    gainNodes[channelIndex].gain.value = isMuted[channelIndex] ? 0 : storedVolumes[channelIndex];

    const source = audioContexts[channelIndex].createMediaStreamSource(stream);
    source.connect(gainNodes[channelIndex]);
    gainNodes[channelIndex].connect(audioContexts[channelIndex].destination);

    const mediaRecorder = new MediaRecorder(stream);
    mediaRecorders[channelIndex] = mediaRecorder;
    audioChunks[channelIndex] = [];

    mediaRecorder.ondataavailable = (event) => {
        audioChunks[channelIndex].push(event.data);
    };

    mediaRecorder.onstop = () => {
        const audioBlob = new Blob(audioChunks[channelIndex], { type: 'audio/mp3' });
        const audioURL = URL.createObjectURL(audioBlob);
        document.getElementById(`audio${channelIndex + 1}`).src = audioURL;

        // Mostra os botões de download e upload
        const downloadButton = document.getElementById(`downloadButton${channelIndex}`);
        downloadButton.style.display = 'inline';
        downloadButton.onclick = function() {
            downloadRecording(channelIndex, audioBlob);
            // Após baixar, permite novas gravações
            resetRecording(channelIndex);
        };

        const uploadButton = document.getElementById(`uploadButton${channelIndex}`);
        uploadButton.style.display = 'inline';
        uploadButton.audioBlob = audioBlob;
        uploadButton.onclick = function() {
            uploadRecording(channelIndex);
            // Após fazer upload, permite novas gravações
            resetRecording(channelIndex);
        };
    };

    mediaRecorder.start();
    document.getElementById(`startButton${channelIndex}`).disabled = true; // Desabilitar o botão de iniciar durante a gravação
}

function stopRecording(channelIndex) {
    if (!isRecording[channelIndex]) return;

    isRecording[channelIndex] = false;
    mediaRecorders[channelIndex].stop();
}

function resetRecording(channelIndex) {
    const downloadButton = document.getElementById(`downloadButton${channelIndex}`);
    const uploadButton = document.getElementById(`uploadButton${channelIndex}`);

    // Reseta os dados de áudio e os elementos da interface
    audioChunks[channelIndex] = [];
    document.getElementById(`audio${channelIndex + 1}`).src = '';
    downloadButton.style.display = 'none';
    uploadButton.style.display = 'none';

    // Reabilita o botão de iniciar para novas gravações
    document.getElementById(`startButton${channelIndex}`).disabled = false;
}


    function toggleMute(channelIndex) {
        isMuted[channelIndex] = !isMuted[channelIndex];
        gainNodes[channelIndex].gain.value = isMuted[channelIndex] ? 0 : storedVolumes[channelIndex];
    }

    function setVolume(channelIndex, volume) {
        storedVolumes[channelIndex] = volume;
        gainNodes[channelIndex].gain.value = isMuted[channelIndex] ? 0 : volume;
    }

    function downloadRecording(channelIndex, audioBlob) {
        const url = URL.createObjectURL(audioBlob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `recording_channel_${channelIndex + 1}.mp3`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
        resetChannel(channelIndex);
    }

    function resetChannel(channelIndex) {
        audioChunks[channelIndex] = [];
        document.getElementById(`audio${channelIndex + 1}`).src = '';
        document.getElementById(`downloadButton${channelIndex}`).style.display = 'none';
        document.getElementById(`uploadButton${channelIndex}`).style.display = 'none';
        document.getElementById(`startButton${channelIndex}`).disabled = false; // Reabilitar botão para nova gravação
    }

    async function updateInputDevice(channelIndex) {
        const selectedDeviceId = document.getElementById(`inputSelect${channelIndex}`).value;
        localStorage.setItem(`inputDeviceChannel${channelIndex}`, selectedDeviceId);
    }

    function uploadRecording(channelIndex) {
        const audioBlob = document.getElementById(`uploadButton${channelIndex}`).audioBlob;
        const formData = new FormData();
        formData.append('audio', audioBlob, `recording_channel_${channelIndex + 1}.mp3`);

        fetch('upload.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert("Gravação enviada com sucesso!");
            } else {
                alert("Erro ao enviar a gravação: " + data.message);
            }
        })
        .catch(error => {
            console.error("Erro:", error);
            alert("Erro ao enviar a gravação.");
        });
    }

    function setCookie(name, value, days) {
        const d = new Date();
        d.setTime(d.getTime() + (days * 24 * 60 * 60 * 1000));
        const expires = "expires=" + d.toUTCString();
        document.cookie = name + "=" + value + ";" + expires + ";path=/";
    }

    function getCookie(name) {
        const nameEQ = name + "=";
        const ca = document.cookie.split(';');
        for (let i = 0; i < ca.length; i++) {
            let c = ca[i];
            while (c.charAt(0) === ' ') c = c.substring(1, c.length);
            if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length, c.length);
        }
        return null;
    }

    function applyTheme() {
        const theme = getCookie("theme");
        if (theme === "dark") {
            document.body.classList.add("dark-mode");
            document.getElementById('toggle-button').innerHTML = '<i class="fas fa-sun"></i> Modo Claro';
        } else {
            document.body.classList.remove("dark-mode");
            document.getElementById('toggle-button').innerHTML = '<i class="fas fa-moon"></i> Modo Escuro';
        }
    }

    window.onload = function() {
        applyTheme();
        populateInputSelects();
    };

    const toggleButton = document.getElementById('toggle-button');

    toggleButton.addEventListener('click', () => {
        const isDarkMode = document.body.classList.toggle('dark-mode');
        setCookie("theme", isDarkMode ? "dark" : "light", 7);
        toggleButton.innerHTML = isDarkMode ? '<i class="fas fa-sun"></i> Modo Claro' : '<i class="fas fa-moon"></i> Modo Escuro';
    });

    window.onbeforeunload = function() {
        return "Deseja realmente atualizar? As gravações serão perdidas.";
    };

</script>

<script src="/javascript/javascrip.js"></script>
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.2/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

<footer class="footer text-center">
    <div class="container">
        &copy; Desenvolvido por Helton Machado.
    </div>
</footer>

</body>

</html>
