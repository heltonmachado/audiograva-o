const mediaRecorders = [];
const audioChunks = [];
const isRecording = Array(8).fill(false);
const isMuted = Array(8).fill(false);
const audioContexts = [];
const gainNodes = [];

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
                select.appendChild(option.cloneNode(true)); // Clona a opção para o canal
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
    if (isRecording[channelIndex]) return;

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
    gainNodes[channelIndex].gain.value = isMuted[channelIndex] ? 0 : 1;

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
        const audioBlob = new Blob(audioChunks[channelIndex], { type: 'audio/webm' });
        const audioURL = URL.createObjectURL(audioBlob);
        document.getElementById(`audio${channelIndex + 1}`).src = audioURL;

        const downloadButton = document.getElementById(`downloadButton${channelIndex}`);
        downloadButton.style.display = 'inline';
        downloadButton.onclick = function() {
            downloadRecording(channelIndex, audioBlob);
        };

        const uploadButton = document.getElementById(`uploadButton${channelIndex}`);
        uploadButton.style.display = 'inline';
        uploadButton.audioBlob = audioBlob;

        const startButton = document.getElementById(`startButton${channelIndex}`);
        startButton.disabled = false; // Liberar para nova gravação
    };

    mediaRecorder.start();
    document.getElementById(`startButton${channelIndex}`).disabled = true; // Desabilitar botão de iniciar durante gravação
}

function stopRecording(channelIndex) {
    if (!isRecording[channelIndex]) return;

    isRecording[channelIndex] = false;
    mediaRecorders[channelIndex].stop();
}

function toggleMute(channelIndex) {
    isMuted[channelIndex] = !isMuted[channelIndex];
    gainNodes[channelIndex].gain.value = isMuted[channelIndex] ? 0 : 1;
}

function setVolume(channelIndex, volume) {
    gainNodes[channelIndex].gain.value = volume;
}

function downloadRecording(channelIndex, audioBlob) {
    const url = URL.createObjectURL(audioBlob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `recording_channel_${channelIndex + 1}.webm`;
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
    formData.append('audio', audioBlob, `recording_channel_${channelIndex + 1}.webm`);

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

window.onload = populateInputSelects;

window.onbeforeunload = function() {
    return "Deseja realmente atualizar? As gravações serão perdidas.";
};
