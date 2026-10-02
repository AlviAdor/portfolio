/*
 * Voice and video calling over WebRTC.
 *
 * The media itself never touches this server: once the two browsers connect,
 * audio and video flow directly peer-to-peer, encrypted by WebRTC's own
 * mandatory DTLS-SRTP -- the same guarantee every WebRTC call has, browser-
 * native, no code here to get that part wrong, and true even when a TURN
 * relay is involved (TURN forwards encrypted packets; the DTLS keys are
 * negotiated directly between the two browsers and a relay never has them).
 * What *isn't* automatically private is the signaling that lets the two
 * browsers find each other in the first place (the offer/answer/ICE
 * candidates) -- see app/Views/chat/room.php, which encrypts each one with
 * the same RSA+AES scheme the chat already uses before it ever reaches this
 * module or the server.
 *
 * This module's only job is the mechanics of one call: the peer connection,
 * local mic/camera, remote audio/video. Deliberately plain WebRTC -- no
 * library, no SFU, no experimental APIs (insertable streams, etc.) -- so it
 * runs on whatever a standard browser already ships.
 */
const VoiceCall = (() => {
    let pc = null;
    let localStream = null;
    let videoEnabled = false;

    async function getLocalMedia(withVideo) {
        try {
            return await navigator.mediaDevices.getUserMedia({ audio: true, video: withVideo ? { facingMode: 'user' } : false });
        } catch (err) {
            if (!withVideo) throw err;
            // No camera, or permission denied for it -- fall back to audio-only
            // rather than failing the call outright.
            return navigator.mediaDevices.getUserMedia({ audio: true, video: false });
        }
    }

    async function createConnection(iceServers, withVideo, handlers) {
        pc = new RTCPeerConnection({ iceServers });

        pc.onicecandidate = (event) => {
            if (event.candidate) handlers.onIceCandidate(event.candidate.toJSON());
        };
        pc.ontrack = (event) => {
            handlers.onRemoteStream(event.streams[0]);
        };
        pc.onconnectionstatechange = () => {
            handlers.onConnectionStateChange(pc ? pc.connectionState : 'closed');
        };
        // Separate from connectionState: ICE specifically flickering to
        // 'disconnected' and back is the earliest, most direct signal of a
        // shaky network, often well before (or without ever reaching) a full
        // connection failure -- this is what drives the "unstable connection"
        // indicator, independent of whether the call ultimately survives it.
        pc.oniceconnectionstatechange = () => {
            if (pc && handlers.onIceConnectionStateChange) handlers.onIceConnectionStateChange(pc.iceConnectionState);
        };

        localStream = await getLocalMedia(withVideo);
        videoEnabled = localStream.getVideoTracks().length > 0;
        localStream.getTracks().forEach((track) => pc.addTrack(track, localStream));
        handlers.onLocalStream(localStream, videoEnabled);
    }

    // Caller's side: build the connection, produce an offer to send.
    async function createOffer(iceServers, withVideo, handlers) {
        await createConnection(iceServers, withVideo, handlers);
        const offer = await pc.createOffer();
        await pc.setLocalDescription(offer);
        return { sdp: offer, video: videoEnabled };
    }

    // Callee's side: build the connection around the received offer, produce
    // an answer to send back. Matches video on/off to what the caller asked
    // for -- no point requesting a camera for an audio-only call.
    async function createAnswer(iceServers, remoteOffer, withVideo, handlers) {
        await createConnection(iceServers, withVideo, handlers);
        await pc.setRemoteDescription(remoteOffer);
        const answer = await pc.createAnswer();
        await pc.setLocalDescription(answer);
        return { sdp: answer, video: videoEnabled };
    }

    // Caller's side, once the callee's answer arrives.
    async function acceptAnswer(remoteAnswer) {
        if (!pc) return;
        await pc.setRemoteDescription(remoteAnswer);
    }

    // Either side, as ICE candidates trickle in from the peer. Failures here
    // are usually just a candidate arriving after the call already ended --
    // not worth surfacing as an error.
    async function addIceCandidate(candidate) {
        if (!pc) return;
        try {
            await pc.addIceCandidate(candidate);
        } catch (err) {
            // benign
        }
    }

    function setMuted(muted) {
        if (!localStream) return;
        localStream.getAudioTracks().forEach((track) => { track.enabled = !muted; });
    }

    // Toggling a track's enabled flag (rather than renegotiating to add/
    // remove it) keeps this simple and avoids a second offer/answer round --
    // the track keeps flowing, just as silence/black frames, same approach
    // as mute.
    function setVideoEnabled(enabled) {
        if (!localStream) return false;
        const tracks = localStream.getVideoTracks();
        if (!tracks.length) return false;
        tracks.forEach((track) => { track.enabled = enabled; });
        return true;
    }

    function hasVideo() {
        return !!(localStream && localStream.getVideoTracks().length);
    }

    function hangup() {
        if (pc) {
            pc.onicecandidate = null;
            pc.ontrack = null;
            pc.onconnectionstatechange = null;
            pc.oniceconnectionstatechange = null;
            pc.close();
            pc = null;
        }
        if (localStream) {
            localStream.getTracks().forEach((track) => track.stop());
            localStream = null;
        }
        videoEnabled = false;
    }

    function isActive() {
        return pc !== null;
    }

    return {
        createOffer, createAnswer, acceptAnswer, addIceCandidate,
        setMuted, setVideoEnabled, hasVideo, hangup, isActive,
    };
})();

