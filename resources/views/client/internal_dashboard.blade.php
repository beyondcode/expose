<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Internal Dashboard</title>

    <link href="{{ $cssFile }}" rel="stylesheet">
</head>

<body>
    <div id="internalDashboard" data-page='@json($page)'></div>

    <script src="{{ $jsFile }}"></script>
</body>

</html>