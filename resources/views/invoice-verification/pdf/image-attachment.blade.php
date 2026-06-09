<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; }
        .header { margin-bottom: 12px; }
        .title { font-size: 16px; font-weight: bold; }
        .meta { color: #475569; margin-top: 4px; }
        .image-wrap { text-align: center; margin-top: 16px; }
        img { max-width: 100%; max-height: 700px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">{{ $document['label'] ?? 'Image Attachment' }}</div>
        <div class="meta">{{ $transaction->registration_number }} · {{ $document['file_name'] ?? '-' }}</div>
    </div>
    <div class="image-wrap">
        <img src="{{ $imageDataUri }}" alt="{{ $document['label'] ?? 'attachment image' }}">
    </div>
</body>
</html>
