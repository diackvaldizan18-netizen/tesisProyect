import jsQR from 'jsqr';
import { ticketQrUrl } from './ticket-qr-url';

const panel = document.getElementById('ticket-scanner');
if (panel) {
    const video = panel.querySelector('video');
    const status = panel.querySelector('[data-scan-status]');
    const start = panel.querySelector('[data-scan-start]');
    const canvas = document.createElement('canvas');
    const context = canvas.getContext('2d', { willReadFrequently: true });
    let stream = null, timer = null, generation = 0;
    const stop = () => {
        generation++;
        clearTimeout(timer);
        stream?.getTracks().forEach(track => track.stop());
        stream = null;
        video.srcObject = null;
        start.disabled = false;
    };
    const scan = token => {
        if (token !== generation || !stream || !panel.open) return;
        if (video.readyState >= 2 && video.videoWidth && context) {
            const scale = Math.min(1, 960 / video.videoWidth);
            canvas.width = Math.round(video.videoWidth * scale);
            canvas.height = Math.round(video.videoHeight * scale);
            context.drawImage(video, 0, 0, canvas.width, canvas.height);
            const frame = context.getImageData(0, 0, canvas.width, canvas.height);
            const code = jsQR(frame.data, frame.width, frame.height, { inversionAttempts: 'dontInvert' });
            if (code) {
                const url = ticketQrUrl(code.data, panel.dataset.ticketRoute, window.location.origin);
                if (url) {
                    stop();
                    status.textContent = 'Ticket detectado. Abriendo el saldo…';
                    window.location.assign(url);
                    return;
                }
                status.textContent = 'Este QR no es un ticket de esta sede. Apunta al QR impreso de Parke’o.';
            }
        }
        timer = setTimeout(() => scan(token), 180);
    };
    const openCamera = async () => {
        stop();
        const token = generation;
        if (!window.isSecureContext || !navigator.mediaDevices?.getUserMedia) {
            status.textContent = 'La cámara necesita HTTPS. Abre la dirección segura de Parke’o o busca el ticket manualmente.';
            return;
        }
        start.disabled = true;
        status.textContent = 'Permite el acceso a la cámara para leer el ticket.';
        try {
            const camera = await navigator.mediaDevices.getUserMedia({ audio: false, video: { facingMode: { ideal: 'environment' }, width: { ideal: 1280 } } });
            if (token !== generation || !panel.open) { camera.getTracks().forEach(track => track.stop()); return; }
            stream = camera;
            video.srcObject = stream;
            await video.play();
            if (token !== generation) return;
            status.textContent = 'Coloca el QR completo dentro de la imagen y mantén el ticket quieto.';
            scan(token);
        } catch (error) {
            if (token !== generation) return;
            stop();
            status.textContent = error.name === 'NotAllowedError'
                ? 'Acceso a la cámara denegado. Permítelo en el navegador y pulsa Activar cámara, o busca por código.'
                : 'No se pudo abrir la cámara. Comprueba que esté conectada y que otra aplicación no la esté usando.';
        }
    };
    document.querySelectorAll('[data-open-ticket-scanner]').forEach(button => button.addEventListener('click', () => {
        panel.showModal();
        openCamera();
    }));
    start.addEventListener('click', openCamera);
    panel.querySelector('[data-scan-close]').addEventListener('click', () => panel.close());
    panel.querySelector('[data-scan-manual]').addEventListener('click', () => {
        panel.close();
        const search = document.getElementById('buscar');
        if (search) search.focus();
        else window.location.assign(panel.dataset.manualUrl);
    });
    panel.addEventListener('close', stop);
    panel.addEventListener('cancel', stop);
    window.addEventListener('pagehide', stop);
    document.addEventListener('visibilitychange', () => {
        if (document.hidden && panel.open) {
            stop();
            status.textContent = 'Cámara pausada. Pulsa Activar cámara para continuar.';
        }
    });
}
