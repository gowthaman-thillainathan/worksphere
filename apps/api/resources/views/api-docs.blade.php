<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Meridian API</title>
    <link rel="stylesheet" href="{{ $assetBaseUrl }}/swagger-ui.css">
</head>
<body>
<div id="swagger-ui"></div>
<script src="{{ $assetBaseUrl }}/swagger-ui-bundle.js" crossorigin></script>
<script>
    window.addEventListener('load', () => {
        window.ui = SwaggerUIBundle({
            url: @js($specUrl),
            dom_id: '#swagger-ui',
            deepLinking: true,
            persistAuthorization: false,
        });
    });
</script>
</body>
</html>
