<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Meridian API</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swagger-ui-dist@{{ $swaggerUiVersion }}/swagger-ui.css">
</head>
<body>
<div id="swagger-ui"></div>
<script src="https://cdn.jsdelivr.net/npm/swagger-ui-dist@{{ $swaggerUiVersion }}/swagger-ui-bundle.js" crossorigin></script>
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
