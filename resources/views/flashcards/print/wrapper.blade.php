<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flashcards Print - {{ ucfirst(str_replace('_', ' ', $layout)) }}</title>
    <style>
        {!! $css !!}
    </style>
</head>
<body>
    <div class="print-wrapper">
        {!! $content !!}
    </div>
</body>
</html>