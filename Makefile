styles:
	sass -s compressed --no-source-map assets/scss/sse.scss > assets/css-compiled/sse.min.css

watch:
	sass -s compressed --source-map --watch assets/scss/sse.scss:assets/css-compiled/sse.min.css
