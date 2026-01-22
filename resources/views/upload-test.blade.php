<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Video Chunk Upload Test</title>
    <script src="https://cdn.jsdelivr.net/npm/resumablejs@1.1.0/resumable.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-10">

    <div class="max-w-2xl mx-auto bg-white p-8 rounded shadow">
        <h2 class="text-2xl font-bold mb-6 text-gray-800">Chunk Video Upload Test (Blade)</h2>

        <!-- Token Input -->



        <!-- File Input -->
        <div class="mb-6">
            <div id="dropTarget" class="border-2 border-dashed border-gray-300 p-10 text-center rounded cursor-pointer hover:bg-gray-50">
                <p class="text-gray-500">Drag & drop video here or click to select</p>
                <button id="browseButton" type="button" class="mt-2 bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Select Video</button>
            </div>
        </div>

        <!-- Progress Bar -->
        <div id="progressArea" class="hidden">
            <div class="w-full bg-gray-200 rounded-full h-4 mb-2">
                <div id="progressBar" class="bg-green-500 h-4 rounded-full" style="width: 0%"></div>
            </div>
            <p id="progressText" class="text-sm text-gray-600 text-center">0%</p>
        </div>

        <!-- Result Area -->
        <div id="resultArea" class="mt-4 hidden p-4 bg-green-100 text-green-800 rounded">
            <p class="font-bold">Upload Complete!</p>
            <p class="text-sm">File Path: <span id="filePath"></span></p>
        </div>

        <!-- Error Area -->
        <div id="errorArea" class="mt-4 hidden p-4 bg-red-100 text-red-800 rounded">
            <p class="font-bold">Error!</p>
            <p id="errorText" class="text-sm"></p>
        </div>
    </div>

    <script>
        // Configuration
        // Laravel URL helper ensures correct domain/IP
        const API_URL = "{{ url('/api/v1/admin/courses/upload-video') }}";

        var r = new Resumable({
            target: API_URL,
            query: {},
            fileType: ['mp4', 'mov', 'avi', 'mkv'],
            chunkSize: 2 * 1024 * 1024, // 2MB chunks
            headers: {
                'Accept': 'application/json'
            },
            testChunks: false,
            throttleProgressCallbacks: 1,
            fileParameterName: 'video_file'
        });

        r.assignBrowse(document.getElementById('browseButton'));
        r.assignDrop(document.getElementById('dropTarget'));

        r.on('fileAdded', function(file){
            const token = document.getElementById('apiToken').value;
            if(!token) {
                alert('Please enter your Bearer Token first!');
                r.cancel();
                return;
            }

            // Set Auth Header
            r.opts.headers['Authorization'] = 'Bearer ' + token;

            document.getElementById('progressArea').classList.remove('hidden');
            document.getElementById('resultArea').classList.add('hidden');
            document.getElementById('errorArea').classList.add('hidden');
            r.upload();
        });

        r.on('fileProgress', function(file){
            const progress = Math.floor(file.progress() * 100);
            document.getElementById('progressBar').style.width = progress + '%';
            document.getElementById('progressText').innerText = progress + '% Uploaded';
        });

        r.on('fileSuccess', function(file, message){
            try {
                const response = JSON.parse(message);
                document.getElementById('progressBar').style.width = '100%';
                document.getElementById('progressText').innerText = '100% (Processing Complete)';

                document.getElementById('resultArea').classList.remove('hidden');
                document.getElementById('filePath').innerText = response.path || response.message;
                console.log('Server Response:', response);
            } catch (e) {
                console.error('Parsing Error:', e);
            }
        });

        r.on('fileError', function(file, message){
            document.getElementById('errorArea').classList.remove('hidden');
            try {
                const response = JSON.parse(message);
                document.getElementById('errorText').innerText = response.message || message;
            } catch (e) {
                document.getElementById('errorText').innerText = message;
            }
            console.error('Upload Error:', message);
        });
    </script>
</body>
</html>
