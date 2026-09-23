import { test } from 'node:test';
import assert from 'node:assert/strict';
import { createScanner } from '../../resources/js/sale-camera.js';

test('camera requires HTTPS without requesting permission', async () => {
    globalThis.window = { isSecureContext: false };
    const scanner = createScanner({}, () => assert.fail('Must not add a product'));
    await scanner.start();
    assert.match(scanner.error, /HTTPS/);
    assert.equal(scanner.starting, false);
});

test('permission denial is recoverable and explained', async () => {
    globalThis.window = { isSecureContext: true };
    Object.defineProperty(globalThis, 'navigator', { configurable: true, value: {
        mediaDevices: { getUserMedia: async () => { throw Object.assign(new Error(), { name: 'NotAllowedError' }); } },
    } });
    const scanner = createScanner({}, () => assert.fail('Must not add a product'));
    await scanner.start();
    assert.match(scanner.error, /permission was denied/);
    assert.equal(scanner.starting, false);
    assert.equal(scanner.running, false);
});

test('closing while permission is pending releases the late camera stream', async () => {
    let resolvePermission;
    let requested;
    const requestStarted = new Promise(resolve => { requested = resolve; });
    let stopped = 0;
    globalThis.window = { isSecureContext: true };
    Object.defineProperty(globalThis, 'navigator', { configurable: true, value: {
        mediaDevices: { getUserMedia: () => { requested(); return new Promise(resolve => { resolvePermission = resolve; }); } },
    } });
    const scanner = createScanner({}, () => assert.fail('Must not add a product'));
    const pending = scanner.start();
    await requestStarted;
    scanner.stop();
    resolvePermission({ getTracks: () => [{ stop: () => { stopped++; } }] });
    await pending;
    assert.equal(stopped, 1);
    assert.equal(scanner.open, false);
    assert.equal(scanner.running, false);
});

test('requests high resolution rear camera without making resolution mandatory', async () => {
    let requestedConstraints;
    globalThis.window = { isSecureContext: true };
    Object.defineProperty(globalThis, 'navigator', { configurable: true, value: {
        mediaDevices: { getUserMedia: async constraints => {
            requestedConstraints = constraints;
            throw Object.assign(new Error(), { name: 'NotAllowedError' });
        } },
    } });
    const scanner = createScanner({}, () => assert.fail('Must not add a product'));
    await scanner.start();
    assert.deepEqual(requestedConstraints.video, {
        width: { ideal: 1920 }, height: { ideal: 1080 }, facingMode: { ideal: 'environment' },
    });
    scanner.deviceId = 'selected-camera';
    await scanner.start();
    assert.deepEqual(requestedConstraints.video.deviceId, { exact: 'selected-camera' });
    assert.equal(requestedConstraints.video.facingMode, undefined);
});