/*
 * Ringback (what the caller hears while the phone "rings" on the other end)
 * and ringtone (what the callee hears for an incoming call), synthesized with
 * the Web Audio API instead of shipping an audio file -- one less asset to
 * load, works offline, and there's nothing for a license or a CDN to go wrong
 * with. Standard telephony tone pairs (dual-frequency, same idea as a real
 * ringback tone), not anything fancier.
 */
const CallAudio = (() => {
    let ctx = null;
    let timer = null;
    let activeNodes = [];

    function getCtx() {
        if (!ctx) ctx = new (window.AudioContext || window.webkitAudioContext)();
        if (ctx.state === 'suspended') ctx.resume();
        return ctx;
    }

    function tone(freqs, startAt, duration, gainValue) {
        const audioCtx = getCtx();
        const gain = audioCtx.createGain();
        gain.gain.value = gainValue;
        gain.connect(audioCtx.destination);
        const oscs = freqs.map((f) => {
            const osc = audioCtx.createOscillator();
            osc.type = 'sine';
            osc.frequency.value = f;
            osc.connect(gain);
            osc.start(startAt);
            osc.stop(startAt + duration);
            return osc;
        });
        activeNodes.push(...oscs, gain);
    }

    function stop() {
        if (timer) { clearInterval(timer); timer = null; }
        activeNodes.forEach((node) => { try { node.stop?.(); node.disconnect?.(); } catch (err) { /* already stopped */ } });
        activeNodes = [];
    }

    // Caller's side: a classic two-tone ringback, ~2s on / ~2s off.
    function startRingback() {
        stop();
        const audioCtx = getCtx();
        const cycle = () => tone([440, 480], audioCtx.currentTime, 1.8, 0.07);
        cycle();
        timer = setInterval(cycle, 4000);
    }

    // Callee's side: a brighter, faster double-chirp, closer to a phone's
    // actual ringtone than a ringback tone -- the two should feel distinct.
    function startRingtone() {
        stop();
        const audioCtx = getCtx();
        const cycle = () => {
            const t = audioCtx.currentTime;
            tone([587, 880], t, 0.35, 0.09);
            tone([587, 880], t + 0.45, 0.35, 0.09);
        };
        cycle();
        timer = setInterval(cycle, 2000);
    }

    return { startRingback, startRingtone, stop };
})();
