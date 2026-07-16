<!DOCTYPE html>
<html>

<head>
    <title>Fullscreen Mode</title>
    <meta charset="utf-8">
    <style>
        body,
        html {
            margin: 0;
            height: 100%;
            background-color: #282c34;
            color: white;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
        }

        button {
            padding: 10px 20px;
            font-size: 16px;
        }
    </style>
</head>

<body>
    <h1>Mode Fullscreen (Seperti F11)</h1>
    <button onclick="launchFullscreen(document.documentElement)">Masuk Fullscreen</button>

    <script>
        function launchFullscreen(element) {
            if (element.requestFullscreen) {
                element.requestFullscreen();
            } else if (element.webkitRequestFullscreen) {
                /* Safari */
                element.webkitRequestFullscreen();
            } else if (element.msRequestFullscreen) {
                /* IE11 */
                element.msRequestFullscreen();
            }
        }
    </script>
</body>

</html>