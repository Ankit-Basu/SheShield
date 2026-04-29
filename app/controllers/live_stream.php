<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Stream - Trae AI</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="src/trae-theme.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #1E1E2E 0%, #2E2E4E 100%);
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* Glassmorphic Effects */
        .glass-effect {
            background: rgba(46, 46, 78, 0.2);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(74, 30, 115, 0.2);
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
        }
        
        .glass-card {
            background: rgba(46, 46, 78, 0.2);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(74, 30, 115, 0.25);
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
            transition: all 0.3s ease;
        }
        
        .glass-nav-item {
            transition: all 0.3s ease;
            border: 1px solid transparent;
        }
        
        .glass-nav-item:hover {
            background: rgba(215, 109, 119, 0.15);
            border: 1px solid rgba(215, 109, 119, 0.2);
            transform: translateX(5px);
        }

        /* Video container styles */
        .video-container {
            position: relative;
            width: 100%;
            max-width: 640px;
            margin: 0 auto;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            border-radius: 0.75rem;
            overflow: hidden;
            border: 1px solid rgba(74, 30, 115, 0.3);
            background: rgba(46, 46, 78, 0.15);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }
        
        #videoElement {
            width: 100%;
            border-radius: 0.5rem;
            background-color: rgba(30, 30, 46, 0.8);
        }
        
        .controls {
            position: absolute;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 1rem;
            background: rgba(46, 46, 78, 0.7);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            padding: 0.5rem 1rem;
            border-radius: 2rem;
            border: 1px solid rgba(74, 30, 115, 0.3);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }
        
        .control-btn {
            background: none;
            border: none;
            color: white;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 50%;
            transition: all 0.3s;
        }
        
        .control-btn:hover {
            background: rgba(215, 109, 119, 0.3);
            transform: scale(1.1);
        }
        
        .control-btn#endStream:hover {
            background: rgba(220, 38, 38, 0.4);
        }
        
        .recording {
            animation: pulse 2s infinite;
        }
        
        /* Animations */
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }
        
        @keyframes fadeIn {
            0% { opacity: 0; transform: translateY(10px); }
            100% { opacity: 1; transform: translateY(0); }
        }
        
        .animate-fade-in {
            animation: fadeIn 0.5s ease-in-out forwards;
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: rgba(46, 46, 78, 0.3);
        }
        
        ::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, rgba(74, 30, 115, 0.7), rgba(215, 109, 119, 0.7));
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, rgba(74, 30, 115, 0.9), rgba(215, 109, 119, 0.9));
        }
    </style>
</head>
<body class="min-h-screen p-4">
    <!-- Background gradient shapes -->
    <div class="absolute -top-[300px] -right-[300px] w-[600px] h-[600px] bg-gradient-to-r from-[rgba(74,30,115,0.3)] to-[rgba(215,109,119,0.3)] rounded-full blur-3xl -z-10 animate-pulse-slow"></div>
    <div class="absolute -bottom-[200px] -left-[200px] w-[500px] h-[500px] bg-gradient-to-r from-[rgba(215,109,119,0.2)] to-[rgba(74,30,115,0.2)] rounded-full blur-3xl -z-10 animate-pulse-slow opacity-70"></div>
    <div class="max-w-4xl mx-auto relative z-10">
        <div class="glass-card rounded-xl p-6 animate-fade-in">
            <h1 class="text-2xl font-bold text-gradient mb-6 flex items-center">
                <span class="recording inline-block w-3 h-3 bg-red-500 rounded-full mr-2"></span>
                Live Stream
            </h1>
            
            <div class="video-container mb-4">
                <video id="videoElement" autoplay playsinline></video>
                <div class="controls">
                    <button class="control-btn" id="micToggle" title="Toggle Microphone">
                        <i class="fas fa-microphone"></i>
                    </button>
                    <button class="control-btn" id="cameraToggle" title="Toggle Camera">
                        <i class="fas fa-video"></i>
                    </button>
                    <button class="control-btn" id="shareBtn" title="Share Stream">
                        <i class="fas fa-share"></i>
                    </button>
                    <button class="control-btn" id="endStream" title="End Stream">
                        <i class="fas fa-phone-slash"></i>
                    </button>
                </div>
            </div>

            <div class="text-[#A0A0B0] text-sm mt-4 glass-effect p-3 rounded-lg inline-block">
                <p>Stream Status: <span id="streamStatus" class="font-semibold text-[#FFAF7B]">Active</span></p>
                <p class="mt-1">Connected to: Security Team</p>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/js/all.min.js"></script>
    <script>
        let stream;
        const videoElement = document.getElementById('videoElement');
        const micToggle = document.getElementById('micToggle');
        const cameraToggle = document.getElementById('cameraToggle');
        const shareBtn = document.getElementById('shareBtn');
        const endStream = document.getElementById('endStream');

        async function startStream() {
            try {
                stream = await navigator.mediaDevices.getUserMedia({ 
                    video: true, 
                    audio: true 
                });
                videoElement.srcObject = stream;
            } catch (err) {
                console.error('Error accessing media devices:', err);
                alert('Unable to access camera and microphone. Please ensure you have granted the necessary permissions.');
            }
        }

        micToggle.addEventListener('click', () => {
            if (stream) {
                const audioTrack = stream.getAudioTracks()[0];
                audioTrack.enabled = !audioTrack.enabled;
                micToggle.innerHTML = audioTrack.enabled ? 
                    '<i class="fas fa-microphone"></i>' : 
                    '<i class="fas fa-microphone-slash"></i>';
            }
        });

        cameraToggle.addEventListener('click', () => {
            if (stream) {
                const videoTrack = stream.getVideoTracks()[0];
                videoTrack.enabled = !videoTrack.enabled;
                cameraToggle.innerHTML = videoTrack.enabled ? 
                    '<i class="fas fa-video"></i>' : 
                    '<i class="fas fa-video-slash"></i>';
            }
        });

        shareBtn.addEventListener('click', () => {
            const streamUrl = window.location.href;
            navigator.clipboard.writeText(streamUrl).then(() => {
                alert('Stream URL copied to clipboard!');
            });
        });

        endStream.addEventListener('click', () => {
            if (confirm('Are you sure you want to end the stream?')) {
                if (stream) {
                    stream.getTracks().forEach(track => track.stop());
                }
                window.close();
            }
        });

        // Start stream when page loads
        startStream();

        // Handle page unload
        window.addEventListener('beforeunload', (e) => {
            if (stream) {
                stream.getTracks().forEach(track => track.stop());
            }
        });
    </script>
</body>
</html>
