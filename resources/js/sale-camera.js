export function createScanner(video, addProduct) {
    let stream = null;
    let controls = null;
    let generation = 0;
    let destroyed = false;

    const release = () => {
        controls?.stop();
        controls = null;
        stream?.getTracks().forEach(track => track.stop());
        stream = null;
        video.srcObject = null;
    };

    return {
        open: false,
        starting: false,
        running: false,
        busy: false,
        cameras: [],
        deviceId: '',
        message: '',
        error: '',

        async start() {
            if (destroyed || this.starting || this.busy || this.running) return;
            this.open = true;
            this.error = '';
            if (!window.isSecureContext || !navigator.mediaDevices?.getUserMedia) {
                this.error = 'Camera access requires HTTPS. Open the secure website address on your device, or enter the barcode below.';
                return;
            }

            const session = ++generation;
            this.starting = true;
            this.message = 'Allow camera access when your browser asks.';
            try {
                const { BrowserMultiFormatReader } = await import('@zxing/browser');
                const { BarcodeFormat, DecodeHintType } = await import('@zxing/library');
                if (session !== generation) return;
                const captured = await navigator.mediaDevices.getUserMedia({
                    audio: false,
                    video: {
                        width: { ideal: 1920 },
                        height: { ideal: 1080 },
                        ...(this.deviceId ? { deviceId: { exact: this.deviceId } } : { facingMode: { ideal: 'environment' } }),
                    },
                });
                if (session !== generation) {
                    captured.getTracks().forEach(track => track.stop());
                    return;
                }
                stream = captured;
                const track = captured.getVideoTracks()[0];
                const capabilities = track?.getCapabilities?.();
                if (capabilities?.focusMode?.includes('continuous')) {
                    await track.applyConstraints({ advanced: [{ focusMode: 'continuous' }] }).catch(() => {});
                }
                this.deviceId = captured.getVideoTracks()[0]?.getSettings().deviceId ?? '';
                this.cameras = (await navigator.mediaDevices.enumerateDevices()).filter(device => device.kind === 'videoinput');
                if (session !== generation) return;
                this.running = true;
                this.message = 'Keep the full barcode near the middle of the view, with white space at both ends. Move back slightly if it looks blurry.';
                const hints = new Map([
                    [DecodeHintType.TRY_HARDER, true],
                    [DecodeHintType.POSSIBLE_FORMATS, [BarcodeFormat.CODE_128, BarcodeFormat.EAN_13, BarcodeFormat.EAN_8, BarcodeFormat.UPC_A, BarcodeFormat.UPC_E, BarcodeFormat.CODE_39]],
                ]);
                const reader = new BrowserMultiFormatReader(hints, { delayBetweenScanAttempts: 150 });
                const scanControls = await reader.decodeFromStream(captured, video, (result, error, activeControls) => {
                    if (!result || this.busy || !this.running || session !== generation) return;
                    this.running = false;
                    this.busy = true;
                    activeControls.stop();
                    release();
                    this.message = 'Checking barcode…';
                    Promise.resolve().then(() => addProduct(result.getText()))
                        .then(productName => {
                            this.message = productName
                                ? `${productName} added. Tap Scan next item to continue.`
                                : 'Item not added. Check the barcode message below.';
                        })
                        .catch(() => { this.error = 'Could not confirm the scan. Check the item list before trying again.'; })
                        .finally(() => { this.busy = false; });
                });
                if (session !== generation || !this.running) scanControls.stop();
                else controls = scanControls;
            } catch (error) {
                if (session !== generation) return;
                release();
                this.running = false;
                this.message = '';
                const messages = {
                    NotAllowedError: 'Camera permission was denied. Allow camera access in your browser settings, then try again.',
                    NotFoundError: 'No camera was found on this device. You can enter the barcode below.',
                    NotReadableError: 'The camera is busy. Close other apps using it, then try again.',
                    OverconstrainedError: 'This camera is unavailable. Choose another camera or try again.',
                };
                this.error = messages[error.name] || 'Could not start the camera. Check your browser permissions and try again.';
            } finally {
                if (session === generation) this.starting = false;
            }
        },

        stop() {
            generation++;
            release();
            this.running = false;
            this.starting = false;
            this.open = false;
        },

        switchCamera(deviceId) {
            this.stop();
            this.deviceId = deviceId;
            return this.start();
        },

        destroy() {
            destroyed = true;
            this.stop();
        },
    };
}
